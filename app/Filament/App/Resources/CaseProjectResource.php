<?php

namespace App\Filament\App\Resources;

use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use App\Models\CaseProjectPowerOfAttorney;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CaseProject;
use App\Traits\HasPermissions;
use App\Services\WablasService;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\CaseProjectResource\Pages;

class CaseProjectResource extends Resource
{
    use HasPermissions;

    protected static ?string $model = CaseProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Daftar Projek Kasus';

    protected static ?string $navigationGroup = 'HRD';

    public static function form(Form $form): Form
    {
        // $hideForHrd = function () {
        //     /** @var \App\Models\User|null $user */
        //     $user = \Illuminate\Support\Facades\Auth::user();
        //     return $user?->hasRole('hrd-manager') ?? false;
        // };

        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->company_name}")
                    ->searchable(['company_name', 'code'])
                    ->label('Nama Client')
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('case_type')
                    ->options([
                        'SPT' => 'SPT',
                        'SP2DK' => 'SP2DK',
                        'Pembetulan' => 'PEMBETULAN',
                        'Pemeriksaan' => 'PEMERIKSAAN',
                        'Himbauan' => 'HIMBAUAN',
                        'Lainnya' => 'LAINNYA',
                    ])
                    ->label('Kategori')
                    ->required(),
                Forms\Components\Select::make('staff_id')
                    ->options(function (?CaseProject $record) {
                        return Staff::query()
                            ->where('is_active', true)
                            ->when($record, fn($query) => $query->orWhereIn('id', $record->staff_id ?? []))
                            ->pluck('name', 'id');
                    })
                    ->multiple()
                    ->label('Tim Case Project')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Section::make('Detail Tim')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship('details')
                            ->label('Nominal Bonus Staff')
                            ->schema([
                                Forms\Components\Select::make('staff_id')
                                    ->label('Staff')
                                    ->options(function (\Filament\Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                        return Staff::query()
                                            ->where('is_active', true)
                                            ->when($record, fn($query) => $query->orWhere('id', $record->staff_id))
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('bonus')
                                    ->label('Nominal Bonus')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Bonus Staff')
                            ->defaultItems(0)
                            ->helperText('Tambahkan satu baris untuk setiap staff beserta nominal bonusnya.'),
                    ])
                    ->columnSpanFull(),
                Forms\Components\Select::make('mou_id')
                    ->label('No MoU')
                    ->options(MoU::with('client')->get()->mapWithKeys(fn($record) => [
                        $record->id => "{$record->mou_number} - {$record->client?->company_name} - {$record->description}"
                    ]))
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('case_letter_number')
                    ->label('No Surat Kasus'),
                Forms\Components\DatePicker::make('case_letter_date')
                    ->label('Tanggal Surat Kasus')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\TextInput::make('power_of_attorney_number')
                    ->label('No Surat Kuasa'),
                Forms\Components\DatePicker::make('power_of_attorney_date')
                    ->label('Tanggal Surat Kuasa')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('filling_drive')
                    ->label('Drive Pengisian')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\Textarea::make('link_drive')
                    ->label('Link Drive'),
                Forms\Components\DatePicker::make('report_date')
                    ->label('Tanggal Laporan')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('share_client_date')
                    ->label('Tanggal Berikan Client')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'case_done' => 'CASE DONE',
                        'bonus_done' => 'BONUS DONE',
                    ])
                    ->default(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('hrd-manager') ? 'case_done' : null;
                    })
                    ->afterStateHydrated(function (Forms\Components\Select $component, $state) {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user?->hasRole('hrd-manager')) {
                            $component->state('case_done');
                        }
                    })
                    ->mutateDehydratedStateUsing(function ($state) {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('hrd-manager') ? 'case_done' : $state;
                    })
                    ->disabled(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('hrd-manager') ?? false;
                    })
                    ->dehydrated()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $hideForHrd = function () {
            /** @var \App\Models\User|null $user */
            $user = \Illuminate\Support\Facades\Auth::user();
            return $user?->hasRole('hrd-manager') ?? false;
        };

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('index')->label('No')->rowIndex(),


                Tables\Columns\TextColumn::make('description')->sortable()->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->badge()->formatStateUsing(function ($state) {
                    return match ($state) {
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'case_done' => 'CASE DONE',
                        'bonus_done' => 'BONUS DONE',
                        'paid' => 'PAID',
                        default => $state,
                    };
                })->color(function ($state) {
                    return match ($state) {
                        'open' => 'primary',
                        'in_progress' => 'warning',
                        'case_done' => 'success',
                        'bonus_done' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    };
                }),


                Tables\Columns\TextColumn::make('client.code')->label('Kode Client')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Nama Client')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('case_type')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('staff_id')
                    ->label('Nama Pelaksana')
                    ->formatStateUsing(function ($state, CaseProject $record) {
                        $staffIds = $record->staff_id ?? [];
                        return Staff::whereIn('id', $staffIds)->pluck('name')->join(', ');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('bonus_staff')
                    ->label('Staff Penerima Bonus')
                    ->getStateUsing(function (CaseProject $record) {
                        return $record->details
                            ->map(function ($detail) {
                                $staffName = $detail->staff->name ?? '-';
                                $bonus = number_format((float) ($detail->bonus ?? 0), 0, ',', '.');

                                return $staffName . ' (Rp ' . $bonus . ')';
                            })
                            ->implode(', ');
                    })
                    ->wrap()
                    ->hidden(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('inventory-admin') ?? false;
                    }),

                Tables\Columns\TextColumn::make('total_bonus')
                    ->label('Total Bonus')
                    ->getStateUsing(fn(CaseProject $record) => $record->details->sum('bonus'))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->withSum('details', 'bonus')
                            ->orderBy('details_sum_bonus', $direction);
                    })
                    ->hidden(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('inventory-admin') ?? false;
                    }),
                Tables\Columns\TextColumn::make('mou.mou_number')->label('No MoU')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('case_letter_number')->label('No Surat Kasus')->sortable(),
                Tables\Columns\TextColumn::make('case_letter_date')->label('Tanggal Surat Kasus')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('power_of_attorney_number')->label('No Surat Kuasa')->sortable(),
                Tables\Columns\TextColumn::make('power_of_attorney_date')->label('Tanggal Surat Kuasa')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('filling_drive')->label('Drive Pengisian')->sortable(),
                Tables\Columns\TextColumn::make('link_drive')
                    ->label('Link Drive')
                    ->formatStateUsing(fn($state) => $state ? 'Buka Link' : '-')
                    ->url(fn($record) => $record->link_drive, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->icon('heroicon-o-link')
                    ->iconPosition('before'),
                Tables\Columns\TextColumn::make('report_date')->label('Tanggal Laporan')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('share_client_date')->label('Tanggal Berikan Client')->date('d-m-Y')->sortable(),
                // Tables\Columns\TextColumn::make('case_date')->label('Tanggal Kasus')->date('d-m-Y')->sortable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'OPEN',
                        'in_progress' => 'IN PROGRESS',
                        'case_done' => 'CASE DONE',
                        'bonus_done' => 'BONUS DONE',
                        'paid' => 'PAID',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('case_type')
                    ->options([
                        'SPT' => 'SPT',
                        'SP2DK' => 'SP2DK',
                        'Pembetulan' => 'PEMBETULAN',
                        'Pemeriksaan' => 'PEMERIKSAAN',
                        'Himbauan' => 'HIMBAUAN',
                        'Lainnya' => 'LAINNYA',
                    ])
                    ->label('Jenis Kasus'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendNotification')
                    ->label('Kirim Notifikasi')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Notifikasi Invoice & Payroll')
                    ->modalDescription('Apakah Anda yakin ingin mengirim notifikasi ini via WhatsApp?')
                    ->action(function (CaseProject $record, WablasService $wablasService) {
                        $phone = '6281359976015';

                        $staffNames = Staff::whereIn('id', $record->staff_id ?? [])->pluck('name')->join(', ');
                        $clientName = $record->client->company_name ?? '-';
                        $caseDate = $record->case_date ? \Carbon\Carbon::parse($record->case_date)->format('d-m-Y') : '-';

                        $message = "🔔 *NOTIFIKASI INVOICE & PAYROLL*\n\n";
                        $message .= "Case Project berikut memerlukan pembuatan invoice dan sudah dapat diproses payroll bonusnya:\n\n";
                        $message .= "📝 *Deskripsi*: {$record->description}\n";
                        $message .= "🏢 *Client*: {$clientName}\n";
                        $message .= "📁 *Kategori*: {$record->case_type}\n";
                        $message .= "👨‍💻 *Staff*: {$staffNames}\n";
                        $message .= "📅 *Tanggal*: {$caseDate}\n\n";
                        $message .= "Mohon segera diproses. Terima kasih.";

                        $result = $wablasService->sendMessage($phone, $message);

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil')
                                ->body('Notifikasi berhasil dikirim via WhatsApp')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body('Gagal mengirim notifikasi: ' . ($result['message'] ?? 'Unknown error'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->hidden(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('inventory-admin') ?? false;
                    }),
                Tables\Actions\Action::make('detail')
                    ->label('Detail Tim')
                    ->url(fn($record) => static::getUrl('detail', ['record' => $record]))
                    ->icon('heroicon-o-information-circle')
                    ->hidden(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user?->hasRole('inventory-admin') ?? false;
                    }),
                Tables\Actions\Action::make('editLinkDrive')
                    ->label('Edit Link Drive')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('link_drive')
                            ->label('Link Drive')
                            ->default(fn(CaseProject $record) => $record->link_drive),
                    ])
                    ->action(function (CaseProject $record, array $data) {
                        $record->update(['link_drive' => $data['link_drive']]);
                    }),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('dokumenKuasa')
                        ->label('Buat / Edit Dokumen Kuasa')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->modalHeading('Surat Kuasa Khusus & Surat Pernyataan (PMK 44)')
                        ->modalDescription('Lengkapi dan sesuaikan data surat kuasa khusus dan surat pernyataan. Format PMK 44 akan di-export menjadi 1 file PDF gabungan.')
                        ->modalWidth('5xl')
                        ->modalSubmitActionLabel('Simpan & Unduh PDF')
                        ->fillForm(function (CaseProject $record): array {
                            $poa = $record->powerOfAttorney;
                            if ($poa) {
                                return [
                                    'nomor_surat_kuasa' => $poa->nomor_surat_kuasa,
                                    'tanggal_surat_kuasa' => $poa->tanggal_surat_kuasa?->format('Y-m-d'),
                                    'jenis_wp' => $poa->jenis_wp,
                                    'pemberi_nama' => $poa->pemberi_nama,
                                    'pemberi_npwp' => $poa->pemberi_npwp,
                                    'pemberi_jabatan' => $poa->pemberi_jabatan,
                                    'bertindak_selaku' => $poa->bertindak_selaku,
                                    'wp_badan_nama' => $poa->wp_badan_nama,
                                    'wp_badan_npwp' => $poa->wp_badan_npwp,
                                    'kuasa_kategori' => $poa->kuasa_kategori,
                                    'penerima_nama' => $poa->penerima_nama,
                                    'penerima_npwp' => $poa->penerima_npwp,
                                    'penerima_izin_no' => $poa->penerima_izin_no,
                                    'penerima_status_keluarga' => $poa->penerima_status_keluarga,
                                    'kewajiban_terkait' => $poa->kewajiban_terkait,
                                    'jenis_pajak' => $poa->jenis_pajak,
                                    'masa_pajak_pilihan' => $poa->masa_pajak_pilihan,
                                    'masa_tahun_pajak' => $poa->masa_tahun_pajak,
                                    'tanggal_mulai' => $poa->tanggal_mulai?->format('Y-m-d'),
                                    'tanggal_selesai' => $poa->tanggal_selesai?->format('Y-m-d'),
                                    'rincian_kuasa' => $poa->rincian_kuasa ?? CaseProjectPowerOfAttorney::getDefaultRincianKuasa(),
                                    'pernyataan_nomor' => $poa->pernyataan_nomor,
                                    'pernyataan_tanggal' => $poa->pernyataan_tanggal?->format('Y-m-d'),
                                    'pernyataan_pemberi_alamat' => $poa->pernyataan_pemberi_alamat,
                                    'pernyataan_penerima_alamat' => $poa->pernyataan_penerima_alamat,
                                    'pernyataan_status_hubungan' => $poa->pernyataan_status_hubungan,
                                ];
                            }

                            $client = $record->client;
                            $defaultHak = match ($record->case_type) {
                                'SP2DK' => 'Penanganan Permintaan Penjelasan atas Data dan/atau Keterangan (SP2DK)',
                                'Pemeriksaan' => 'Pemeriksaan Pajak',
                                'Pembetulan' => 'Pembetulan SPT',
                                'SPT' => 'Penyampaian dan Pelaporan SPT',
                                default => ($record->case_type ?? 'Pemeriksaan Perpajakan'),
                            };

                            return [
                                'nomor_surat_kuasa' => $record->power_of_attorney_number ?? '',
                                'tanggal_surat_kuasa' => $record->power_of_attorney_date ? \Carbon\Carbon::parse($record->power_of_attorney_date)->format('Y-m-d') : now()->format('Y-m-d'),
                                'jenis_wp' => ($client?->jenis_wp === 'badan') ? 'BADAN' : 'ORANG PRIBADI',
                                'pemberi_nama' => $client?->owner_name ?? ($client?->company_name ?? ''),
                                'pemberi_npwp' => $client?->npwp ?? '',
                                'pemberi_jabatan' => $client?->owner_role ?? ($client?->jenis_wp === 'badan' ? 'Direktur' : '-'),
                                'bertindak_selaku' => ($client?->jenis_wp === 'badan') ? 'wakil_wajib_pajak' : 'wajib_pajak',
                                'wp_badan_nama' => $client?->company_name ?? '',
                                'wp_badan_npwp' => $client?->npwp ?? '',
                                'kuasa_kategori' => 'konsultan_pajak',
                                'penerima_nama' => 'ANTIN OKFITASARI, S.E., S.H., M.Si., Ak., CA.AB., BKP., CATr., ACPA',
                                'penerima_npwp' => '',
                                'penerima_izin_no' => '',
                                'penerima_status_keluarga' => '-',
                                'kewajiban_terkait' => $defaultHak,
                                'jenis_pajak' => 'Pajak Penghasilan Badan dan Pajak Pertambahan Nilai',
                                'masa_pajak_pilihan' => 'Tahun Pajak',
                                'masa_tahun_pajak' => (string) ($record->mou?->tahun_pajak ?? now()->year),
                                'tanggal_mulai' => now()->format('Y-m-d'),
                                'tanggal_selesai' => now()->addMonths(6)->format('Y-m-d'),
                                'rincian_kuasa' => CaseProjectPowerOfAttorney::getDefaultRincianKuasa(),
                                'pernyataan_nomor' => $record->power_of_attorney_number ?? '',
                                'pernyataan_tanggal' => $record->power_of_attorney_date ? \Carbon\Carbon::parse($record->power_of_attorney_date)->format('Y-m-d') : now()->format('Y-m-d'),
                                'pernyataan_pemberi_alamat' => $client?->address ?? '',
                                'pernyataan_penerima_alamat' => 'Dk. Nampan RT 02 RW 01, Madegondo, Grogol, Sukoharjo',
                                'pernyataan_status_hubungan' => 'Konsultan Pajak yang memenuhi persyaratan sesuai ketentuan peraturan perundang-undangan perpajakan',
                            ];
                        })
                        ->form([
                            Forms\Components\Tabs::make('DokumenKuasaTabs')
                                ->tabs([
                                    Forms\Components\Tabs\Tab::make('Surat Kuasa Khusus')
                                        ->icon('heroicon-o-document-text')
                                        ->schema([
                                            Forms\Components\Section::make('Identitas Surat & Wajib Pajak Pemberi Kuasa')
                                                ->schema([
                                                    Forms\Components\Grid::make(3)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('nomor_surat_kuasa')
                                                                ->label('Nomor Surat Kuasa')
                                                                ->required(),
                                                            Forms\Components\DatePicker::make('tanggal_surat_kuasa')
                                                                ->label('Tanggal Surat Kuasa')
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y')
                                                                ->required(),
                                                            Forms\Components\Select::make('jenis_wp')
                                                                ->label('Wajib Pajak (Jenis)')
                                                                ->options([
                                                                    'BADAN' => 'BADAN',
                                                                    'ORANG PRIBADI' => 'ORANG PRIBADI',
                                                                    'WARISAN YANG BELUM TERBAGI' => 'WARISAN YANG BELUM TERBAGI',
                                                                ])
                                                                ->required(),
                                                        ]),
                                                    Forms\Components\Grid::make(3)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('pemberi_nama')
                                                                ->label('Nama Penandatangan (Pemberi Kuasa)')
                                                                ->required(),
                                                            Forms\Components\TextInput::make('pemberi_npwp')
                                                                ->label('NPWP Penandatangan')
                                                                ->required(),
                                                            Forms\Components\TextInput::make('pemberi_jabatan')
                                                                ->label('Jabatan Penandatangan')
                                                                ->placeholder('Contoh: Direktur'),
                                                        ]),
                                                    Forms\Components\Radio::make('bertindak_selaku')
                                                        ->label('Bertindak Selaku')
                                                        ->options([
                                                            'wajib_pajak' => 'Wajib Pajak (Orang Pribadi)',
                                                            'wakil_wajib_pajak' => 'Wakil Wajib Pajak (Badan/Warisan)',
                                                        ])
                                                        ->inline()
                                                        ->live()
                                                        ->required(),
                                                    Forms\Components\Grid::make(2)
                                                        ->visible(fn(Forms\Get $get) => $get('bertindak_selaku') === 'wakil_wajib_pajak')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('wp_badan_nama')
                                                                ->label('Nama Wajib Pajak Badan')
                                                                ->required(fn(Forms\Get $get) => $get('bertindak_selaku') === 'wakil_wajib_pajak'),
                                                            Forms\Components\TextInput::make('wp_badan_npwp')
                                                                ->label('NPWP Wajib Pajak Badan')
                                                                ->required(fn(Forms\Get $get) => $get('bertindak_selaku') === 'wakil_wajib_pajak'),
                                                        ]),
                                                ]),
                                            Forms\Components\Section::make('Penerima Kuasa')
                                                ->schema([
                                                    Forms\Components\Radio::make('kuasa_kategori')
                                                        ->label('Kategori Penerima Kuasa')
                                                        ->options([
                                                            'konsultan_pajak' => 'Konsultan Pajak',
                                                            'pihak_lain' => 'Pihak Lain',
                                                            'keluarga' => 'Keluarga Wajib Pajak',
                                                        ])
                                                        ->inline()
                                                        ->live()
                                                        ->required(),
                                                    Forms\Components\Grid::make(2)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('penerima_nama')
                                                                ->label('Nama Penerima Kuasa')
                                                                ->required(),
                                                            Forms\Components\TextInput::make('penerima_npwp')
                                                                ->label('NPWP Penerima Kuasa'),
                                                        ]),
                                                    Forms\Components\Grid::make(2)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('penerima_izin_no')
                                                                ->label('No. Izin Konsultan Pajak / SKT')
                                                                ->visible(fn(Forms\Get $get) => in_array($get('kuasa_kategori'), ['konsultan_pajak', 'pihak_lain'])),
                                                            Forms\Components\TextInput::make('penerima_status_keluarga')
                                                                ->label('Status Hubungan Keluarga')
                                                                ->visible(fn(Forms\Get $get) => $get('kuasa_kategori') === 'keluarga'),
                                                        ]),
                                                ]),
                                            Forms\Components\Section::make('Kewenangan & Objek Perpajakan yang Dikuasakan')
                                                ->schema([
                                                    Forms\Components\Grid::make(2)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('kewajiban_terkait')
                                                                ->label('Terkait Hak / Kewajiban Perpajakan')
                                                                ->placeholder('Contoh: Pemeriksaan Pajak, Penagihan Pajak, SP2DK')
                                                                ->required(),
                                                            Forms\Components\TextInput::make('jenis_pajak')
                                                                ->label('Jenis Pajak')
                                                                ->placeholder('Contoh: Pajak Penghasilan Badan, Pajak Pertambahan Nilai')
                                                                ->required(),
                                                        ]),
                                                    Forms\Components\Grid::make(4)
                                                        ->schema([
                                                            Forms\Components\Select::make('masa_pajak_pilihan')
                                                                ->label('Klausul Waktu')
                                                                ->options([
                                                                    'Masa Pajak' => 'Masa Pajak',
                                                                    'Bagian Tahun Pajak' => 'Bagian Tahun Pajak',
                                                                    'Tahun Pajak' => 'Tahun Pajak',
                                                                ])
                                                                ->required(),
                                                            Forms\Components\TextInput::make('masa_tahun_pajak')
                                                                ->label('Masa / Tahun Pajak')
                                                                ->placeholder('Contoh: 2025 atau Juli s.d. Desember 2025')
                                                                ->required(),
                                                            Forms\Components\DatePicker::make('tanggal_mulai')
                                                                ->label('Berlaku Mulai')
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y')
                                                                ->required(),
                                                            Forms\Components\DatePicker::make('tanggal_selesai')
                                                                ->label('Sampai Dengan')
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y')
                                                                ->required(),
                                                        ]),
                                                    Forms\Components\Repeater::make('rincian_kuasa')
                                                        ->label('Rincian Hal yang Dikuasakan (Butir a, b, c, dst.)')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('text')
                                                                ->label('Butir Kewenangan')
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->addActionLabel('Tambah Butir Kewenangan')
                                                        ->defaultItems(4),
                                                ]),
                                        ]),
                                    Forms\Components\Tabs\Tab::make('Surat Pernyataan')
                                        ->icon('heroicon-o-document-check')
                                        ->schema([
                                            Forms\Components\Section::make('Identitas Surat Pernyataan')
                                                ->schema([
                                                    Forms\Components\Grid::make(2)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('pernyataan_nomor')
                                                                ->label('Nomor Surat Pernyataan')
                                                                ->helperText('Bisa disamakan dengan nomor surat kuasa'),
                                                            Forms\Components\DatePicker::make('pernyataan_tanggal')
                                                                ->label('Tanggal Surat Pernyataan')
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y'),
                                                        ]),
                                                ]),
                                            Forms\Components\Section::make('Alamat & Keterangan Status Hubungan')
                                                ->schema([
                                                    Forms\Components\Textarea::make('pernyataan_pemberi_alamat')
                                                        ->label('Alamat Pemberi Kuasa')
                                                        ->rows(2)
                                                        ->required(),
                                                    Forms\Components\Textarea::make('pernyataan_penerima_alamat')
                                                        ->label('Alamat Penerima Kuasa')
                                                        ->rows(2)
                                                        ->required(),
                                                    Forms\Components\TextInput::make('pernyataan_status_hubungan')
                                                        ->label('Pernyataan Status Hubungan (merupakan ...)')
                                                        ->helperText('Contoh: Konsultan Pajak yang memenuhi persyaratan sesuai ketentuan peraturan perundang-undangan perpajakan')
                                                        ->required(),
                                                ]),
                                        ]),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->extraModalFooterActions(fn(Tables\Actions\Action $action, CaseProject $record): array => [
                            $action->makeModalSubmitAction('saveOnly', ['save_only' => true])
                                ->label('Simpan Saja')
                                ->color('gray'),
                            Tables\Actions\Action::make('previewPdfModal')
                                ->label('Lihat Preview PDF')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->color('info')
                                ->url(fn() => route('case-projects.dokumen-kuasa.preview', ['id' => $record->id]), shouldOpenInNewTab: true),
                        ])
                        ->action(function (CaseProject $record, array $data, array $arguments) {
                            $record->powerOfAttorney()->updateOrCreate(
                                ['case_project_id' => $record->id],
                                $data
                            );

                            $record->update([
                                'power_of_attorney_number' => $data['nomor_surat_kuasa'] ?? $record->power_of_attorney_number,
                                'power_of_attorney_date' => $data['tanggal_surat_kuasa'] ?? $record->power_of_attorney_date,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Dokumen Kuasa Berhasil Disimpan')
                                ->success()
                                ->send();

                            if (!($arguments['save_only'] ?? false)) {
                                return redirect()->route('case-projects.dokumen-kuasa.download', ['id' => $record->id]);
                            }
                        }),

                    Tables\Actions\Action::make('previewDokumenKuasa')
                        ->label('Lihat Preview PDF')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn(CaseProject $record) => route('case-projects.dokumen-kuasa.preview', ['id' => $record->id]), shouldOpenInNewTab: true),

                    Tables\Actions\Action::make('downloadDokumenKuasa')
                        ->label('Unduh PDF Gabungan')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn(CaseProject $record) => route('case-projects.dokumen-kuasa.download', ['id' => $record->id]), shouldOpenInNewTab: true),
                ])
                ->label('Surat Kuasa & Pernyataan')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCaseProjects::route('/'),
            'detail' => Pages\DetailTim::route('/{record}/detail'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
