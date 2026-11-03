<?php

namespace App\Http\Controllers;

use App\Helpers\PiutangCategoryMouExporter;
use App\Models\CategoryMou;
use App\Models\Invoice;
use App\Models\MoU;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiutangCategoryMouController extends Controller
{
    /**
     * Tampilkan Halaman Detail Piutang Kategori MoU.
     */
    public function show($id, Request $request)
    {
        $category = CategoryMou::findOrFail($id);

        $year   = $request->get('year', (string) date('Y'));
        $entity = strtolower($request->get('entity', 'all'));
        $status = strtolower($request->get('status', 'all'));

        $data = $this->prepareCategoryDetailData($category, $year, $entity, $status);

        return view('filament.pages.piutang-category-mou-detail', array_merge($data, [
            'category' => $category,
            'year'     => $year,
            'entity'   => $entity,
            'status'   => $status,
        ]));
    }

    /**
     * Download Excel Rincian Piutang 1 Kategori MoU.
     */
    public function exportDetail($id, Request $request)
    {
        $category = CategoryMou::findOrFail($id);

        $year   = $request->get('year', (string) date('Y'));
        $entity = strtolower($request->get('entity', 'all'));
        $status = strtolower($request->get('status', 'all'));

        $data = $this->prepareCategoryDetailData($category, $year, $entity, $status);

        $subtitleParts = [];
        $subtitleParts[] = $year === 'all' ? 'Semua Tahun' : "Tahun {$year}";
        if ($entity !== 'all') {
            $subtitleParts[] = 'Tipe ' . strtoupper($entity);
        }
        if ($status !== 'all') {
            $subtitleParts[] = 'Status ' . ucfirst($status);
        }
        $subtitle = implode(' | ', $subtitleParts);

        return PiutangCategoryMouExporter::exportDetail($category, $data, $subtitle);
    }

    /**
     * Download Excel Rekapitulasi Piutang Seluruh Kategori MoU.
     */
    public function exportSummary(Request $request)
    {
        $year   = $request->get('year', (string) date('Y'));
        $entity = strtolower($request->get('entity', 'all'));

        $records = $this->getSummaryRecords($year, $entity);

        $subtitleParts = [];
        $subtitleParts[] = $year === 'all' ? 'Periode: Semua Waktu' : "Periode: Tahun {$year}";
        if ($entity !== 'all') {
            $subtitleParts[] = 'Tipe: ' . strtoupper($entity);
        }
        $subtitle = implode(' | ', $subtitleParts);

        return PiutangCategoryMouExporter::exportSummary($records, $subtitle);
    }

    /**
     * Helper menyiapkan data rincian kategori untuk view & excel.
     */
    private function prepareCategoryDetailData(CategoryMou $category, string $year, string $entity, string $status): array
    {
        // 1. Query Invoices
        $invoicesQuery = Invoice::query()
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoices.memo_id')
            ->whereHas('mou', function ($q) use ($category, $entity) {
                $q->where('category_mou_id', $category->id)
                    ->where(function ($sub) {
                        $sub->where('mou_piutang_lama', false)->orWhereNull('mou_piutang_lama');
                    });
                if ($entity === 'pt' || $entity === 'kkp') {
                    $q->where('type', $entity);
                }
            })
            ->with([
                'mou.client',
                'client',
                'costListInvoices',
            ]);

        if ($year !== 'all') {
            $invoicesQuery->whereYear('invoice_date', (int) $year);
        }

        if ($status === 'unpaid') {
            $invoicesQuery->where('invoice_status', '!=', 'paid');
        } elseif ($status === 'paid') {
            $invoicesQuery->where('invoice_status', 'paid');
        }

        $invoiceModels = $invoicesQuery->orderBy('invoice_date', 'desc')->get();

        $invoicesList = [];
        $totalTagihan = 0;
        $totalTerbayar = 0;
        $totalSisaPiutang = 0;
        $countPaid = 0;
        $countUnpaid = 0;

        foreach ($invoiceModels as $inv) {
            $amount = (float) $inv->costListInvoices->sum('amount');
            $clientName = $inv->client_name ?: ($inv->mou?->client?->company_name ?? '-');
            $isPaid = $inv->invoice_status === 'paid';

            $totalTagihan += $amount;
            if ($isPaid) {
                $totalTerbayar += $amount;
                $countPaid++;
            } else {
                $totalSisaPiutang += $amount;
                $countUnpaid++;
            }

            $invoicesList[] = [
                'id'              => $inv->id,
                'invoice_number'  => $inv->invoice_number,
                'invoice_date'    => $inv->invoice_date,
                'due_date'        => $inv->due_date,
                'invoice_status'  => $inv->invoice_status,
                'invoice_type'    => $inv->invoice_type ?: ($inv->mou?->type ?? '-'),
                'is_send_invoice' => $inv->is_send_invoice,
                'send_invoice_date' => $inv->send_invoice_date,
                'tgl_transfer'    => $inv->tgl_transfer,
                'rek_transfer'    => $inv->rek_transfer,
                'client_name'     => $clientName,
                'mou_number'      => $inv->mou?->mou_number ?? '-',
                'mou_id'          => $inv->mou_id,
                'amount'          => $amount,
            ];
        }

        // 2. Query Payments (Invoices paid)
        $paymentsList = [];
        foreach ($invoicesList as $inv) {
            if ($inv['invoice_status'] === 'paid') {
                $paymentsList[] = [
                    'invoice_id'     => $inv['id'],
                    'invoice_number' => $inv['invoice_number'],
                    'date'           => $inv['tgl_transfer'] ?: $inv['invoice_date'],
                    'client_name'    => $inv['client_name'],
                    'mou_number'     => $inv['mou_number'],
                    'rek_transfer'   => $inv['rek_transfer'] ?: 'Kas / Bank',
                    'amount'         => $inv['amount'],
                ];
            }
        }
        usort($paymentsList, fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        // 3. Breakdown per MoU
        $mouQuery = MoU::where('category_mou_id', $category->id)
            ->where(function ($sub) {
                $sub->where('mou_piutang_lama', false)->orWhereNull('mou_piutang_lama');
            })
            ->when($entity !== 'all', fn ($q) => $q->where('type', $entity))
            ->with([
                'client',
                'cost_lists',
                'invoices' => function ($q) use ($year) {
                    $q->whereNull('memo_id');
                    if ($year !== 'all') {
                        $q->whereYear('invoice_date', (int) $year);
                    }
                },
                'invoices.costListInvoices',
            ]);

        $mouBreakdown = $mouQuery->get()->map(function ($mou) {
            $costMou = (float) $mou->cost_lists->sum('amount');
            $totalInv = (float) $mou->invoices->flatMap->costListInvoices->sum('amount');
            $totalPaid = (float) $mou->invoices->where('invoice_status', 'paid')->flatMap->costListInvoices->sum('amount');
            $sisaPiutang = $totalInv - $totalPaid;

            return [
                'id'            => $mou->id,
                'mou_number'    => $mou->mou_number,
                'client_name'   => $mou->client?->company_name ?? '-',
                'type'          => $mou->type,
                'cost_mou'      => $costMou,
                'total_invoice' => $totalInv,
                'total_paid'    => $totalPaid,
                'sisa_piutang'  => $sisaPiutang,
                'invoices_count' => $mou->invoices->count(),
            ];
        })->filter(fn ($m) => $m['total_invoice'] > 0 || $m['cost_mou'] > 0)->values()->toArray();

        $rasioPelunasan = $totalTagihan > 0 ? round(($totalTerbayar / $totalTagihan) * 100, 1) : 100;

        return [
            'invoices'          => $invoicesList,
            'payments'          => $paymentsList,
            'mou_breakdown'     => $mouBreakdown,
            'totalTagihan'      => $totalTagihan,
            'totalTerbayar'     => $totalTerbayar,
            'totalSisaPiutang'  => $totalSisaPiutang,
            'countPaid'         => $countPaid,
            'countUnpaid'       => $countUnpaid,
            'countTotal'        => count($invoicesList),
            'rasioPelunasan'    => $rasioPelunasan,
            'countMou'          => count($mouBreakdown),
        ];
    }

    /**
     * Helper mengambil daftar rekapitulasi seluruh kategori MoU.
     */
    public function getSummaryRecords(string $year = '2026', string $entity = 'all'): \Illuminate\Support\Collection
    {
        $categories = CategoryMou::query()->orderBy('name', 'asc')->get();

        return $categories->map(function ($cat) use ($year, $entity) {
            $mouQuery = MoU::where('category_mou_id', $cat->id)
                ->where(function ($sub) {
                    $sub->where('mou_piutang_lama', false)->orWhereNull('mou_piutang_lama');
                })
                ->when($entity !== 'all', fn ($q) => $q->where('type', $entity));

            $mouCount = (clone $mouQuery)
                ->where(function ($q) use ($year) {
                    if ($year !== 'all') {
                        $q->whereYear('created_at', (int) $year)
                            ->orWhereHas('invoices', fn ($inv) => $inv->whereNull('memo_id')->whereYear('invoice_date', (int) $year));
                    }
                })
                ->count();

            // Total Invoice
            $invTotal = (float) \App\Models\CostListInvoice::whereHas('invoice', function ($q) use ($cat, $year, $entity) {
                $q->whereNull('memo_id')
                    ->whereHas('mou', function ($m) use ($cat, $entity) {
                        $m->where('category_mou_id', $cat->id)
                            ->where(function ($sub) {
                                $sub->where('mou_piutang_lama', false)->orWhereNull('mou_piutang_lama');
                            });
                        if ($entity !== 'all') {
                            $m->where('type', $entity);
                        }
                    });
                if ($year !== 'all') {
                    $q->whereYear('invoice_date', (int) $year);
                }
            })->sum('amount');

            // Total Pembayaran (status=paid)
            $invPaid = (float) \App\Models\CostListInvoice::whereHas('invoice', function ($q) use ($cat, $year, $entity) {
                $q->whereNull('memo_id')
                    ->where('invoice_status', 'paid')
                    ->whereHas('mou', function ($m) use ($cat, $entity) {
                        $m->where('category_mou_id', $cat->id)
                            ->where(function ($sub) {
                                $sub->where('mou_piutang_lama', false)->orWhereNull('mou_piutang_lama');
                            });
                        if ($entity !== 'all') {
                            $m->where('type', $entity);
                        }
                    });
                if ($year !== 'all') {
                    $q->whereYear('invoice_date', (int) $year);
                }
            })->sum('amount');

            $sisaPiutang = $invTotal - $invPaid;

            $cat->jumlah_mou = $mouCount;
            $cat->total_invoice = $invTotal;
            $cat->total_pembayaran = $invPaid;
            $cat->total_piutang = $sisaPiutang;

            return $cat;
        });
    }
}

