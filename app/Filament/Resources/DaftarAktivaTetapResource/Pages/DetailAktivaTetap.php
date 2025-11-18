<?php

namespace App\Filament\Resources\DaftarAktivaTetapResource\Pages;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Models\DaftarAktivaTetap;
use Filament\Resources\Pages\Page;
use App\Models\DepresiasiAktivaTetap;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\DaftarAktivaTetapResource;

class DetailAktivaTetap extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DaftarAktivaTetapResource::class;

    protected static string $view = 'filament.resources.daftar-aktiva-tetap-resource.pages.detail-aktiva-tetap';

    protected static ?string $title = 'Detail Aktiva Tetap';

    public DaftarAktivaTetap $record;

    protected function getTotalDepresiasi(): int
    {
        return DepresiasiAktivaTetap::query()
            ->where('daftar_aktiva_tetap_id', $this->record->id)
            ->sum('jumlah_penyusutan');
    }

    public function table(Table $table): Table
    {
        $totalDepresiasi = $this->getTotalDepresiasi();
        $sisaNilai = $this->record->harga_perolehan - $totalDepresiasi;

        return $table
            ->query(
                DepresiasiAktivaTetap::query()
                    ->where('daftar_aktiva_tetap_id', $this->record->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->getStateUsing(function ($record, $rowLoop) {
                        return $rowLoop->iteration;
                    }),
                Tables\Columns\TextColumn::make('tanggal_penyusutan')
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('jumlah_penyusutan')
                    ->numeric()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->jumlah_penyusutan, 0, ',', '.');
                    })
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('sisa_nilai')
                    ->label('Sisa Nilai')
                    ->alignEnd()
                    ->getStateUsing(function ($record, $rowLoop) use ($sisaNilai, $totalDepresiasi) {
                        // Get all previous depreciations
                        $previousDepreciations = DepresiasiAktivaTetap::query()
                            ->where('daftar_aktiva_tetap_id', $record->daftar_aktiva_tetap_id)
                            ->where(function ($query) use ($record) {
                                $query->where('tanggal_penyusutan', '<', $record->tanggal_penyusutan)
                                    ->orWhere(function ($q) use ($record) {
                                        $q->where('tanggal_penyusutan', '=', $record->tanggal_penyusutan)
                                            ->where('id', '<=', $record->id);
                                    });
                            })
                            ->sum('jumlah_penyusutan');

                        $currentSisaNilai = $this->record->harga_perolehan - $previousDepreciations;
                        return number_format($currentSisaNilai, 0, ',', '.');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->form([
                        TextInput::make('jumlah_penyusutan')
                            ->label('Jumlah Penyusutan')
                            ->numeric()
                            ->default(fn($record) => $record->jumlah_penyusutan)
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->action(function ($data, $record) {
                        $record->update([
                            'jumlah_penyusutan' => $data['jumlah_penyusutan'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tambah Data')
                ->icon('heroicon-o-plus')
                ->form([
                    DatePicker::make('tanggal_penyusutan')
                        ->label('Tanggal Penyusutan')
                        ->required(),
                    TextInput::make('jumlah_penyusutan')
                        ->label('Jumlah Penyusutan')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    DepresiasiAktivaTetap::create([
                        'daftar_aktiva_tetap_id' => $this->record->id,
                        'tanggal_penyusutan' => $data['tanggal_penyusutan'],
                        'jumlah_penyusutan' => $data['jumlah_penyusutan'],
                    ]);
                })
                ->modalSubmitActionLabel('Simpan')
                ->modalCancelActionLabel('Batal'),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(DaftarAktivaTetapResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
