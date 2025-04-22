<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Coa;
use Filament\Forms\Form;
use App\Models\CostListInvoice;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class EditCostInvoice extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.edit-cost-invoice';

    public ?array $data = [];

    public CostListInvoice $record;

    public function mount($record): void
    {
        $this->record = CostListInvoice::findOrFail($record);
        
        $this->form->fill([
            'coa_id' => $this->record->coa_id,
            'amount' => $this->record->amount,
            'description' => $this->record->description,
        ]);
    }

    public function getTitle(): string
    {
        return 'Edit Cost List #' . $this->record->id;
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
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $this->record->update([
            'coa_id' => $data['coa_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
        ]);

        Notification::make()
            ->title('Cost List updated successfully')
            ->success()
            ->send();
            
        $this->redirect(InvoiceResource::getUrl('viewCostList', ['record' => $this->record->invoice_id]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }
} 