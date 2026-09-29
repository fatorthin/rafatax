<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * WablasService Adapter / Proxy
 *
 * Mengalihkan seluruh panggilan WhatsApp ke WhatsAppGatewayService mandiri
 * (go-whatsapp-web-multidevice). Ini memastikan backward compatibility penuh
 * untuk seluruh job antrean, controller, dan action Filament yang sebelumnya
 * bergantung pada Wablas.
 */
class WablasService
{
    protected WhatsAppGatewayService $gateway;

    public function __construct(?WhatsAppGatewayService $gateway = null)
    {
        $this->gateway = $gateway ?? app(WhatsAppGatewayService::class);
    }

    /**
     * Kirim pesan teks
     */
    public function sendMessage(string $phone, string $message): array
    {
        Log::info('WablasService::sendMessage redirected to WhatsAppGatewayService', ['phone' => $phone]);
        return $this->gateway->sendMessage($phone, $message);
    }

    /**
     * Kirim dokumen dari file lokal
     */
    public function sendDocument($phone, $filePath, $caption = ''): array
    {
        Log::info('WablasService::sendDocument redirected to WhatsAppGatewayService', ['phone' => $phone, 'file' => $filePath]);
        return $this->gateway->sendDocument((string)$phone, (string)$filePath, (string)$caption);
    }

    /**
     * Kirim dokumen via URL publik
     */
    public function sendDocumentUrl(string $phone, string $url, string $caption = ''): array
    {
        Log::info('WablasService::sendDocumentUrl redirected to WhatsAppGatewayService', ['phone' => $phone, 'url' => $url]);
        return $this->gateway->sendDocumentUrl($phone, $url, $caption);
    }

    /**
     * Kirim gambar dari file lokal
     */
    public function sendImage($phone, $filePath, $caption = ''): array
    {
        Log::info('WablasService::sendImage redirected to WhatsAppGatewayService', ['phone' => $phone, 'file' => $filePath]);
        return $this->gateway->sendImage((string)$phone, (string)$filePath, (string)$caption);
    }

    /**
     * Kirim notifikasi teks slip gaji
     */
    public function sendPayslipMessage(string $phone, string $staffName, string $period, string $totalSalary): array
    {
        return $this->gateway->sendPayslipMessage($phone, $staffName, $period, $totalSalary);
    }

    /**
     * Kirim slip gaji lengkap dengan file PDF
     */
    public function sendPayslipWithPdf(string $phone, string $staffName, string $period, string $totalSalary, string $pdfPath): array
    {
        return $this->gateway->sendPayslipWithPdf($phone, $staffName, $period, $totalSalary, $pdfPath);
    }

    /**
     * Kirim pesan massal
     */
    public function sendBulkMessage(array $payload): array
    {
        return $this->gateway->sendBulkMessage($payload);
    }

    /**
     * Kirim dokumen massal
     */
    public function sendBulkDocument(array $payload): array
    {
        return $this->gateway->sendBulkDocument($payload);
    }
}
