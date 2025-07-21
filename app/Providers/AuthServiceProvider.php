<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use Illuminate\Support\ServiceProvider;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the custom login response
        $this->app->singleton(
            LoginResponseContract::class,
            LoginResponse::class
        );

        // Register the custom logout response
        $this->app->singleton(
            LogoutResponseContract::class,
            LogoutResponse::class
        );
    }

    public function boot(): void
    {
        //
    }
}
