<?php

namespace App\Filament\App\Pages;

use App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang as BaseNeracaLajurPiutang;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;

class NeracaLajurPiutang extends BaseNeracaLajurPiutang
{
    protected static bool $isDiscovered = true;

    protected static ?string $title = 'Neraca Lajur (Konsep Piutang)';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Neraca Lajur (Piutang)';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 3;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('cash-manager') || $user->hasRole('admin');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return static::canAccess($parameters);
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();

        $routeName = 'pages.' . static::getRelativeRouteName();
        $routeName = static::prependClusterRouteBaseName($routeName);

        return $panel->generateRouteName($routeName);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        if (blank($panel) || Filament::getPanel($panel)->hasTenancy()) {
            $parameters['tenant'] ??= ($tenant ?? Filament::getTenant());
        }

        return route(static::getRouteName($panel), $parameters, $isAbsolute);
    }

    /**
     * @param  array<string, mixed>  $urlParameters
     */
    public static function getNavigationItems(array $urlParameters = []): array
    {
        return [
            NavigationItem::make('Neraca Lajur (PT)')
                ->group('Laporan Keuangan PT')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(static::getUrl(['entity' => 'pt']))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && strtolower(request('entity', 'all')) === 'pt')
                ->sort(1),
            NavigationItem::make('Neraca Lajur (KKP)')
                ->group('Laporan Keuangan KKP')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(static::getUrl(['entity' => 'kkp']))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && strtolower(request('entity', 'all')) === 'kkp')
                ->sort(1),
            NavigationItem::make('Neraca Lajur Konsolidasi')
                ->group('Laporan Konsolidasi')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(static::getUrl(['entity' => 'all']))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && strtolower(request('entity', 'all')) === 'all')
                ->sort(1),
        ];
    }

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName();
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => static::getTitle(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter Periode')
                ->icon('heroicon-o-funnel')
                ->form([
                    Select::make('period')
                        ->label('Periode')
                        ->options([
                            '2026-1'  => 'Januari 2026',
                            '2026-2'  => 'Februari 2026',
                            '2026-3'  => 'Maret 2026',
                            '2026-4'  => 'April 2026',
                            '2026-5'  => 'Mei 2026',
                            '2026-6'  => 'Juni 2026',
                            '2026-7'  => 'Juli 2026',
                            '2026-8'  => 'Agustus 2026',
                            '2026-9'  => 'September 2026',
                            '2026-10' => 'Oktober 2026',
                            '2026-11' => 'November 2026',
                            '2026-12' => 'Desember 2026',
                        ])
                        ->default($this->year . '-' . $this->month)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $parts = explode('-', $data['period']);
                    $this->year  = (int) $parts[0];
                    $this->month = (int) $parts[1];
                    $this->redirect(static::getUrl([
                        'month'  => $this->month,
                        'year'   => $this->year,
                        'entity' => $this->entity,
                    ]));
                }),
            Action::make('viewOld')
                ->label('Lihat Neraca Lajur (Lama)')
                ->icon('heroicon-o-arrow-left-circle')
                ->color('gray')
                ->url(function () {
                    if (Route::has('filament.app.pages.neraca-lajur-bulanan')) {
                        return route('filament.app.pages.neraca-lajur-bulanan', [
                            'month' => $this->month,
                            'year'  => $this->year,
                        ]);
                    }
                    return null;
                }),
            Action::make('cutOffNeraca')
                ->label(fn () => match ($this->entity) {
                    'pt'    => 'Cut Off Neraca (PT)',
                    'kkp'   => 'Cut Off Neraca (KKP)',
                    default => 'Cut Off Neraca (Konsolidasi)',
                })
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->modalHeading(function () {
                    $mNames = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ];
                    $mName = $mNames[$this->month] ?? $this->month;
                    $entLabel = match ($this->entity) {
                        'pt'    => 'PT',
                        'kkp'   => 'KKP',
                        default => 'Konsolidasi',
                    };
                    return "Konfirmasi Cut Off Neraca {$entLabel} - {$mName} {$this->year}";
                })
                ->modalDescription(function () {
                    $isSeparated = ($this->year > 2026) || ($this->year == 2026 && $this->month >= 4);
                    $status = \App\Services\NeracaLajurCutOffService::getCutOffStatus($this->year, $this->month, $this->entity);
                    $statusText = $status['is_cutoff']
                        ? "⚠️ PERHATIAN: Periode ini sudah pernah di-cut off ({$status['count']} akun). Melakukan cut off kembali akan memperbarui (overwrite) saldo tersebut."
                        : "ℹ️ Periode ini belum pernah di-cut off.";

                    if ($this->entity === 'pt') {
                        return "Tindakan ini akan mengunci dan menyimpan saldo akhir Neraca PT ke dalam Jurnal Neraca Bulanan (journal_book_id = 3, kepemilikan PT). Saldo ini otomatis menjadi Neraca Awal PT pada bulan berikutnya.\n\n{$statusText}";
                    } elseif ($this->entity === 'kkp') {
                        return "Tindakan ini akan mengunci dan menyimpan saldo akhir Neraca KKP ke dalam Jurnal Neraca Bulanan (journal_book_id = 3, kepemilikan KKP). Saldo ini otomatis menjadi Neraca Awal KKP pada bulan berikutnya.\n\n{$statusText}";
                    } elseif ($isSeparated) {
                        return "Periode ini berada dalam pemisahan entitas aktif (PT & KKP). Cut Off Neraca Konsolidasi akan mengunci saldo akhir untuk kedua entitas sehingga masing-masing entitas dan neraca konsolidasi memiliki saldo awal yang akurat pada bulan berikutnya.\n\n{$statusText}";
                    } else {
                        return "Tindakan ini akan mengunci dan menyimpan saldo akhir Neraca Konsolidasi ke dalam Jurnal Neraca Bulanan (journal_book_id = 3). Saldo ini otomatis menjadi Neraca Awal pada bulan berikutnya.\n\n{$statusText}";
                    }
                })
                ->form(function () {
                    $isSeparated = ($this->year > 2026) || ($this->year == 2026 && $this->month >= 4);
                    if ($this->entity === 'all' && $isSeparated) {
                        return [
                            Select::make('target_mode')
                                ->label('Pilihan Pemrosesan Cut Off')
                                ->options([
                                    'both' => 'Kedua Entitas (PT & KKP) - Konsolidasi Lengkap (Direkomendasikan)',
                                    'pt'   => 'Hanya Entitas PT',
                                    'kkp'  => 'Hanya Entitas KKP',
                                ])
                                ->default('both')
                                ->required()
                                ->helperText('Pilih entitas yang ingin di-cut off. Memproses kedua entitas sekaligus akan memastikan Neraca Konsolidasi dan Neraca masing-masing entitas pada bulan berikutnya sinkron.'),
                        ];
                    }
                    return [];
                })
                ->modalSubmitActionLabel('Ya, Jalankan Cut Off')
                ->action(function (array $data = []): void {
                    try {
                        $targetMode = $data['target_mode'] ?? null;
                        $result = \App\Services\NeracaLajurCutOffService::cutOff(
                            $this->year,
                            $this->month,
                            $this->entity,
                            $targetMode
                        );

                        Notification::make()
                            ->success()
                            ->title('Cut Off Neraca Berhasil!')
                            ->body($result['message'])
                            ->persistent()
                            ->send();

                        $this->redirect(static::getUrl([
                            'month'  => $this->month,
                            'year'   => $this->year,
                            'entity' => $this->entity,
                        ]));
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal Melakukan Cut Off Neraca')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('exportDetailJP')
                ->label('Export Detail Jurnal Pendapatan')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->url(fn() => url('/neraca-lajur-piutang/export-detail-jp?month=' . $this->month . '&year=' . $this->year . '&entity=' . $this->entity))
                ->openUrlInNewTab(false),
            Action::make('export')
                ->label('Export Neraca Lajur')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => url('/neraca-lajur-piutang/export?month=' . $this->month . '&year=' . $this->year . '&entity=' . $this->entity))
                ->openUrlInNewTab(false),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    if (Route::has('filament.app.resources.cash-reports.index')) {
                        return route('filament.app.resources.cash-reports.index');
                    }
                    return route('filament.app.pages.dashboard');
                }),
        ];
    }
}
