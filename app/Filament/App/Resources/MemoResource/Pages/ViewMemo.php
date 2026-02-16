<?php

namespace App\Filament\App\Resources\MemoResource\Pages;

use App\Filament\App\Resources\MemoResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewMemo extends ViewRecord
{
    protected static string $resource = MemoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->url(fn($record) => route('memos.pdf', ['id' => $record->id]))
                ->openUrlInNewTab(),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),

            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(MemoResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function generateInvoiceNumber(\Filament\Forms\Set $set, \Filament\Forms\Get $get): void
    {
        $invoiceDate = $get('invoice_date');
        $invoiceType = $get('invoice_type');
        $isSaldoAwal = $get('is_saldo_awal') ?? false;

        if (!$invoiceDate || !$invoiceType) {
            return;
        }

        // 1. Type
        $typeCode = $invoiceType === 'pt' ? 'PT' : 'KKP';

        // 2. Category (Default to LN for Memos)
        $categoryCode = 'LN';

        // 3. Date
        $date = \Carbon\Carbon::parse($invoiceDate);
        $year = $date->year;
        $month = $date->month;

        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        $monthRoman = $romanMonths[$month];

        // 4. Sequence
        $lastNumber = 0;

        // Find existing invoices for the same month and year
        $invoices = Invoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->pluck('invoice_number');

        foreach ($invoices as $inv) {
            $val = 0;
            if (preg_match('/^INV\/(\d+)\//', $inv, $matches)) {
                $val = (int)$matches[1];
            } elseif (preg_match('/^INV\/SA\/(\d+)\//', $inv, $matches)) {
                $val = (int)$matches[1];
            }

            if ($val > $lastNumber) {
                $lastNumber = $val;
            }
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Format
        if ($isSaldoAwal) {
            $result = sprintf('INV/SA/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
        } else {
            $result = sprintf('INV/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
        }
        $set('invoice_number', $result);
    }
}
