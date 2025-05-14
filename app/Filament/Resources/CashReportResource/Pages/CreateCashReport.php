<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use App\Models\CashReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class CreateCashReport extends CreateRecord
{
    protected static string $resource = CashReportResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        // Otomatis mengisi cash_reference_id dari URL setelah mount
        $cash_reference_id = request()->query('cash_reference_id');
        if ($cash_reference_id) {
            $this->form->fill([
                'cash_reference_id' => (int) $cash_reference_id,
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        // Redirect ke detail cash reference jika ID tersedia
        $cash_reference_id = $this->form->getState()['cash_reference_id'] ?? request()->query('cash_reference_id');
        if ($cash_reference_id) {
            return route('filament.admin.resources.cash-references.view', ['record' => $cash_reference_id]);
        }
        
        return $this->getResource()::getUrl('index');
    }
    
    protected function getRedirectUrlAfterSave(): string
    {
        // Jika menggunakan Create & Create Another, tambahkan parameter ke URL
        if ($this->getActiveFormAction() && $this->getActiveFormAction()->getName() === 'createAnother') {
            // Ambil ID dari record yang baru dibuat
            $cash_reference_id = $this->record->cash_reference_id;
            if ($cash_reference_id) {
                return $this->getResource()::getUrl('create') . '?cash_reference_id=' . $cash_reference_id;
            }
        }
        
        return parent::getRedirectUrlAfterSave();
    }
    
    public function createAnother(): void
    {
        // Simpan cash_reference_id sebelum membuat record baru
        $cash_reference_id = $this->form->getState()['cash_reference_id'] ?? null;
        
        // Panggil metode parent
        parent::createAnother();
        
        // Tambahkan kode untuk menjaga agar cash_reference_id tetap diisi
        if ($cash_reference_id) {
            $this->form->fill([
                'cash_reference_id' => (int) $cash_reference_id,
            ]);
        }
    }
}
