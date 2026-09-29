<?php

namespace App\Console\Commands;

use App\Services\WhatsAppGatewayService;
use Illuminate\Console\Command;

class TestWhatsAppGateway extends Command
{
    protected $signature = 'whatsapp-gateway:test 
                            {phone? : Nomor telepon penerima uji coba (misal: 08123456789)}
                            {--qr : Tampilkan link QR Code login jika perangkat belum terhubung}
                            {--pdf : Sertakan pengiriman dokumen PDF uji coba}';

    protected $description = 'Uji koneksi dan pengiriman pesan WhatsApp Gateway (go-whatsapp-web-multidevice)';

    public function handle(WhatsAppGatewayService $service): int
    {
        $this->info('==================================================');
        $this->info('  UJI KONEKSI WHATSAPP GATEWAY');
        $this->info('==================================================');

        $this->line('Base URL   : ' . $service->getBaseUrl());
        $this->line('Device ID  : ' . ($service->getDeviceId() ?: '(Default)'));
        $this->line('Status Enabled: ' . ($service->isEnabled() ? 'TRUE' : 'FALSE'));
        $this->newLine();

        $this->info('Memeriksa status perangkat gateway...');
        $status = $service->getStatus();

        if ($status['connected'] ?? false) {
            $this->info('STATUS: TERHUBUNG / ONLINE ✅');
            $this->line('Pesan: ' . $status['message']);
            if (!empty($status['data'])) {
                $this->line('Data Perangkat: ' . json_encode($status['data'], JSON_PRETTY_PRINT));
            }
        } else {
            $this->warn('STATUS: BELUM TERHUBUNG / OFFLINE ⚠️');
            $this->line('Pesan: ' . ($status['message'] ?? 'Perangkat belum tersambung'));

            if ($this->option('qr')) {
                $this->newLine();
                $this->info('Mengambil QR Code login...');
                $qr = $service->getLoginQr();
                if ($qr['success']) {
                    $this->info('Link QR Code: ' . ($qr['qr_link'] ?? '-'));
                    $this->line('Durasi Berlaku: ' . ($qr['qr_duration'] ?? 30) . ' detik');
                    $this->line('Silakan buka link di atas di browser Anda dan scan dengan WhatsApp.');
                } else {
                    $this->error('Gagal mengambil QR Code: ' . $qr['message']);
                }
            } else {
                $this->line('Tip: Jalankan `php artisan whatsapp-gateway:test --qr` atau buka menu Admin Pengaturan > WhatsApp Gateway untuk scan QR.');
            }
        }

        $phone = $this->argument('phone');
        if ($phone) {
            $this->newLine();
            $this->info("Mengirim pesan uji coba ke {$phone}...");
            $res = $service->sendMessage($phone, "Hello! Ini adalah pesan uji coba dari Rafatax WhatsApp Gateway pada " . now()->format('Y-m-d H:i:s'));

            if ($res['success']) {
                $this->info('PESAN BERHASIL TERKIRIM! ✅');
            } else {
                $this->error('GAGAL MENGIRIM PESAN: ' . ($res['message'] ?? 'Unknown error'));
            }

            if ($this->option('pdf')) {
                $this->newLine();
                $this->info("Menguji pengiriman PDF ke {$phone}...");
                $dummyPdf = storage_path('app/temp/test_wa.pdf');
                if (!file_exists(storage_path('app/temp'))) {
                    @mkdir(storage_path('app/temp'), 0755, true);
                }
                file_put_contents($dummyPdf, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000108 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n185\n%%EOF");

                $pdfRes = $service->sendDocument($phone, $dummyPdf, 'Uji Coba Pengiriman Dokumen PDF');
                @unlink($dummyPdf);

                if ($pdfRes['success'] ?? false) {
                    $this->info('DOKUMEN PDF BERHASIL TERKIRIM! ✅');
                } else {
                    $this->error('GAGAL MENGIRIM DOKUMEN: ' . ($pdfRes['message'] ?? 'Unknown error'));
                }
            }
        }

        return 0;
    }
}
