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

    public ?array $data = [];
    public array $gatewayStatus = [];

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Fitur ini hanya untuk admin di panel Admin
        return $user->hasRole('admin') || $user->hasPermission('manage_whatsapp_gateway');
    }

    public function mount(): void
    {
        $cfg = config('services.whatsapp_gateway', []);

        $this->form->fill([
            'enabled' => (bool)($cfg['enabled'] ?? true),
            'url' => (string)($cfg['url'] ?? 'https://wagateway.surakana.my.id'),
            'auth' => (string)($cfg['auth'] ?? 'admin:admin'),
            'device_id' => (string)($cfg['device_id'] ?? '8a744703-b90a-4690-b911-b1b8f2523963'),
            'verify_ssl' => (bool)($cfg['verify_ssl'] ?? true),
            'timeout' => (int)($cfg['timeout'] ?? 30),
        ]);

        $this->checkGatewayStatus();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi WhatsApp Gateway (go-whatsapp-web-multidevice v9.0.0)')
                    ->description('Pengaturan server WhatsApp Gateway terpisah (mini server) tanpa mengganggu fitur Wablas existing.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Aktifkan WhatsApp Gateway')
                            ->helperText('Jika dinonaktifkan, pengiriman via WhatsApp Gateway ini tidak akan diproses.')
                            ->default(true),

                        Forms\Components\TextInput::make('url')
                            ->label('URL Server WhatsApp Gateway')
                            ->placeholder('https://wagateway.surakana.my.id')
                            ->helperText('URL endpoint server go-whatsapp-web-multidevice Anda.')
                            ->url()
                            ->required(),

                        Forms\Components\TextInput::make('auth')
                            ->label('Kredensial Autentikasi (Basic Auth / Token)')
                            ->placeholder('admin:admin')
                            ->helperText('Format `username:password` untuk Basic Auth, atau Token tunggal.')
                            ->required(),

                        Forms\Components\TextInput::make('device_id')
                            ->label('Device ID (Opsional / Multi-Device)')
                            ->placeholder('8a744703-b90a-4690-b911-b1b8f2523963')
                            ->helperText('UUID / Device ID unik jika mengelola beberapa perangkat pada gateway.'),

                        Forms\Components\Toggle::make('verify_ssl')
                            ->label('Verifikasi SSL Certificate')
                            ->helperText('Matikan jika server menggunakan Self-Signed Certificate atau SSL lokal.')
                            ->default(true),

                        Forms\Components\TextInput::make('timeout')
                            ->label('HTTP Timeout (Detik)')
                            ->numeric()
                            ->default(30)
                            ->required(),
                    ])->columns(2)
            ])
            ->statePath('data');
    }

    public function checkGatewayStatus(): void
    {
        $service = app(WhatsAppGatewayService::class);
        $this->gatewayStatus = $service->getStatus();
    }

    public function saveSettings(): void
    {
        $state = $this->form->getState();

        $envUpdates = [
            'WHATSAPP_GATEWAY_ENABLED' => $state['enabled'] ? 'true' : 'false',
            'WHATSAPP_GATEWAY_URL' => $state['url'],
            'WHATSAPP_GATEWAY_AUTH' => $state['auth'],
            'WHATSAPP_DEVICE_ID' => $state['device_id'] ?? '',
            'WHATSAPP_GATEWAY_VERIFY_SSL' => $state['verify_ssl'] ? 'true' : 'false',
            'WHATSAPP_GATEWAY_TIMEOUT' => (string)($state['timeout'] ?? 30),
        ];

        $this->updateEnvFile($envUpdates);

        // Dynamic config update
        config([
            'services.whatsapp_gateway.enabled' => $state['enabled'],
            'services.whatsapp_gateway.url' => $state['url'],
            'services.whatsapp_gateway.auth' => $state['auth'],
            'services.whatsapp_gateway.device_id' => $state['device_id'] ?? '',
            'services.whatsapp_gateway.verify_ssl' => $state['verify_ssl'],
            'services.whatsapp_gateway.timeout' => $state['timeout'],
        ]);

        $this->checkGatewayStatus();

        Notification::make()
            ->title('Pengaturan WhatsApp Gateway berhasil disimpan!')
            ->success()
            ->send();
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
                    ->default('Halo! Ini adalah pesan uji coba dari WhatsApp Gateway (go-whatsapp-web-multidevice v9.0.0) sistem Rafatax.')
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
            ->label('Disconnect Device')
            ->color('danger')
            ->icon('heroicon-o-power')
            ->requiresConfirmation()
            ->modalHeading('Putuskan Koneksi WhatsApp?')
            ->modalDescription('Apakah Anda yakin ingin memutus sesi koneksi WhatsApp dari gateway?')
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

    private function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $val) {
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$val}", $content);
            } else {
                $content .= "\n{$key}={$val}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
