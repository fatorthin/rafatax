<?php

namespace App\Filament\Pages;

use App\Services\WhatsAppGatewayService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WhatsAppGatewaySettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'WhatsApp Gateway';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.whatsapp-gateway-settings';

    public array $gatewayStatus = [];
    public ?string $qrCodeBase64 = null;
    public ?string $qrCodeMessage = null;
    public bool $isShowingQr = false;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasPermission('manage_whatsapp_gateway');
    }

    public function mount(): void
    {
        $this->checkGatewayStatus();
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function checkGatewayStatus(): void
    {
        $service = app(WhatsAppGatewayService::class);
        $this->gatewayStatus = $service->getStatus();
    }

    public function fetchQrCode(): void
    {
        $service = app(WhatsAppGatewayService::class);
        $res = $service->getLoginQr();

        if ($res['success'] && !empty($res['qr_base64'])) {
            $this->qrCodeBase64 = $res['qr_base64'];
            $this->qrCodeMessage = 'Silakan scan QR Code berikut dengan WhatsApp di ponsel Anda (Berlaku ~' . ($res['qr_duration'] ?? 30) . ' detik).';
            $this->isShowingQr = true;

            Notification::make()
                ->title('QR Code berhasil dimuat')
                ->body('Arahkan kamera WhatsApp Linked Devices ke QR Code')
                ->success()
                ->send();
        } else {
            $this->isShowingQr = false;
            $msg = $res['message'] ?? 'Gagal memuat QR Code login dari server gateway.';

            Notification::make()
                ->title('Gagal Mengambil QR Code')
                ->body($msg)
                ->danger()
                ->send();
        }
    }

    public function hideQrCode(): void
    {
        $this->isShowingQr = false;
        $this->qrCodeBase64 = null;
    }

    public function reconnectAction(): void
    {
        $service = app(WhatsAppGatewayService::class);
        $res = $service->reconnectDevice();

        if ($res['success']) {
            Notification::make()
                ->title('Perangkat sedang menghubungkan ulang')
                ->body($res['message'])
                ->info()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Reconnect')
                ->body($res['message'])
                ->warning()
                ->send();
        }

        $this->checkGatewayStatus();
    }



    public function testSendAction(): Action
    {
        return Action::make('testSendAction')
            ->label('Uji Kirim Pesan WA')
            ->color('info')
            ->icon('heroicon-o-paper-airplane')
            ->form([
                Forms\Components\TextInput::make('phone')
                    ->label('Nomor WhatsApp Tujuan')
                    ->placeholder('08123456789 atau 628123456789')
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->label('Pesan Uji Coba')
                    ->default('Halo! Ini adalah pesan uji coba dari server WhatsApp Gateway Rafatax pada ' . now()->format('d-m-Y H:i:s'))
                    ->required(),
            ])
            ->action(function (array $data) {
                $service = app(WhatsAppGatewayService::class);
                $res = $service->sendMessage($data['phone'], $data['message']);

                if ($res['success']) {
                    Notification::make()
                        ->title('Pesan Uji Coba Berhasil Terkirim!')
                        ->body('Terkirim ke ' . $data['phone'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Gagal Mengirim Pesan')
                        ->body($res['message'] ?? 'Terjadi kesalahan saat menghubungkan server gateway.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function logoutDeviceAction(): Action
    {
        return Action::make('logoutDeviceAction')
            ->label('Disconnect / Logout')
            ->color('danger')
            ->icon('heroicon-o-power')
            ->requiresConfirmation()
            ->modalHeading('Putuskan Koneksi WhatsApp?')
            ->modalDescription('Apakah Anda yakin ingin memutus sesi koneksi WhatsApp dari gateway? Anda harus scan QR lagi setelah ini.')
            ->action(function () {
                $service = app(WhatsAppGatewayService::class);
                $res = $service->logoutDevice();

                if ($res['success']) {
                    Notification::make()->title('Perangkat berhasil logout')->success()->send();
                } else {
                    Notification::make()->title('Gagal Logout')->body($res['message'])->danger()->send();
                }

                $this->checkGatewayStatus();
            });
    }


}
