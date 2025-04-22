<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Models\Coa;
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

class EditCostMou extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = MouResource::class;

    protected static string $view = 'filament.resources.mou-resource.pages.edit-cost-mou';

    public ?array $data = [];

    public CostListMou $costList;

    public function mount($record): void
    {
        $this->costList = CostListMou::findOrFail($record);
        $this->form->fill($this->costList->toArray());
    }

    public function getTitle(): string
    {
        return 'Edit Cost List #' . $this->costList->id;
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
                    ->rows(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $this->costList->update($data);
        
        $this->redirect(MouResource::getUrl('viewCostList', ['record' => $this->costList->mou_id]));
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