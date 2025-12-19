<?php

namespace App\Providers;

use Filament\Tables\Table;
use Filament\Support\Colors\Color;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentColor;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('mou-invoices-table', \App\Filament\Widgets\MouInvoicesTable::class);

        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Amber,
            'success' => Color::Green,
            'warning' => Color::Amber,
            'secondary' => Color::Gray,
        ]);
    }
}
