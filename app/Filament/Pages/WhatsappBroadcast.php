<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Staff;
use App\Services\WablasService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WhatsappBroadcast extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'WhatsApp Broadcast';
    protected static ?string $navigationGroup = 'Bagian HRD';
    protected static string $view = 'filament.pages.whatsapp-broadcast';

    public ?array $data = [];

    public function sendAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('send')
            ->label('Kirim Broadcast')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Kirim Broadcast')
            ->modalDescription('Apakah Anda yakin ingin mengirim pesan broadcast ini? Pastikan pesan dan tujuan sudah benar.')
            ->modalSubmitActionLabel('Ya, Kirim')
            ->action(fn() => $this->kirimBroadcast());
    }

    public function mount(): void
    {
        $this->form->fill([
            'target_type' => 'client',
            'scope' => 'selected',
            'recipients' => [],
            'message' => '',
            'attachment' => null,
            'test_mode' => false,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Broadcast')
                    ->schema([
                        Forms\Components\Radio::make('target_type')
                            ->label('Target')
                            ->options([
                                'client' => 'Client',
                                'staff' => 'Staff',
                            ])
                            ->inline()
                            ->live()
                            ->required(),

                        Forms\Components\Radio::make('scope')
                            ->label('Cakupan')
                            ->options([
                                'selected' => 'Pilih sebagian',
                            ])
                            ->default('selected')
                            ->inline()
                            ->live()
                            ->required(),

                        Forms\Components\MultiSelect::make('recipients')
                            ->label('Penerima (jika memilih sebagian)')
                            ->options(function (callable $get) {
                                $target = $get('target_type') ?? 'client';

                                if ($target === 'staff') {
                                    return Staff::query()
                                        ->whereNotNull('phone')
                                        ->where('phone', '!=', '')
                                        ->where('is_active', '1')
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }

                                return Client::query()
                                    ->whereNotNull('phone')
                                    ->where('phone', '!=', '')
                                    ->orderBy('company_name')
                                    ->pluck('company_name', 'id');
                            })
                            ->visible(fn(callable $get) => $get('scope') === 'selected')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->placeholder('Pilih penerima...'),

                        Forms\Components\Textarea::make('message')
                            ->label('Pesan')
                            ->rows(6)
                            ->helperText('Gunakan teks biasa. Emoji didukung. Pastikan nomor valid.')
                            ->required(),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Lampiran (Gambar/PDF)')
                            ->helperText('Format: JPG, PNG, PDF. Maks 5MB.')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->directory('whatsapp-broadcasts')
                            ->visibility('public'),

                        Forms\Components\Toggle::make('test_mode')
                            ->label('Mode uji (batasi 3 penerima)')
                            ->default(false),
                    ])
            ])
            ->statePath('data');
    }

    public function kirimBroadcast(): void
    {
        $state = $this->form->getState();
        $target = $state['target_type'] ?? 'client';
        $scope = $state['scope'] ?? 'all';
        $ids = $state['recipients'] ?? [];
        $message = trim((string)($state['message'] ?? ''));
        $attachment = $state['attachment'] ?? null;
        $testMode = (bool)($state['test_mode'] ?? false);

        if ($message === '' && empty($attachment)) {
            Notification::make()->title('Pesan atau lampiran wajib diisi')->danger()->send();
            return;
        }

        if ($target === 'staff') {
            $query = Staff::query()->whereNotNull('phone')->where('phone', '!=', '');
            if ($scope === 'selected' && !empty($ids)) {
                $query->whereIn('id', $ids);
            }
            $list = $query->select(['id', 'name', 'phone'])->orderBy('name')->get()
                ->map(fn($s) => [
                    'name' => $s->name,
                    'phone' => $this->normalizePhone($s->phone),
                ])
                ->filter(fn($r) => $r['phone'] !== null)
                ->values();
        } else {
            $query = Client::query()->whereNotNull('phone')->where('phone', '!=', '');
            if ($scope === 'selected' && !empty($ids)) {
                $query->whereIn('id', $ids);
            }
            $list = $query->select(['id', 'company_name', 'phone'])->orderBy('company_name')->get()
                ->map(fn($c) => [
                    'name' => $c->company_name,
                    'phone' => $this->normalizePhone($c->phone),
                ])
                ->filter(fn($r) => $r['phone'] !== null)
                ->values();
        }

        if ($list->isEmpty()) {
            Notification::make()->title('Tidak ada nomor valid untuk dikirim')->warning()->send();
            return;
        }

        if ($testMode) {
            $list = $list->take(3);
        }

        $svc = app(WablasService::class);

        $total = $list->count();
        $ok = 0;
        $fail = 0;
        $failedRows = [];

        $attachmentPath = null;
        $attachmentType = null;

        if ($attachment) {
            $attachmentPath = \Illuminate\Support\Facades\Storage::disk('public')->path($attachment);
            $mime = mime_content_type($attachmentPath);
            $attachmentType = str_starts_with($mime, 'image/') ? 'image' : 'document';
        }

        $firstErrorDetail = null;
        foreach ($list as $row) {
            if ($attachmentPath) {
                if ($attachmentType === 'image') {
                    $res = $svc->sendImage($row['phone'], $attachmentPath, $message);
                } else {
                    $res = $svc->sendDocument($row['phone'], $attachmentPath, $message);
                }
            } else {
                $res = $svc->sendMessage($row['phone'], $message);
            }

            if (($res['success'] ?? false) === true || ($res['status'] ?? false) === true) {
                $ok++;
            } else {
                $fail++;
                if ($firstErrorDetail === null) {
                    $firstErrorDetail = [
                        'http_code' => $res['http_code'] ?? null,
                        'message' => $res['message'] ?? null,
                        'data' => $res['data'] ?? null,
                    ];
                }
                $failedRows[] = $row['name'] . ' (' . $row['phone'] . ')';
            }
            usleep(500000); // 0.5s delay to be safe
        }

        if ($fail === 0) {
            Notification::make()
                ->title('Broadcast terkirim: ' . $ok . '/' . $total)
                ->success()
                ->send();
        } else {
            $detail = '';
            if ($firstErrorDetail) {
                $detail = ' | Kode: ' . ($firstErrorDetail['http_code'] ?? '-') . ' | Pesan: ' . ($firstErrorDetail['message'] ?? '-');
                if (!empty($firstErrorDetail['data']) && is_array($firstErrorDetail['data'])) {
                    $err = $firstErrorDetail['data']['message'] ?? $firstErrorDetail['data']['error'] ?? null;
                    if ($err) {
                        $detail .= ' | Detail: ' . (is_string($err) ? $err : json_encode($err));
                    }
                }
            }

            Notification::make()
                ->title('Sebagian gagal: ' . $ok . '/' . $total . ' terkirim')
                ->body('Gagal: ' . implode(', ', array_slice($failedRows, 0, 5)) . (count($failedRows) > 5 ? '...' : '') . $detail)
                ->danger()
                ->send();
        }
    }

    private function normalizePhone(?string $raw): ?string
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
