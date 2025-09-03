<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Models\Coa;
use App\Models\MoU;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListMou;
use App\Models\Invoice;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use App\Filament\App\Resources\MouResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Actions;
use Filament\Actions\Action;

class ListCostMou extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = MouResource::class;

    protected static ?string $model = CostListMou::class;

    protected static string $view = 'filament.app.resources.mou-resource.pages.list-cost-mou';

    public MoU $mou;

    public $cost_lists;

    public $invoices;

    public function mount($record): void
    {
        $this->mou = MoU::findOrFail($record);
        $this->cost_lists = CostListMou::where('mou_id', $record)->get();
        $this->invoices = Invoice::where('mou_id', $record)->get();
    }

    public function getTitle(): string
    {
        return 'Detail MoU #' . $this->mou->mou_number;
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
            ->record($this->mou)
            ->schema([
                Section::make('Informasi MoU')
                    ->schema([
                        TextEntry::make('mou_number')
                            ->label('Nomor MoU')
                            ->weight('bold'),
                        TextEntry::make('client.company_name')
                            ->label('Klien')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->weight('bold')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'approved' => 'Disetujui',
                                'unapproved' => 'Belum Disetujui',
                                default => ucfirst($state),
                            }),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                                default => $state,
                            })
                            ->weight('bold'),
                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->weight('bold')
                            ->dateTime('d F Y'),
                        TextEntry::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->weight('bold')
                            ->dateTime('d F Y'),
                        TextEntry::make('client.contact_person')
                            ->label('Kontak Person')
                            ->weight('bold'),
                        TextEntry::make('client.phone')
                            ->label('Nomor Telepon')
                            ->weight('bold'),
                        TextEntry::make('categoryMou.name')
                            ->label('Kategori MoU')
                            ->weight('bold'),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CostListMou::where('mou_id', $this->mou->id))
            ->heading('Daftar Biaya')
            ->description('Detail biaya untuk MoU ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')
                    ->label('CoA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total Jumlah'))
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                //
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('coa_id')
                    ->label('CoA')
                    ->options(Coa::all()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Pilih CoA'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Daftar MoU')
                ->url(MouResource::getUrl('index'))
                ->color('primary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
