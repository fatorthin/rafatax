<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Models\MoU;
use App\Models\Client;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use App\Filament\Resources\ClientResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Actions;
use Filament\Actions\Action;


class ClientDetail extends Page implements HasInfolists, HasTable
{
    use InteractsWithInfolists;
    use InteractsWithTable;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.client-detail';

    public $client;
    public $mou;

    public function mount($record): void
    {
        $this->client = Client::findOrFail($record);
        $this->mou = MoU::where('client_id', $record)->get();
    }

    public function getTitle(): string
    {
        return 'Detail Klien #' . $this->client->code;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }


    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->client)
            ->schema([
                Section::make('Client Information')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Klien')
                            ->weight('bold'),
                        TextEntry::make('company_name')
                            ->label('Nama Perusahaan')
                            ->weight('bold'),
                        TextEntry::make('address')
                            ->label('Alamat Perusahaan')
                            ->weight('bold'),
                        TextEntry::make('email')
                            ->label('Email Perusahaan')
                            ->weight('bold'),
                        TextEntry::make('owner_name')
                            ->label('Nama Pimpinan')
                            ->weight('bold'),
                        TextEntry::make('owner_role')
                            ->label('Jabatan Pimpinan')
                            ->weight('bold'),
                        TextEntry::make('contact_person')
                            ->label('Kontak Person')
                            ->weight('bold'),
                        TextEntry::make('phone')
                            ->label('No WA Kontak Person')
                            ->weight('bold'),
                        TextEntry::make('npwp')
                            ->label('No NPWP')
                            ->weight('bold'),
                        TextEntry::make('grade')
                            ->label('Grade Klien')
                            ->weight('bold'),
                        TextEntry::make('type')
                            ->label('Tipe Klien')
                            ->weight('bold')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                            }),
                        TextEntry::make('jenis_wp')
                            ->label('Jenis Wajib Pajak')
                            ->weight('bold')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'perseorangan' => 'Perseorangan',
                                'badan' => 'Badan',
                            }),
                        Fieldset::make('Laporan Pajak Klien')
                            ->schema([
                                TextEntry::make('pph_25_reporting')
                                    ->label('PPH Pasal 25')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(bool $state): string
                                    => $state ? 'Ya' : 'Tidak'),
                                TextEntry::make('pph_23_reporting')
                                    ->label('PPH Pasal 23')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(bool $state): string
                                    => $state ? 'Ya' : 'Tidak'),
                                TextEntry::make('pph_21_reporting')
                                    ->label('PPH Pasal 21 / Basil')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(bool $state): string
                                    => $state ? 'Ya' : 'Tidak'),
                                TextEntry::make('pph_4_reporting')
                                    ->label('PPH Pasal 4')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(bool $state): string
                                    => $state ? 'Ya' : 'Tidak'),
                                TextEntry::make('ppn_reporting')
                                    ->label('PPN')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(bool $state): string
                                    => $state ? 'Ya' : 'Tidak'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(3)


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => MoU::where('client_id', $this->client->id))
            ->heading('List MoU Client')
            ->columns([
                TextColumn::make('mou_number')
                    ->label('No MoU')
                    ->rowIndex(),
                TextColumn::make('description')->label('Description'),
                TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->dateTime('d F Y'),
                TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->dateTime('d F Y'),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    }),
                TextColumn::make('cost_lists_sum_amount')
                    ->label('Total MoU')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        return number_format($record->cost_lists()->sum('amount'), 0, ',', '.');
                    })->alignEnd(),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => "/admin/mous/{$record->id}/cost-list") // Change this line
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                //
            ]);
    }
}
