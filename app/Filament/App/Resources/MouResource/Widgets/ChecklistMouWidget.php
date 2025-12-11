<?php

namespace App\Filament\App\Resources\MouResource\Widgets;

use Filament\Tables;
use App\Models\Invoice;
use Filament\Tables\Table;
use App\Models\ChecklistMou;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class ChecklistMouWidget extends BaseWidget
{
    public ?int $mouId = null;

    public function mount()
    {
        $this->generateMonthlyChecklists();
    }

    protected function generateMonthlyChecklists()
    {
        if (!$this->mouId) return;

        $mou = \App\Models\MoU::find($this->mouId);

        if (!$mou || !$mou->start_date || !$mou->end_date) return;

        $start = \Carbon\Carbon::parse($mou->start_date)->startOfMonth();
        $end = \Carbon\Carbon::parse($mou->end_date)->startOfMonth();

        $validDates = [];

        while ($start->lte($end)) {
            // Check if checklist already exists for this month/year combo for this MoU
            // Using startOfMonth date as the identifier
            $dateString = $start->format('Y-m-d');
            $validDates[] = $dateString;

            ChecklistMou::firstOrCreate(
                [
                    'mou_id' => $this->mouId,
                    'checklist_date' => $dateString,
                ],
                [
                    'status' => 'pending',
                ]
            );

            $start->addMonth();
        }

        // Hapus checklist yang berada di luar range tanggal MoU
        // Hanya hapus yang belum ada invoice-nya untuk keamanan data
        ChecklistMou::where('mou_id', $this->mouId)
            ->whereNotIn('checklist_date', $validDates)
            ->whereNull('invoice_id') // Safety check: jangan hapus jika sudah ada invoice
            ->delete();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ChecklistMou::query()->where('mou_id', $this->mouId)
            )
            ->heading('Daftar Checklist Tagihan Bulanan MoU')
            ->columns([
                TextColumn::make('checklist_date')
                    ->label('Checklist Date')
                    ->date('F Y')
                    ->sortable(),
                SelectColumn::make('invoice_id')
                    ->label('Invoice')
                    ->options(Invoice::where('mou_id', $this->mouId)->pluck('invoice_number', 'id')),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                    ]),
                TextInputColumn::make('notes')
                    ->label('Notes')
                    ->rules(['nullable', 'max:255']),
            ])
            ->paginated(false);
    }
}
