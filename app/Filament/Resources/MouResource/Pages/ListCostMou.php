<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Models\Coa;
use App\Models\MoU;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListMou;
use App\Models\Invoice;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\MouResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Actions\Action;
use App\Filament\Widgets\MouInvoicesTable;

class ListCostMou extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = MouResource::class;

    protected static ?string $model = CostListMou::class;

    protected static string $view = 'filament.resources.mou-resource.pages.list-cost-mou';

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
        return 'Detail MoU #' . $this->mou->id;
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
                Section::make('MoU Information')
                    ->schema([
                        TextEntry::make('mou_number')
                            ->label('MoU Number')
                            ->weight('bold'),
                        TextEntry::make('client.name')
                            ->label('Client')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->weight('bold')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('type')
                            ->label('Type')
                            ->weight('bold'),
                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->weight('bold')
                            ->date(),
                        TextEntry::make('end_date')
                            ->label('End Date')
                            ->weight('bold')
                            ->date(),
                        TextEntry::make('client.contact_person')
                            ->label('Contact Person')
                            ->weight('bold'),
                        TextEntry::make('client.phone')
                            ->label('Contact Number')
                            ->weight('bold'),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CostListMou::where('mou_id', $this->mou->id))
            ->heading('Cost List')
            ->description('Detail biaya untuk MoU ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')->label('CoA'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total Amount')),
                TextColumn::make('description')->label('Description'),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn (CostListMou $record): string => route('filament.admin.resources.mous.cost-edit', ['record' => $record->id])),
                \Filament\Tables\Actions\DeleteAction::make(),
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
                    ->label('Coa')
                    ->options(Coa::all()->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Cost List')
                ->url(fn(): string => MouResource::getUrl('cost-create', ['record' => $this->mou->id])),
            Action::make('back')
                ->label('Back to MoU List')
                ->url(MouResource::getUrl('index'))
                ->color('primary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
} 