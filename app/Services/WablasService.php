<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WablasService
{
    private $token;
    private $secretKey;
    private $baseUrl;
    private $authHeaderStyle;

    public function __construct()
    {
        $this->token = config('services.wablas.token');
        $this->secretKey = config('services.wablas.secret_key');
        $this->baseUrl = config('services.wablas.base_url', 'https://texas.wablas.com/api');
        $this->authHeaderStyle = config('services.wablas.auth_header', 'concat'); // concat|token|bearer
    }

    public function sendMessage(string $phone, string $message): array
    {
        $curl = curl_init();

        $data = [
            'phone' => $phone,
            'message' => $message,
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            $this->buildAuthHeader(),
        ]);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . "/send-message");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            Log::error('Wablas API Error: ' . $error);
            return [
                'success' => false,
                'message' => 'Error: ' . $error
            ];
        }

        $response = json_decode($result, true);

        Log::info('Wablas API Response', [
            'http_code' => $httpCode,
            'response' => $response,
            'raw' => $result,
        ]);

        return [
            'success' => $httpCode === 200,
            'message' => $response['message'] ?? 'Unknown response',
            'data' => $response,
            'http_code' => $httpCode,
        ];
    }

    public function sendDocument(string $phone, string $filePath, string $filename, string $caption = ''): array
    {
        $curl = curl_init();

        // Cek apakah file ada
        if (!file_exists($filePath)) {
            Log::error('Wablas: File tidak ditemukan', ['path' => $filePath]);
            return [
                'success' => false,
                'message' => 'File tidak ditemukan: ' . $filePath
            ];
        }

        // Validasi ukuran file (max 10MB untuk WhatsApp document)
        $fileSize = filesize($filePath);
        if ($fileSize > 10 * 1024 * 1024) {
            Log::error('Wablas: File terlalu besar', [
                'size' => $fileSize,
                'max' => '10MB'
            ]);
            return [
                'success' => false,
                'message' => 'File terlalu besar (max 10MB): ' . round($fileSize / 1024 / 1024, 2) . 'MB'
            ];
        }

        Log::info('Wablas: Mengirim dokumen', [
            'phone' => $phone,
            'file' => $filename,
            'size' => $fileSize . ' bytes',
            'path' => $filePath
        ]);

        // Prepare multipart form data
        $postFields = [
            'phone' => $phone,
            'document' => new \CURLFile($filePath, 'application/pdf', $filename),
            'caption' => $caption
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            $this->buildAuthHeader(),
        ]);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . "/send-document");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            Log::error('Wablas Document CURL Error: ' . $error);
            return [
                'success' => false,
                'message' => 'Error koneksi: ' . $error
            ];
        }

        $response = json_decode($result, true);

        Log::error('Wablas Document API Response', [
            'http_code' => $httpCode,
            'response' => $response,
            'raw' => $result,
            'phone' => $phone,
            'file_size' => $fileSize
        ]);

        // Jika gagal, berikan pesan yang lebih informatif
        if ($httpCode !== 200) {
            $errorMsg = $response['message'] ?? 'Unknown error';
            Log::error('Wablas Document API Failed', [
                'http_code' => $httpCode,
                'message' => $errorMsg,
                'response' => $response
            ]);
        }

        return [
            'success' => $httpCode === 200,
            'message' => $response['message'] ?? 'Unknown response',
            'data' => $response,
            'http_code' => $httpCode,
        ];
    }

    public function sendPayslipMessage(string $phone, string $staffName, string $period, string $totalSalary): array
    {
        $message = "📋 *SLIP GAJI RAFATAX*\n\n";
        $message .= "👤 Nama: {$staffName}\n";
        $message .= "📅 Periode: {$period}\n";
        $message .= "💰 Total Gaji: Rp " . number_format($totalSalary, 0, ',', '.') . "\n\n";
        $message .= "Slip gaji detail telah dikirim melalui sistem.\n";
        $message .= "Terima kasih atas kerja keras Anda! 🙏";

        return $this->sendMessage($phone, $message);
    }

    public function sendPayslipWithPdf(string $phone, string $staffName, string $period, string $totalSalary, string $pdfPath): array
    {
        // Kirim pesan notifikasi dulu
        $message = "📋 *SLIP GAJI RAFATAX*\n\n";
        $message .= "👤 Nama: {$staffName}\n";
        $message .= "📅 Periode: {$period}\n";
        $message .= "💰 Total Gaji: Rp " . number_format($totalSalary, 0, ',', '.') . "\n\n";
        $message .= "📄 Slip gaji detail dalam bentuk PDF akan dikirim setelah pesan ini.\n";
        $message .= "Terima kasih atas kerja keras Anda! 🙏";

        $messageResult = $this->sendMessage($phone, $message);

        if (!$messageResult['success']) {
            return $messageResult;
        }

        // Coba kirim PDF via document
        $filename = "Slip_Gaji_{$staffName}_{$period}.pdf";
        $caption = "📄 Slip Gaji {$staffName} - {$period}";

        $documentResult = $this->sendDocument($phone, $pdfPath, $filename, $caption);

        // Jika gagal kirim document, gunakan fallback: simpan ke public dan kirim link
        if (!$documentResult['success']) {
            Log::warning('Document send failed, using fallback method (link)', [
                'phone' => $phone,
                'error' => $documentResult['message']
            ]);

            // Simpan PDF ke public storage
            $publicPath = public_path('storage/payslips/');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            $filename = 'slip_gaji_' . str_replace(' ', '_', $staffName) . '_' . time() . '.pdf';
            $publicFile = $publicPath . $filename;

            if (!copy($pdfPath, $publicFile)) {
                Log::error('Failed to copy PDF to public storage');
                return $documentResult; // Return original error
            }

            $downloadUrl = url('storage/payslips/' . $filename);

            // Kirim link download via WhatsApp
            $fallbackMessage = "\n\n⚠️ *UPDATE*\n\n";
            $fallbackMessage .= "PDF tidak dapat dikirim langsung.\n";
            $fallbackMessage .= "Silakan download slip gaji Anda di:\n\n";
            $fallbackMessage .= "🔗 {$downloadUrl}\n\n";
            $fallbackMessage .= "⏰ Link berlaku 7 hari.\n";
            $fallbackMessage .= "_Simpan atau screenshot link ini._";

            $linkResult = $this->sendMessage($phone, $fallbackMessage);

            if ($linkResult['success']) {
                Log::info('Fallback: PDF link sent successfully', [
                    'phone' => $phone,
                    'url' => $downloadUrl
                ]);

                return [
                    'success' => true,
                    'message' => 'Slip gaji dikirim via link download (fallback mode)',
                    'fallback' => true,
                    'download_url' => $downloadUrl
                ];
            } else {
                return $linkResult;
            }
        }

        return $documentResult;
    }

    private function buildAuthHeader(): string
    {
        // Beberapa instance Wablas memakai format yang berbeda untuk Authorization
        // concat: "Authorization: {token}.{secretKey}"
        // token:  "Authorization: {token}"
        // bearer: "Authorization: Bearer {token}"
        $style = strtolower((string) $this->authHeaderStyle);
        if ($style === 'bearer') {
            return "Authorization: Bearer {$this->token}";
        }
        if ($style === 'token') {
            return "Authorization: {$this->token}";
        }
        return "Authorization: {$this->token}.{$this->secretKey}";
    }
}
