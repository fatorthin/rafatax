<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WablasService;

class TestWablasConnection extends Command
{
    protected $signature = 'wablas:test {phone?}';
    protected $description = 'Test Wablas API connection and device status';

    public function handle()
    {
        $phone = $this->argument('phone') ?? '6285725380708';

        $this->info('Testing Wablas API Connection...');
        $this->info('Phone: ' . $phone);
        $this->newLine();

        $wablas = new WablasService();

        // Test 1: Send simple message
        $this->info('1. Testing send message...');
        $result = $wablas->sendMessage($phone, 'Test message from Rafatax Payroll System');

        if ($result['success']) {
            $this->info('✓ Message sent successfully!');
            $this->line('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error('✗ Failed to send message');
            $this->line('Error: ' . $result['message']);
            $this->line('HTTP Code: ' . ($result['http_code'] ?? 'N/A'));
        }

        $this->newLine();

        // Test 2: Check device status via API
        $this->info('2. Checking device status...');
        $deviceStatus = $this->checkDeviceStatus();

        if ($deviceStatus) {
            $this->info('Device Status: ' . json_encode($deviceStatus, JSON_PRETTY_PRINT));
        }

        $this->newLine();

        // Test 3: Create dummy PDF and try sending
        $this->info('3. Testing document send...');
        $testPdfPath = $this->createTestPdf();

        if ($testPdfPath) {
            $this->line('Test PDF created: ' . $testPdfPath);
            $this->line('File size: ' . filesize($testPdfPath) . ' bytes');

            $result = $wablas->sendDocument(
                $phone,
                $testPdfPath,
                'test_document.pdf',
                'Test document from Rafatax'
            );

            if ($result['success']) {
                $this->info('✓ Document sent successfully!');
                $this->line('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
            } else {
                $this->error('✗ Failed to send document');
                $this->line('Error: ' . $result['message']);
                $this->line('HTTP Code: ' . ($result['http_code'] ?? 'N/A'));

                if (isset($result['data'])) {
                    $this->line('API Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
                }
            }

            // Cleanup
            if (file_exists($testPdfPath)) {
                unlink($testPdfPath);
            }
        }

        return 0;
    }

    private function createTestPdf()
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Test Document</h1><p>This is a test PDF for Wablas integration.</p>');

            $tempPath = storage_path('app/temp/test_wablas_' . time() . '.pdf');

            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            return $tempPath;
        } catch (\Exception $e) {
            $this->error('Failed to create test PDF: ' . $e->getMessage());
            return null;
        }
    }

    private function checkDeviceStatus()
    {
        $token = config('services.wablas.token');
        $baseUrl = config('services.wablas.base_url');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: {$token}",
        ]);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $baseUrl . "/device/status");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($result, true);
        }

        return null;
    }
}
