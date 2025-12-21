<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WablasService
{
    private $token;
    private $secretKey;
    private $baseUrl;

    public function __construct()
    {
        $this->token = config('services.wablas.token');
        $this->secretKey = config('services.wablas.secret_key');
        $this->baseUrl = config('services.wablas.base_url', 'https://texas.wablas.com/api');
    }

    /**
     * Kirim pesan teks sederhana
     * Sesuai dokumentasi: https://texas.wablas.com/documentation/api#single-send-text
     */
    public function sendMessage(string $phone, string $message): array
    {
        $curl = curl_init();

        $data = [
            'phone' => $phone,
            'message' => $message,
        ];

        // Format Authorization sesuai dokumentasi: token.secret_key
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: {$this->token}.{$this->secretKey}",
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

    /**
     * Kirim document dari file lokal
     * Menggunakan endpoint /send-document-from-local sesuai dokumentasi WAblas
     * https://texas.wablas.com/documentation/api#send-document-local
     */
    public function sendDocument($phone, $filePath, $caption = '')
    {
        try {
            // Normalize path untuk Windows
            $normalizedPath = realpath($filePath);

            if (!file_exists($normalizedPath)) {
                Log::error('WAblas: File tidak ditemukan', [
                    'original_path' => $filePath,
                    'normalized_path' => $normalizedPath
                ]);
                return [
                    'status' => false,
                    'message' => 'File tidak ditemukan: ' . $filePath
                ];
            }

            Log::info('WAblas: Mengirim dokumen dari lokal', [
                'phone' => $phone,
                'file_path' => $normalizedPath,
                'file_size' => filesize($normalizedPath),
                'file_exists' => file_exists($normalizedPath),
                'mime_type' => mime_content_type($normalizedPath)
            ]);

            // Baca file sebagai base64 (sesuai dokumentasi untuk /send-document-from-local)
            $fileContent = file_get_contents($normalizedPath);
            $fileName = basename($normalizedPath);

            // Method dari dokumentasi: encode file sebagai base64
            $data = [
                'phone' => $phone,
                'file' => base64_encode($fileContent),
                'data' => json_encode(['name' => $fileName])
            ];

            $curl = curl_init();

            // Format Authorization sesuai dokumentasi: token.secret_key
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->baseUrl . "/send-document-from-local",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($data), // x-www-form-urlencoded
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->token}.{$this->secretKey}"
                ],
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            ]);

            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            curl_close($curl);

            Log::info('WAblas: Response send document from local', [
                'http_code' => $httpCode,
                'response' => $result,
                'curl_error' => $error,
                'content_type' => $info['content_type'] ?? null,
            ]);

            $response = json_decode($result, true);

            // Cek jika masih error 403, kembalikan dengan flag untuk fallback
            if ($httpCode === 403 || !($response['status'] ?? false)) {
                return [
                    'status' => false,
                    'message' => $response['message'] ?? 'Failed to send document',
                    'http_code' => $httpCode,
                    'use_fallback' => true
                ];
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('WAblas: Exception saat send document', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'use_fallback' => true
            ];
        }
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
        $caption = "📄 Slip Gaji {$staffName} - {$period}";

        $documentResult = $this->sendDocument($phone, $pdfPath, $caption);

        // Jika gagal kirim document, gunakan fallback: simpan ke public dan kirim link
        $documentSuccess = isset($documentResult['status']) && $documentResult['status'] === true;

        if (!$documentSuccess) {
            Log::warning('Document send failed, using fallback method (link)', [
                'phone' => $phone,
                'error' => $documentResult['message'] ?? 'Unknown error'
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

    /**
     * Kirim image dari file lokal
     * Menggunakan endpoint /send-image-from-local sesuai dokumentasi WAblas
     * https://texas.wablas.com/documentation/api#send-image-local
     */
    public function sendImage($phone, $filePath, $caption = '')
    {
        try {
            // Normalize path untuk Windows
            $normalizedPath = realpath($filePath);

            if (!file_exists($normalizedPath)) {
                Log::error('WAblas: File gambar tidak ditemukan', [
                    'original_path' => $filePath,
                    'normalized_path' => $normalizedPath
                ]);
                return [
                    'status' => false,
                    'message' => 'File gambar tidak ditemukan: ' . $filePath
                ];
            }

            Log::info('WAblas: Mengirim gambar dari lokal', [
                'phone' => $phone,
                'file_path' => $normalizedPath,
                'file_size' => filesize($normalizedPath),
                'file_exists' => file_exists($normalizedPath),
                'mime_type' => mime_content_type($normalizedPath)
            ]);

            // Baca file sebagai base64
            $fileContent = file_get_contents($normalizedPath);
            $fileName = basename($normalizedPath);

            // Method dari dokumentasi: encode file sebagai base64
            $data = [
                'phone' => $phone,
                'caption' => $caption,
                'file' => base64_encode($fileContent),
                'data' => json_encode(['name' => $fileName])
            ];

            $curl = curl_init();

            // Format Authorization sesuai dokumentasi: token.secret_key
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->baseUrl . "/send-image-from-local",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($data), // x-www-form-urlencoded
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->token}.{$this->secretKey}"
                ],
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            ]);

            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            curl_close($curl);

            Log::info('WAblas: Response send image from local', [
                'http_code' => $httpCode,
                'response' => $result,
                'curl_error' => $error,
                'content_type' => $info['content_type'] ?? null,
            ]);

            $response = json_decode($result, true);

            // Cek jika error
            if ($httpCode >= 400 || !($response['status'] ?? false)) {
                return [
                    'status' => false,
                    'message' => $response['message'] ?? 'Failed to send image',
                    'http_code' => $httpCode,
                    'use_fallback' => true
                ];
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('WAblas: Exception saat send image', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'use_fallback' => true
            ];
        }
    }
}
