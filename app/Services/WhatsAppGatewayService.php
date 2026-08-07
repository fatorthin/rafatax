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
        $this->deviceId = trim((string)($cfg['device_id'] ?? ''));
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

    /**
     * Helper header HTTP untuk go-whatsapp-web-multidevice (v9.0.0)
     */
    private function getHeaders(): array
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

        if (!empty($this->deviceId)) {
            $headers['X-Device-Id'] = $this->deviceId;
            $headers['X-Device'] = $this->deviceId;
        }

        return $headers;
    }

    /**
     * Membuat instance HTTP Client Laravel dengan opsi SSL & Timeout
     */
    private function httpClient()
    {
        $client = Http::withHeaders($this->getHeaders())
            ->timeout($this->timeout);

        if (!$this->verifySsl) {
            $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Cek status koneksi perangkat & info profil
     */
    public function getStatus(): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'connected' => false,
                'message' => 'WhatsApp Gateway sedang dinonaktifkan di .env (WHATSAPP_GATEWAY_ENABLED=false)',
            ];
        }

        $endpointsToTry = [
            '/user/my/profile',
            '/user/info',
            '/user/devices',
            '/devices',
            '/user/login',
        ];

        $lastError = null;

        foreach ($endpointsToTry as $endpoint) {
            try {
                $response = $this->httpClient()->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'connected' => true,
                        'message' => 'WhatsApp Gateway terhubung (via ' . $endpoint . ')',
                        'endpoint' => $endpoint,
                        'data' => $data['results'] ?? $data['data'] ?? $data,
                    ];
                }

                $lastError = 'Gateway (' . $endpoint . ') merespon dengan HTTP ' . $response->status() . ': ' . ($response->json()['message'] ?? $response->body());
            } catch (\Throwable $e) {
                $lastError = 'Error saat menghubungi ' . $endpoint . ': ' . $e->getMessage();
            }
        }

        return [
            'success' => false,
            'connected' => false,
            'message' => $lastError ?? 'Tidak dapat terhubung ke server WhatsApp Gateway.',
        ];
    }

    /**
     * Ambil data QR Code atau status login
     */
    public function getLoginQr(): array
    {
        try {
            $response = $this->httpClient()->get($this->baseUrl . '/user/login');
            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'message' => $json['message'] ?? 'Berhasil mengambil data login',
                    'data' => $json['results'] ?? $json['data'] ?? $json,
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil QR Code (HTTP ' . $response->status() . ')',
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
     * Putus koneksi session perangkat
     */
    public function logoutDevice(): array
    {
        try {
            $response = $this->httpClient()->post($this->baseUrl . '/user/logout');
            return [
                'success' => $response->successful(),
                'message' => $response->json()['message'] ?? ($response->successful() ? 'Berhasil logout' : 'Gagal logout'),
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
                'message' => 'Nomor telepon tidak valid: ' . $phone,
            ];
        }

        try {
            $payload = [
                'phone' => $cleanPhone,
                'message' => $message,
            ];

            $response = $this->httpClient()->post($this->baseUrl . '/send/message', $payload);

            Log::info('WhatsAppGateway sendMessage response', [
                'phone' => $cleanPhone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->successful(),
                'message' => $response->json()['message'] ?? ($response->successful() ? 'Pesan berhasil terkirim' : 'Gagal mengirim pesan'),
                'http_code' => $response->status(),
                'data' => $response->json(),
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
        if (!$cleanPhone || !file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Nomor tidak valid atau file tidak ditemukan: ' . $filePath,
            ];
        }

        try {
            $client = $this->httpClient();
            $filename = basename($filePath);
            $fileStream = fopen($filePath, 'r');

            $response = $client->attach('image', $fileStream, $filename)
                ->post($this->baseUrl . '/send/image', [
                    'phone' => $cleanPhone,
                    'caption' => $caption,
                ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->successful(),
                'message' => $response->json()['message'] ?? ($response->successful() ? 'Gambar berhasil terkirim' : 'Gagal mengirim gambar'),
                'http_code' => $response->status(),
                'data' => $response->json(),
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
     * Kirim Pesan Dokumen (PDF/File)
     */
    public function sendDocument(string $phone, string $filePath, string $caption = ''): array
    {
        $cleanPhone = $this->normalizePhone($phone);
        if (!$cleanPhone || !file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Nomor tidak valid atau file tidak ditemukan: ' . $filePath,
            ];
        }

        try {
            $client = $this->httpClient();
            $filename = basename($filePath);
            $fileStream = fopen($filePath, 'r');

            // go-whatsapp-web-multidevice v9.0.0 mendukung /send/file atau /send/document
            $endpoint = $this->baseUrl . '/send/file';

            $response = $client->attach('file', $fileStream, $filename)
                ->post($endpoint, [
                    'phone' => $cleanPhone,
                    'caption' => $caption,
                ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            if (!$response->successful()) {
                // Fallback ke /send/document jika /send/file merespon 404
                if ($response->status() === 404) {
                    $fileStream2 = fopen($filePath, 'r');
                    $response = $client->attach('document', $fileStream2, $filename)
                        ->post($this->baseUrl . '/send/document', [
                            'phone' => $cleanPhone,
                            'caption' => $caption,
                        ]);
                    if (is_resource($fileStream2)) {
                        fclose($fileStream2);
                    }
                }
            }

            return [
                'success' => $response->successful(),
                'status' => $response->successful(),
                'message' => $response->json()['message'] ?? ($response->successful() ? 'Dokumen berhasil terkirim' : 'Gagal mengirim dokumen'),
                'http_code' => $response->status(),
                'data' => $response->json(),
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
