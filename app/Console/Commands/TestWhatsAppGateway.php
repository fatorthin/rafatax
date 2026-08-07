<?php

namespace App\Console\Commands;

use App\Services\WhatsAppGatewayService;
use Illuminate\Console\Command;

class TestWhatsAppGateway extends Command
{
    protected $signature = 'whatsapp-gateway:test {phone? : Nomor telepon penerima uji coba (misal: 08123456789)}';

    protected $description = 'Uji koneksi dan pengiriman pesan WhatsApp Gateway (go-whatsapp-web-multidevice v9.0.0)';

    public function handle(WhatsAppGatewayService $service): int
    {
        $this->info('==================================================');
        $this->info('  UJI KONEKSI WHATSAPP GATEWAY (V9.0.0)');
        $this->info('==================================================');

        $this->line('Base URL   : ' . $service->getBaseUrl());
        $this->line('Device ID  : ' . ($service->getDeviceId() ?: '(Default)'));
        $this->line('Status Enabled: ' . ($service->isEnabled() ? 'TRUE' : 'FALSE'));
        $this->newLine();

        $this->info('Memeriksa status perangkat gateway...');
        $status = $service->getStatus();

        if ($status['success']) {
            $this->info('STATUS: TERHUBUNG / ONLINE');
            $this->line('Pesan: ' . $status['message']);
            if (!empty($status['data'])) {
                $this->line('Data Perangkat: ' . json_encode($status['data'], JSON_PRETTY_PRINT));
            }
        } else {
            $this->error('STATUS: GAGAL TERHUBUNG / OFFLINE');
            $this->error('Pesan Error: ' . $status['message']);
        }

        $phone = $this->argument('phone');
        if ($phone) {
            $this->newLine();
            $this->info("Mengirim pesan uji coba ke {$phone}...");
            $res = $service->sendMessage($phone, "Hello! Ini adalah pesan uji coba dari Rafatax WhatsApp Gateway pada " . now()->format('Y-m-d H:i:s'));

            if ($res['success']) {
                $this->info('PESAN BERHASIL TERKIRIM!');
            } else {
                $this->error('GAGAL MENGIRIM PESAN: ' . $res['message']);
            }
        }

        return 0;
    }
}
