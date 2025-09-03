<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $title = 'Profile';
    protected static ?string $slug = 'profile';
    protected static ?string $navigationLabel = 'Profile Saya';
    protected static string $view = 'filament.app.pages.profile';

    public function mount(): void
    {
        // Tampilkan error message jika ada dari session
        if (session()->has('error')) {
            // Error message akan ditampilkan di view
        }
    }

    protected function getViewData(): array
    {
        return [
            'user' => Auth::user(),
        ];
    }
}
