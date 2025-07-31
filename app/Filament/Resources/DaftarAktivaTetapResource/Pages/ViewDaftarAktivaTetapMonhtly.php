<?php

namespace App\Filament\Resources\DaftarAktivaTetapResource\Pages;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Models\DaftarAktivaTetap;
use Filament\Resources\Pages\Page;
use App\Models\DepresiasiAktivaTetap;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\DaftarAktivaTetapResource;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Storage;

class ViewDaftarAktivaTetapMonhtly extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DaftarAktivaTetapResource::class;

    protected static string $view = 'filament.resources.daftar-aktiva-tetap-resource.pages.view-daftar-aktiva-tetap-monhtly';

    protected static ?string $title = 'Daftar Aktiva Tetap Bulanan';

    public $bulan;
    public $tahun;
    public $periode;

    public function mount()
    {
        $this->bulan = 1;
        $this->tahun = request('tahun', date('Y'));
        $this->periode = Carbon::create($this->tahun, $this->bulan, 1)->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Pilih Periode')
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('periode')
                        ->label('Pilih Periode')
                        ->format('Y-m')
                        ->displayFormat('F Y')
                        ->default(fn () => Carbon::create($this->tahun, $this->bulan, 1))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $date = Carbon::parse($data['periode']);
                    $this->bulan = $date->format('m');
                    $this->tahun = $date->format('Y');
                }),

            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('daftar-aktiva.export', ['bulan' => $this->bulan, 'tahun' => $this->tahun]))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $bulan = $this->bulan;
        $tahun = $this->tahun;
        
        return $table
            ->query(DaftarAktivaTetap::query()->where('status', 'aktif'))
            ->striped()
            ->columns([
                TextColumn::make('deskripsi')
                    ->label('Nama Aktiva')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tahun_perolehan')
                    ->label('Tahun Perolehan')
                    ->date('M Y')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('harga_perolehan')
                    ->label('Harga Perolehan')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()    
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) use ($bulan, $tahun) {
                                $aktivaIds = $query->pluck('id');
                                return DaftarAktivaTetap::whereIn('id', $aktivaIds)->sum('harga_perolehan');
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Harga Perolehan')
                    ),  
                TextColumn::make('tarif_penyusutan')
                    ->label('Tarif (%)')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').'%')
                    ->alignCenter(),
                TextColumn::make('akumulasi_penyusutan_lalu')
                    ->label('Akumulasi Penyusutan Lalu')
                    ->alignEnd()
                    ->getStateUsing(function ($record) use ($bulan, $tahun) {
                        $tanggal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                        return DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->where('tanggal_penyusutan', '<', $tanggal->format('Y-m-d'))
                            ->sum('jumlah_penyusutan');
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) use ($bulan, $tahun) {
                                $tanggal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                                $aktivaIds = $query->pluck('id');
                                return DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->where('tanggal_penyusutan', '<', $tanggal->format('Y-m-d'))
                                    ->sum('jumlah_penyusutan');
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Akumulasi Penyusutan Lalu')
                    ),
                TextColumn::make('nilai_buku_lalu')
                    ->label('Nilai Buku Lalu')
                    ->alignEnd()
                    ->getStateUsing(function ($record) use ($bulan, $tahun) {
                        $tanggal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                        
                        // Check if asset acquisition date is in the future
                        if ($record->tahun_perolehan > $tanggal) {
                            return 0;
                        }
                        
                        $akumulasiLalu = DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->where('tanggal_penyusutan', '<', $tanggal->format('Y-m-d'))
                            ->sum('jumlah_penyusutan');
                        return $record->harga_perolehan - $akumulasiLalu;
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) use ($bulan, $tahun) {
                                $tanggal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                                $aktivaIds = $query->pluck('id');
                                
                                // Get total acquisition cost only for assets acquired before or in the current month
                                $totalHargaPerolehan = $query->where('tahun_perolehan', '<=', $tanggal)
                                    ->sum('harga_perolehan');
                                
                                $totalAkumulasiLalu = DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->where('tanggal_penyusutan', '<', $tanggal->format('Y-m-d'))
                                    ->sum('jumlah_penyusutan');
                                    
                                return $totalHargaPerolehan - $totalAkumulasiLalu;
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Nilai Buku Lalu')
                    ),
                TextColumn::make('penyusutan_bulan_ini')
                    ->label('Penyusutan Bulan Ini')
                    ->alignEnd()
                    ->getStateUsing(function ($record) use ($bulan, $tahun) {
                        $tanggalAwal = Carbon::create($tahun, $bulan, 1);
                        $tanggalAkhir = $tanggalAwal->copy()->endOfMonth();
                        return DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->whereBetween('tanggal_penyusutan', [$tanggalAwal, $tanggalAkhir])
                            ->sum('jumlah_penyusutan');
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) use ($bulan, $tahun) {
                                $tanggalAwal = Carbon::create($tahun, $bulan, 1);
                                $tanggalAkhir = $tanggalAwal->copy()->endOfMonth();
                                $aktivaIds = $query->pluck('id');
                                return DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->whereBetween('tanggal_penyusutan', [$tanggalAwal, $tanggalAkhir])
                                    ->sum('jumlah_penyusutan');
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Penyusutan Bulan Ini')
                    ),
                TextColumn::make('akumulasi_penyusutan_sd_bulan_ini')
                    ->label('Akumulasi s/d Bulan Ini')
                    ->alignEnd()
                    ->getStateUsing(function ($record) use ($bulan, $tahun) {
                        $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();
                        return DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->where('tanggal_penyusutan', '<=', $tanggalAkhir)
                            ->sum('jumlah_penyusutan');
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) use ($bulan, $tahun) {
                                $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();
                                $aktivaIds = $query->pluck('id');
                                return DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->where('tanggal_penyusutan', '<=', $tanggalAkhir)
                                    ->sum('jumlah_penyusutan');
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Akumulasi s/d Bulan Ini')
                    ),  
                TextColumn::make('nilai_buku_bulan_ini')
                    ->label('Nilai Buku')
                    ->alignEnd()
                    ->getStateUsing(function ($record) use ($bulan, $tahun) {
                        $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();
                        $akumulasi = DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $record->id)
                            ->where('tanggal_penyusutan', '<=', $tanggalAkhir)
                            ->sum('jumlah_penyusutan');
                        return $record->harga_perolehan - $akumulasi;
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function ($query) use ($bulan, $tahun) {
                                $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();
                                $aktivaIds = $query->pluck('id');
                                $totalHargaPerolehan = $query->sum('harga_perolehan');
                                $totalPenyusutan = DepresiasiAktivaTetap::whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                                    ->where('tanggal_penyusutan', '<=', $tanggalAkhir)
                                    ->sum('jumlah_penyusutan');
                                return $totalHargaPerolehan - $totalPenyusutan;
                            })
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 0, ',', '.');
                            })
                            ->label('Total Nilai Buku')
                    ),
            ])
            ->paginated(false);
    }
}
