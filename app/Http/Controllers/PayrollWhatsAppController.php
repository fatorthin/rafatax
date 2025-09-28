<?php

namespace App\Http\Controllers;

use App\Models\PayrollDetail;
use App\Services\WablasService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function sendPayslip(PayrollDetail $detail)
    {
        try {
            Log::info('Starting sendPayslip for detail ID: ' . $detail->id);

            // Validasi nomor telepon
            if (!$detail->staff->phone) {
                Log::warning('No phone number for staff: ' . $detail->staff->name);
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor telepon staff tidak tersedia'
                ], 400);
            }

            // Format nomor telepon
            $phone = $this->formatPhoneNumber($detail->staff->phone);

            // Hitung total gaji
            $totalSalary = $this->calculateTotalSalary($detail);

            // Format periode
            $period = \Carbon\Carbon::parse($detail->payroll->payroll_date)->format('F Y');

            // Kirim pesan WhatsApp
            $result = $this->wablasService->sendPayslipMessage(
                $phone,
                $detail->staff->name,
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
            // Validasi nomor telepon
            if (!$detail->staff->phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor telepon staff tidak tersedia'
                ], 400);
            }

            // Format nomor telepon
            $phone = $this->formatPhoneNumber($detail->staff->phone);

            // Hitung total gaji
            $totalSalary = $this->calculateTotalSalary($detail);

            // Format periode
            $period = \Carbon\Carbon::parse($detail->payroll->payroll_date)->format('F Y');

            // Generate PDF
            $pdfPath = $this->generatePayslipPdf($detail);

            if (!$pdfPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat PDF slip gaji'
                ], 500);
            }

            // Kirim PDF via WhatsApp
            $result = $this->wablasService->sendPayslipWithPdf(
                $phone,
                $detail->staff->name,
                $period,
                $totalSalary,
                $pdfPath
            );

            // Hapus file PDF temporary
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Slip gaji PDF berhasil dikirim ke WhatsApp'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim slip gaji PDF: ' . $result['message']
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error sending payslip PDF via WhatsApp: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengirim slip gaji PDF'
            ], 500);
        }
    }

    private function generatePayslipPdf(PayrollDetail $detail): ?string
    {
        try {
            // Hitung komponen gaji
            $bonusLembur = $detail->overtime_count * 10000;
            $bonusVisitSolo = $detail->visit_solo_count * 10000;
            $bonusVisitLuar = $detail->visit_luar_solo_count * 15000;
            $cutSakit = $detail->sick_leave_count * 0.5 * $detail->salary / 25;
            $cutHalfday = $detail->halfday_count * 0.5 * $detail->salary / 25;
            $cutIjin = $detail->leave_count * $detail->salary / 25;
            $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $detail->bonus_lain;
            $totalPot = $detail->cut_bpjs_kesehatan + $detail->cut_bpjs_ketenagakerjaan + $detail->cut_lain + $detail->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
            $totalGaji = $detail->salary + $detail->bonus_position + $detail->bonus_competency + $totalBonus - $totalPot;

            // Generate PDF
            $pdf = Pdf::loadView('pdf.payslip', [
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
            ])->setPaper('a5', 'portrait');

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
        $bonusLembur = $detail->overtime_count * 10000;
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
}
