<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    private string $baseUrl;
    private string $auth;
    private string $deviceId;
    private bool $verifySsl;
    private int $timeout;
    private bool $enabled;

    public function __construct(?array $config = null)
    {
        $cfg = $config ?? config('services.whatsapp_gateway', []);

        $this->enabled = (bool)($cfg['enabled'] ?? true);
        $this->baseUrl = rtrim((string)($cfg['url'] ?? 'https://wagateway.surakana.my.id'), '/');
        $this->auth = trim((string)($cfg['auth'] ?? 'admin:admin'));
        $this->deviceId = trim((string)($cfg['device_id'] ?? 'rafatax'));
        $this->verifySsl = (bool)($cfg['verify_ssl'] ?? true);
        $this->timeout = (int)($cfg['timeout'] ?? 30);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function setDeviceId(string $deviceId): self
    {
        $this->deviceId = trim($deviceId);
        return $this;
    }

    /**
     * Helper header HTTP untuk go-whatsapp-web-multidevice
     */
    private function getHeaders(?string $overrideDeviceId = null): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($this->auth)) {
            if (str_contains($this->auth, ':')) {
                $headers['Authorization'] = 'Basic ' . base64_encode($this->auth);
            } else {
                $headers['Authorization'] = $this->auth;
            }
        }

        $devId = $overrideDeviceId ?? $this->deviceId;
        if (!empty($devId)) {
            $headers['X-Device-Id'] = $devId;
            $headers['X-Device'] = $devId;
        }

        return $headers;
    }

    /**
     * Membuat instance HTTP Client Laravel dengan opsi SSL & Timeout
     */
    private function httpClient(?string $overrideDeviceId = null)
    {
        $client = Http::withHeaders($this->getHeaders($overrideDeviceId))
            ->timeout($this->timeout);

        if (!$this->verifySsl) {
            $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Ambil daftar seluruh device yang terdaftar di server
     */
    public function getDevices(): array
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            $res = $this->httpClient()->get($this->baseUrl . '/devices');
            if ($res->successful()) {
                $json = $res->json();
                return $json['results'] ?? $json['data'] ?? [];
            }
        } catch (\Throwable $e) {
            Log::error('WhatsAppGatewayService getDevices error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Buat device baru di server
     */
    public function createDevice(string $deviceId): array
    {
        try {
            $res = $this->httpClient()->asJson()->post($this->baseUrl . '/devices', [
                'device_id' => trim($deviceId),
            ]);

            return [
                'success' => $res->successful(),
                'message' => $res->json()['message'] ?? ($res->successful() ? 'Perangkat berhasil dibuat' : 'Gagal membuat perangkat'),
                'data' => $res->json()['results'] ?? $res->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error membuat perangkat: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Hapus device dari server
     */
    public function deleteDevice(string $deviceId): array
    {
        try {
            $res = $this->httpClient()->delete($this->baseUrl . '/devices/' . urlencode(trim($deviceId)));
            return [
                'success' => $res->successful(),
                'message' => $res->json()['message'] ?? ($res->successful() ? 'Perangkat berhasil dihapus' : 'Gagal menghapus perangkat'),
                'data' => $res->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error menghapus perangkat: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek status koneksi perangkat aktif
     */
    public function getStatus(?string $overrideDeviceId = null): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'connected' => false,
                'message' => 'WhatsApp Gateway dinonaktifkan di konfigurasi (WHATSAPP_GATEWAY_ENABLED=false)',
            ];
        }

        $targetDevice = $overrideDeviceId ?? $this->deviceId;

        // 1. Coba endpoint /app/status dengan target device ID
        if (!empty($targetDevice)) {
            try {
                $response = $this->httpClient($targetDevice)->get($this->baseUrl . '/app/status');
                if ($response->successful()) {
                    $json = $response->json();
                    $results = $json['results'] ?? [];
                    $isConnected = (bool)($results['is_connected'] ?? false);
                    $isLoggedIn = (bool)($results['is_logged_in'] ?? false);
                    $jid = $results['jid'] ?? '';

                    return [
                        'success' => true,
                        'connected' => $isConnected && $isLoggedIn,
                        'is_connected' => $isConnected,
                        'is_logged_in' => $isLoggedIn,
                        'device_id' => $targetDevice,
                        'jid' => $jid,
                        'message' => ($isConnected && $isLoggedIn)
                            ? "Device {$targetDevice} terhubung (" . ($jid ?: 'Online') . ")"
                            : "Device {$targetDevice} belum terhubung / terputus",
                        'data' => $results,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsAppGatewayService /app/status check notice: ' . $e->getMessage());
            }
        }

        // 2. Fallback: Ambil data via /devices
        try {
            $response = $this->httpClient()->get($this->baseUrl . '/devices');
            if ($response->successful()) {
                $devices = $response->json()['results'] ?? [];
                $found = null;
                foreach ($devices as $dev) {
                    if (($dev['id'] ?? '') === $targetDevice) {
                        $found = $dev;
                        break;
                    }
                }

                if ($found) {
                    $isOnline = in_array(strtolower((string)($found['state'] ?? '')), ['logged_in', 'connected']);
                    return [
                        'success' => true,
                        'connected' => $isOnline,
                        'device_id' => $targetDevice,
                        'message' => $isOnline ? "Device {$targetDevice} aktif ({$found['state']})" : "Device {$targetDevice} status: {$found['state']}",
                        'data' => $found,
                    ];
                }

                return [
                    'success' => true,
                    'connected' => false,
                    'device_id' => $targetDevice,
                    'message' => "Device '{$targetDevice}' belum ditemukan di server gateway",
                    'data' => ['available_devices' => $devices],
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'connected' => false,
                'message' => 'Error koneksi server WhatsApp Gateway: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => false,
            'connected' => false,
            'message' => 'Tidak dapat terhubung ke server WhatsApp Gateway.',
        ];
    }

    /**
     * Ambil data QR Code login untuk scanning
     */
    public function getLoginQr(?string $overrideDeviceId = null): array
    {
        $targetDevice = $overrideDeviceId ?? $this->deviceId;
        if (empty($targetDevice)) {
            return [
                'success' => false,
                'message' => 'Device ID belum ditentukan',
            ];
        }

        try {
            $response = $this->httpClient($targetDevice)->get($this->baseUrl . '/app/login');
            if ($response->successful()) {
                $json = $response->json();
                $results = $json['results'] ?? [];
                $qrLink = $results['qr_link'] ?? null;
                $qrDuration = $results['qr_duration'] ?? 30;

                $qrBase64 = null;
                if ($qrLink) {
                    try {
                        $imgRes = Http::timeout(10)->get($qrLink);
                        if ($imgRes->successful()) {
                            $mime = $imgRes->header('Content-Type') ?: 'image/png';
                            $qrBase64 = 'data:' . $mime . ';base64,' . base64_encode($imgRes->body());
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed downloading QR image from link: ' . $e->getMessage());
                    }
                }

                return [
                    'success' => true,
                    'message' => $json['message'] ?? 'Berhasil mengambil QR Code login',
                    'device_id' => $targetDevice,
                    'qr_link' => $qrLink,
                    'qr_base64' => $qrBase64,
                    'qr_duration' => $qrDuration,
                    'data' => $results,
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil QR Code (HTTP ' . $response->status() . '): ' . ($response->json()['message'] ?? $response->body()),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi QR: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Coba reconnect perangkat
     */
    public function reconnectDevice(?string $overrideDeviceId = null): array
    {
        $targetDevice = $overrideDeviceId ?? $this->deviceId;
        try {
            $response = $this->httpClient($targetDevice)->get($this->baseUrl . '/app/reconnect');
            return [
                'success' => $response->successful(),
                'message' => $response->json()['message'] ?? ($response->successful() ? 'Perangkat sedang mencoba menghubungkan ulang...' : 'Gagal reconnect'),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error reconnect: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Putus koneksi session perangkat (Logout)
     */
    public function logoutDevice(?string $overrideDeviceId = null): array
    {
        $targetDevice = $overrideDeviceId ?? $this->deviceId;
        try {
            $response = $this->httpClient($targetDevice)->get($this->baseUrl . '/app/logout');
            return [
                'success' => $response->successful(),
                'message' => $response->json()['message'] ?? ($response->successful() ? 'Perangkat berhasil logout' : 'Gagal logout'),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error logout: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim Pesan Teks
     */
    public function sendMessage(string $phone, string $message): array
    {
        $cleanPhone = $this->normalizePhone($phone);
        if (!$cleanPhone) {
            return [
                'success' => false,
                'status' => false,
                'message' => 'Nomor telepon tidak valid: ' . $phone,
            ];
        }

        try {
            $payload = [
                'phone' => $cleanPhone,
                'message' => $message,
            ];

            $response = $this->httpClient()->asJson()->post($this->baseUrl . '/send/message', $payload);

            $isSuccess = $response->successful();
            $data = $response->json();

            Log::info('WhatsAppGateway sendMessage response', [
                'phone' => $cleanPhone,
                'status' => $response->status(),
                'body' => $data,
            ]);

            return [
                'success' => $isSuccess,
                'status' => $isSuccess,
                'message' => $data['message'] ?? ($isSuccess ? 'Pesan berhasil terkirim' : 'Gagal mengirim pesan'),
                'http_code' => $response->status(),
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsAppGatewayService sendMessage Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim Pesan Gambar
     */
    public function sendImage(string $phone, string $filePath, string $caption = ''): array
    {
        $cleanPhone = $this->normalizePhone($phone);
        $normalizedPath = realpath($filePath) ?: $filePath;

        if (!$cleanPhone || !file_exists($normalizedPath)) {
            return [
                'success' => false,
                'status' => false,
                'message' => 'Nomor tidak valid atau file gambar tidak ditemukan: ' . $filePath,
            ];
        }

        try {
            $client = $this->httpClient();
            $filename = basename($normalizedPath);
            $fileStream = fopen($normalizedPath, 'r');

            $response = $client->attach('image', $fileStream, $filename)
                ->post($this->baseUrl . '/send/image', [
                    'phone' => $cleanPhone,
                    'caption' => $caption,
                ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            $isSuccess = $response->successful();
            $data = $response->json();

            return [
                'success' => $isSuccess,
                'status' => $isSuccess,
                'message' => $data['message'] ?? ($isSuccess ? 'Gambar berhasil terkirim' : 'Gagal mengirim gambar'),
                'http_code' => $response->status(),
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsAppGatewayService sendImage Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim Pesan Dokumen (PDF/File Lokal)
     */
    public function sendDocument(string $phone, string $filePath, string $caption = ''): array
    {
        $cleanPhone = $this->normalizePhone($phone);
        $normalizedPath = realpath($filePath) ?: $filePath;

        if (!$cleanPhone || !file_exists($normalizedPath)) {
            return [
                'success' => false,
                'status' => false,
                'message' => 'Nomor tidak valid atau file dokumen tidak ditemukan: ' . $filePath,
            ];
        }

        try {
            $client = $this->httpClient();
            $filename = basename($normalizedPath);
            $fileStream = fopen($normalizedPath, 'r');

            $endpoint = $this->baseUrl . '/send/file';
            $response = $client->attach('file', $fileStream, $filename)
                ->post($endpoint, [
                    'phone' => $cleanPhone,
                    'caption' => $caption,
                ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            if (!$response->successful() && $response->status() === 404) {
                // Fallback ke /send/document
                $fileStream2 = fopen($normalizedPath, 'r');
                $response = $client->attach('document', $fileStream2, $filename)
                    ->post($this->baseUrl . '/send/document', [
                        'phone' => $cleanPhone,
                        'caption' => $caption,
                    ]);
                if (is_resource($fileStream2)) {
                    fclose($fileStream2);
                }
            }

            $isSuccess = $response->successful();
            $data = $response->json();

            return [
                'success' => $isSuccess,
                'status' => $isSuccess,
                'message' => $data['message'] ?? ($isSuccess ? 'Dokumen berhasil terkirim' : 'Gagal mengirim dokumen'),
                'http_code' => $response->status(),
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsAppGatewayService sendDocument Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim dokumen via URL publik
     * Jika gateway tidak dapat mendownload URL langsung, fungsi ini otomatis
     * mendownload ke temporary file dan mengirimkannya via multipart upload.
     */
    public function sendDocumentUrl(string $phone, string $url, string $caption = ''): array
    {
        $cleanPhone = $this->normalizePhone($phone);
        if (!$cleanPhone) {
            return [
                'success' => false,
                'status' => false,
                'message' => 'Nomor telepon tidak valid: ' . $phone,
            ];
        }

        // 1. Coba kirim via file_url parameter ke endpoint gateway
        try {
            $response = $this->httpClient()->asJson()->post($this->baseUrl . '/send/file', [
                'phone' => $cleanPhone,
                'file_url' => $url,
                'caption' => $caption,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => true,
                    'message' => 'Dokumen berhasil terkirim via URL',
                    'data' => $response->json(),
                ];
            }
        } catch (\Throwable $e) {
            Log::info('Direct file_url send attempt skipped, using download fallback: ' . $e->getMessage());
        }

        // 2. Fallback: Download file dari URL ke local temp file, lalu kirim
        $tempPath = null;
        try {
            $downloadRes = Http::timeout(45)->get($url);
            if ($downloadRes->successful()) {
                $tempDir = storage_path('app/temp');
                if (!file_exists($tempDir)) {
                    @mkdir($tempDir, 0755, true);
                }

                $filename = basename(parse_url($url, PHP_URL_PATH)) ?: ('document_' . time() . '.pdf');
                $tempPath = $tempDir . '/' . uniqid('wa_doc_') . '_' . $filename;
                file_put_contents($tempPath, $downloadRes->body());

                $res = $this->sendDocument($cleanPhone, $tempPath, $caption);

                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }

                return $res;
            }
        } catch (\Throwable $e) {
            Log::error('WhatsAppGatewayService sendDocumentUrl download error: ' . $e->getMessage());
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        // 3. Fallback Terakhir: Kirim tautan link via pesan teks
        $fallbackMsg = "📄 *DOKUMEN TERLAMPIR*\n\n";
        if (!empty($caption)) {
            $fallbackMsg .= $caption . "\n\n";
        }
        $fallbackMsg .= "Silakan unduh dokumen melalui tautan berikut:\n";
        $fallbackMsg .= "🔗 " . $url . "\n\n";
        $fallbackMsg .= "Terima kasih.";

        return $this->sendMessage($cleanPhone, $fallbackMsg);
    }

    /**
     * Kirim pesan notifikasi slip gaji teks
     */
    public function sendPayslipMessage(string $phone, string $staffName, string $period, string $totalSalary): array
    {
        $message = "*SLIP GAJI*\n\n";
        $message .= "Nama: {$staffName}\n";
        $message .= "Periode: {$period}\n";
        $message .= "Total Gaji: Rp " . number_format((float)$totalSalary, 0, ',', '.') . "\n\n";
        $message .= "Terima kasih atas Kerja Cerdas dan Konstribusi anda untuk RAFATax! 🙏";

        return $this->sendMessage($phone, $message);
    }

    /**
     * Kirim slip gaji lengkap dengan PDF lampiran + fallback link jika diperlukan
     */
    public function sendPayslipWithPdf(string $phone, string $staffName, string $period, string $totalSalary, string $pdfPath): array
    {
        // 1. Kirim pesan notifikasi pengantar
        $message = "*SLIP GAJI*\n\n";
        $message .= "Nama: {$staffName}\n";
        $message .= "Periode: {$period}\n";
        $message .= "Total Gaji: Rp " . number_format((float)$totalSalary, 0, ',', '.') . "\n\n";
        $message .= "📄 Detail Slip Gaji dalam bentuk PDF terlampir setelah pesan ini.\n";
        $message .= "Terima kasih atas Kerja Cerdas dan Konstribusi anda untuk RAFATax! 🙏";

        $messageResult = $this->sendMessage($phone, $message);
        if (!($messageResult['success'] ?? false)) {
            return $messageResult;
        }

        // 2. Kirim PDF dokumen
        $caption = "Slip Gaji {$staffName} - {$period}";
        $documentResult = $this->sendDocument($phone, $pdfPath, $caption);

        $docSuccess = ($documentResult['success'] ?? false) || ($documentResult['status'] ?? false);
        if ($docSuccess) {
            return [
                'success' => true,
                'status' => true,
                'message' => 'Slip gaji PDF berhasil dikirim ke WhatsApp',
                'data' => $documentResult['data'] ?? [],
            ];
        }

        // 3. Fallback jika gagal kirim file PDF langsung: Simpan ke public storage & kirim link unduh
        Log::warning('WhatsApp Gateway send document failed, falling back to public link', [
            'phone' => $phone,
            'error' => $documentResult['message'] ?? 'Unknown error',
        ]);

        $publicDir = public_path('storage/payslips/');
        if (!file_exists($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }

        $filename = 'slip_gaji_' . str_replace(' ', '_', $staffName) . '_' . time() . '.pdf';
        $publicFile = $publicDir . $filename;

        if (@copy($pdfPath, $publicFile)) {
            $downloadUrl = url('storage/payslips/' . $filename);

            $fallbackMsg = "\n\n⚠️ *UPDATE SLIP GAJI*\n\n";
            $fallbackMsg .= "Detail PDF slip gaji Anda dapat diunduh langsung pada tautan berikut:\n\n";
            $fallbackMsg .= "🔗 {$downloadUrl}\n\n";
            $fallbackMsg .= "⏰ Link berlaku 7 hari.\n";
            $fallbackMsg .= "_Simpan dokumen ini untuk arsip pribadi Anda._";

            $linkResult = $this->sendMessage($phone, $fallbackMsg);
            if ($linkResult['success']) {
                return [
                    'success' => true,
                    'status' => true,
                    'fallback' => true,
                    'download_url' => $downloadUrl,
                    'message' => 'Slip gaji dikirim via link download ke WhatsApp (fallback mode)',
                ];
            }
        }

        return $documentResult;
    }

    /**
     * Kirim pesan massal (Bulk Message)
     */
    public function sendBulkMessage(array $payload): array
    {
        $rows = $payload['data'] ?? [];
        $total = count($rows);
        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($rows as $item) {
            $phone = $item['phone'] ?? '';
            $msg = $item['message'] ?? '';
            if (!$phone || !$msg) {
                $failedCount++;
                continue;
            }

            $res = $this->sendMessage($phone, $msg);
            if ($res['success'] ?? false) {
                $successCount++;
            } else {
                $failedCount++;
            }
            $results[] = $res;

            usleep(300000); // 300ms delay to prevent WhatsApp rate limits
        }

        return [
            'success' => $successCount > 0,
            'status' => $successCount > 0,
            'total' => $total,
            'sent' => $successCount,
            'failed' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Kirim dokumen massal via URL (Bulk Document)
     */
    public function sendBulkDocument(array $payload): array
    {
        $rows = $payload['data'] ?? [];
        $total = count($rows);
        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($rows as $item) {
            $phone = $item['phone'] ?? '';
            $docUrl = $item['document'] ?? $item['url'] ?? '';
            $caption = $item['caption'] ?? '';

            if (!$phone || !$docUrl) {
                $failedCount++;
                continue;
            }

            $res = $this->sendDocumentUrl($phone, $docUrl, $caption);
            if ($res['success'] ?? false) {
                $successCount++;
            } else {
                $failedCount++;
            }
            $results[] = $res;

            usleep(400000); // 400ms delay
        }

        return [
            'success' => $successCount > 0,
            'status' => $successCount > 0,
            'total' => $total,
            'sent' => $successCount,
            'failed' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Normalisasi nomor telepon ke format internasional (e.g. 628xxx)
     */
    public function normalizePhone(?string $raw): ?string
    {
        if (!$raw) return null;
        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if ($digits === '') return null;

        if (str_starts_with($digits, '08')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        return strlen($digits) >= 10 ? $digits : null;
    }
}
