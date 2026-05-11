<?php

namespace App\Filament\Imports;

use App\Models\CaseProjectDetail;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CaseProjectDetailImporter extends Importer
{
    protected static ?string $model = CaseProjectDetail::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('bonus')
                ->requiredMapping()
                ->castStateUsing(function (?string $state): ?string {
                    if (blank($state)) {
                        return '0';
                    }
                    // Hapus semua karakter non-angka (seperti titik, koma, atau Rp)
                    return preg_replace('/[^0-9]/', '', $state);
                })
                ->numeric()
                ->rules(['required', 'numeric']),
        ];
    }

    public function resolveRecord(): ?CaseProjectDetail
    {
        \Illuminate\Support\Facades\Log::info('Import Data Row', ['data' => $this->data]);

        if (empty($this->data['id'])) {
            \Illuminate\Support\Facades\Log::warning('ID kosong, skip baris');
            return null; // Skip baris ini jika ID kosong
        }

        $record = CaseProjectDetail::find($this->data['id']);
        
        // Jika data tidak ditemukan di database, skip baris ini agar tidak error INSERT
        if (! $record) {
            \Illuminate\Support\Facades\Log::warning('Record tidak ditemukan', ['id' => $this->data['id']]);
            return null;
        }

        // Memastikan record ter-update
        if (isset($this->data['bonus'])) {
            $record->bonus = $this->data['bonus'];
            $record->save();
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your case project detail import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
