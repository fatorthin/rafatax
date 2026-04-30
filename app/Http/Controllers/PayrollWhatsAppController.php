<?php

namespace App\Http\Controllers;

use App\Models\PayrollDetail;
use App\Services\WablasService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class PayrollWhatsAppController extends Controller
{
    protected $wablasService;

    public function __construct(WablasService $wablasService)
    {
        $this->wablasService = $wablasService;
    }

    public function sendBulkPayslips(\Illuminate\Support\Collection $details)
    {
        try {
            Log::info('Starting sendBulkPayslips for ' . $details->count() . ' items');

            $textPayload = ['data' => []];
            $documentPayload = ['data' => []];
            $publicPath = public_path('storage/payslips/');

            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            foreach ($details as $detail) {
                if (!$detail->staff_id && !$detail->no_wa_non_staff) {
                    continue;
                }
                if ($detail->staff_id && !$detail->staff->phone) {
                    continue;
                }

                $phone = $this->formatPhoneNumber($detail->staff_id ? $detail->staff->phone : $detail->no_wa_non_staff);
                $period = \Carbon\Carbon::parse($detail->payroll->payroll_date)->format('F Y');
                $totalSalary = $this->calculateTotalSalary($detail);
                $name = $detail->staff_id ? $detail->staff->name : $detail->nama_non_staff;

                // 1. Prepare Text Message
                $message = "📋 *SLIP GAJI*\n\n";
                $message .= "👤 Nama: {$name}\n";
                $message .= "📅 Periode: {$period}\n";
                $message .= "💰 Total Gaji: Rp " . number_format($totalSalary, 0, ',', '.') . "\n\n";
                $message .= "📄 Detail Slip Gaji dalam bentuk PDF terlampir setelah pesan ini.\n";
                $message .= "Terima kasih atas Kerja Cerdas dan Konstribusi anda untuk RAFATax!🙏";

                $textPayload['data'][] = [
                    'phone' => $phone,
                    'message' => $message,
                ];

                // 2. Prepare PDF Document
                $pdfPath = $this->generatePayslipPdf($detail); // This returns temp path

                if ($pdfPath && file_exists($pdfPath)) {
                    $filename = basename($pdfPath);
                    $publicFile = $publicPath . $filename;

                    if (copy($pdfPath, $publicFile)) {
                        $documentUrl = url('storage/payslips/' . $filename);

                        $documentPayload['data'][] = [
                            'phone' => $phone,
                            'document' => $documentUrl,
                        ];

                        // Remove temp file
                        unlink($pdfPath);
                    }
                }
            }

            // Execute Bulk Sending
            $textResult = ['success' => false, 'message' => 'No data'];
            $docResult = ['success' => false, 'message' => 'No data'];

            if (!empty($textPayload['data'])) {
                Log::info('Sending bulk text messages...');
                $textResult = $this->wablasService->sendBulkMessage($textPayload);
            }

            if (!empty($documentPayload['data'])) {
                Log::info('Sending bulk documents...');
                $docResult = $this->wablasService->sendBulkDocument($documentPayload);
            }

            return [
                'success' => ($textResult['success'] ?? false) || ($docResult['success'] ?? false),
                'text_result' => $textResult,
                'doc_result' => $docResult,
                'count' => count($textPayload['data'])
            ];
        } catch (\Exception $e) {
            Log::error('Error in sendBulkPayslips: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendPayslip(PayrollDetail $detail)
    {
        try {
            Log::info('Starting sendPayslip for detail ID: ' . $detail->id);

            // Validasi nomor telepon
            if ($detail->staff_id && !$detail->staff->phone) {
                Log::warning('No phone number for staff: ' . $detail->staff->name);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor telepon staff tidak tersedia'
                ], 400);
            }
            if (!$detail->staff_id && !$detail->no_wa_non_staff) {
                Log::warning('No phone number for non-staff: ' . $detail->nama_non_staff);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor WhatsApp Non Staff tidak tersedia'
                ], 400);
            }

            // Format nomor telepon
            $phone = $this->formatPhoneNumber($detail->staff_id ? $detail->staff->phone : $detail->no_wa_non_staff);
            $name = $detail->staff_id ? $detail->staff->name : $detail->nama_non_staff;

            // Hitung total gaji
            $totalSalary = $this->calculateTotalSalary($detail);

            // Format periode
            $period = \Carbon\Carbon::parse($detail->payroll->payroll_date)->format('F Y');

            // Kirim pesan WhatsApp
            $result = $this->wablasService->sendPayslipMessage(
                $phone,
                $name,
                $period,
                $totalSalary
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Slip gaji berhasil dikirim ke WhatsApp'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim slip gaji: ' . $result['message']
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error sending payslip via WhatsApp: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengirim slip gaji: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendPayslipWithPdf(PayrollDetail $detail)
    {
        try {
            Log::info('Starting sendPayslipWithPdf', [
                'detail_id' => $detail->id,
                'staff' => $detail->staff_id ? $detail->staff->name : $detail->nama_non_staff
            ]);

            // Validasi nomor telepon
            if ($detail->staff_id && !$detail->staff->phone) {
                Log::warning('No phone number', ['staff' => $detail->staff->name]);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor telepon staff tidak tersedia'
                ], 400);
            }
            if (!$detail->staff_id && !$detail->no_wa_non_staff) {
                Log::warning('No phone number', ['non-staff' => $detail->nama_non_staff]);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor WhatsApp Non Staff tidak tersedia'
                ], 400);
            }

            // Format nomor telepon
            $phone = $this->formatPhoneNumber($detail->staff_id ? $detail->staff->phone : $detail->no_wa_non_staff);
            $name = $detail->staff_id ? $detail->staff->name : $detail->nama_non_staff;
            Log::info('Phone formatted', ['original' => $detail->staff_id ? $detail->staff->phone : $detail->no_wa_non_staff, 'formatted' => $phone]);

            // Hitung total gaji
            $totalSalary = $this->calculateTotalSalary($detail);

            // Format periode
            $period = \Carbon\Carbon::parse($detail->payroll->payroll_date)->format('F Y');

            // Generate PDF
            Log::info('Generating PDF...');
            $pdfPath = $this->generatePayslipPdf($detail);

            if (!$pdfPath) {
                Log::error('Failed to generate PDF');
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat PDF slip gaji'
                ], 500);
            }

            Log::info('PDF generated successfully', [
                'path' => $pdfPath,
                'size' => filesize($pdfPath) . ' bytes'
            ]);

            // Kirim PDF via WhatsApp
            Log::info('Sending to Wablas API...');
            $result = $this->wablasService->sendPayslipWithPdf(
                $phone,
                $name,
                $period,
                $totalSalary,
                $pdfPath
            );

            // Hapus file PDF temporary
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
                Log::info('Temporary PDF deleted');
            }

            if ($result['success']) {
                Log::info('PDF sent successfully via WhatsApp');

                $message = 'Slip gaji PDF berhasil dikirim ke WhatsApp';
                if (isset($result['fallback']) && $result['fallback']) {
                    $message = 'Slip gaji dikirim via link download ke WhatsApp';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'fallback' => $result['fallback'] ?? false
                ]);
            } else {
                Log::error('Failed to send via Wablas', [
                    'http_code' => $result['http_code'] ?? null,
                    'message' => $result['message'] ?? 'Unknown error',
                    'data' => $result['data'] ?? null
                ]);

                // Pesan error yang lebih spesifik
                $errorMessage = 'Gagal mengirim slip gaji PDF';
                if (isset($result['http_code']) && $result['http_code'] == 500) {
                    $errorMessage .= ': Server Wablas bermasalah. Mungkin device offline atau quota habis.';
                } elseif (isset($result['message'])) {
                    $errorMessage .= ': ' . $result['message'];
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception in sendPayslipWithPdf', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generatePayslipPdf(PayrollDetail $detail): ?string
    {
        try {
            // Hitung komponen gaji
            $bonusLembur = $detail->overtime_count * $detail->overtime_multiplier;
            $bonusVisitSolo = $detail->visit_solo_count * 10000;
            $bonusVisitLuar = $detail->visit_luar_solo_count * 15000;
            $cutSakit = $detail->sick_leave_count * 0.5 * $detail->salary / 25;
            $cutHalfday = $detail->halfday_count * 0.5 * $detail->salary / 25;
            $cutIjin = $detail->leave_count * $detail->salary / 25;
            $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $detail->bonus_lain;
            $totalPot = $detail->cut_bpjs_kesehatan + $detail->cut_bpjs_ketenagakerjaan + $detail->cut_lain + $detail->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
            $totalGaji = $detail->salary + $detail->bonus_position + $detail->bonus_competency + $totalBonus - $totalPot;

            // Generate PDF
            $pdf = PDF::loadView('pdf.payslip', [
                'detail' => $detail,
                'bonusLembur' => $bonusLembur,
                'bonusVisitSolo' => $bonusVisitSolo,
                'bonusVisitLuar' => $bonusVisitLuar,
                'cutSakit' => $cutSakit,
                'cutHalfday' => $cutHalfday,
                'cutIjin' => $cutIjin,
                'totalBonus' => $totalBonus,
                'totalPot' => $totalPot,
                'totalGaji' => $totalGaji,
            ])->setPaper('a4', 'portrait')
                ->setOption(['isFontSubsettingEnabled' => false])
                ->setOption(['isPhpEnabled' => false])
                ->setOption(['isHtml5ParserEnabled' => true])
                ->setOption(['isRemoteEnabled' => false])
                ->setOption(['compress' => 1]);

            // Simpan ke temporary file
            $filename = 'slip_gaji_' . $detail->id . '_' . time() . '.pdf';
            $tempPath = storage_path('app/temp/' . $filename);

            // Pastikan direktori temp ada
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Error generating payslip PDF: ' . $e->getMessage());
            return null;
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/\D/', '', $phone);

        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        // Jika tidak dimulai dengan 62, tambahkan 62
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    private function calculateTotalSalary(PayrollDetail $detail): float
    {
        $bonusLembur = $detail->overtime_count * $detail->overtime_multiplier;
        $bonusVisitSolo = $detail->visit_solo_count * 10000;
        $bonusVisitLuar = $detail->visit_luar_solo_count * 15000;
        $cutSakit = $detail->sick_leave_count * 0.5 * $detail->salary / 25;
        $cutHalfday = $detail->halfday_count * 0.5 * $detail->salary / 25;
        $cutIjin = $detail->leave_count * $detail->salary / 25;

        return $detail->salary +
            $detail->bonus_position +
            $detail->bonus_competency +
            $bonusLembur +
            $bonusVisitSolo +
            $bonusVisitLuar +
            $detail->bonus_lain -
            $detail->cut_bpjs_kesehatan -
            $detail->cut_bpjs_ketenagakerjaan -
            $detail->cut_lain -
            $detail->cut_hutang -
            $cutSakit -
            $cutHalfday -
            $cutIjin;
    }

    public function downloadSlip(PayrollDetail $detail)
    {
        try {
            Log::info('Starting downloadSlip for detail ID: ' . $detail->id);

            // Hitung komponen gaji
            $bonusLembur = $detail->overtime_count * $detail->overtime_multiplier;
            $bonusVisitSolo = $detail->visit_solo_count * 10000;
            $bonusVisitLuar = $detail->visit_luar_solo_count * 15000;
            $cutSakit = $detail->sick_leave_count * 0.5 * $detail->salary / 25;
            $cutHalfday = $detail->halfday_count * 0.5 * $detail->salary / 25;
            $cutIjin = $detail->leave_count * $detail->salary / 25;
            $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $detail->bonus_lain;
            $totalPot = $detail->cut_bpjs_kesehatan + $detail->cut_bpjs_ketenagakerjaan + $detail->cut_lain + $detail->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
            $totalGaji = $detail->salary + $detail->bonus_position + $detail->bonus_competency + $totalBonus - $totalPot;

            // Generate PDF
            $pdf = PDF::loadView('pdf.payslip', [
                'detail' => $detail,
                'bonusLembur' => $bonusLembur,
                'bonusVisitSolo' => $bonusVisitSolo,
                'bonusVisitLuar' => $bonusVisitLuar,
                'cutSakit' => $cutSakit,
                'cutHalfday' => $cutHalfday,
                'cutIjin' => $cutIjin,
                'totalBonus' => $totalBonus,
                'totalPot' => $totalPot,
                'totalGaji' => $totalGaji,
            ])->setPaper('a4', 'portrait');

            $name = $detail->staff_id ? $detail->staff->name : $detail->nama_non_staff;
            $filename = 'Slip_Gaji_' . str_replace(' ', '_', $name) . '_' . \Carbon\Carbon::parse($detail->payroll->payroll_date)->format('F_Y') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Error downloading payslip: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->back()->with('error', 'Gagal mengunduh slip gaji: ' . $e->getMessage());
        }
    }
}
