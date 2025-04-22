<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Models\Coa;
use App\Models\MoU;
use App\Models\CostListMou;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\MouResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;

class CreateCostMou extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MouResource::class;

    protected static string $view = 'filament.resources.mou-resource.pages.create-cost-mou';

    public ?array $data = [];

    public MoU $mou;

    public function mount($record): void
    {
        $this->mou = MoU::findOrFail($record);
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Add Cost List for MoU #' . $this->mou->id;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('coa_id')
                    ->label('CoA')
                    ->options(Coa::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                
                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->required(),
                
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    // ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['mou_id'] = $this->mou->id;
        
        CostListMou::create($data);
        
        $this->redirect(MouResource::getUrl('viewCostList', ['record' => $this->mou->id]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action(fn () => $this->save()),
        ];
    }
} 