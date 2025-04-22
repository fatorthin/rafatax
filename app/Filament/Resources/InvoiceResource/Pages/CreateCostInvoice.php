<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Coa;
use App\Models\Invoice;
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

class CreateCostInvoice extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.create-cost-invoice';

    public ?array $data = [];

    public Invoice $invoice;

    public function mount($record): void
    {
        $this->invoice = Invoice::findOrFail($record);
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Add Cost List for Invoice #' . $this->invoice->invoice_number;
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
                    ->label('Description'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        CostListInvoice::create([
            'invoice_id' => $this->invoice->id,
            'mou_id' => $this->invoice->mou_id,
            'coa_id' => $data['coa_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
        ]);

        Notification::make()
            ->title('Cost List saved successfully')
            ->success()
            ->send();
            
        $this->redirect(InvoiceResource::getUrl('viewCostList', ['record' => $this->invoice->id]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action(fn () => $this->save())
                ->color('primary'),
        ];
    }
} 