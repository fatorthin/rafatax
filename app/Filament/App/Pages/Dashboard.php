<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        // Tampilkan error message jika ada dari session
        if (session()->has('error')) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body(session('error'))
                ->danger()
                ->send();
            
            // Hapus error message dari session
            session()->forget('error');
        }
    }
}
