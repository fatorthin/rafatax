<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect('/admin/login');
        }

        $user = Auth::user();

        // Cek apakah user memiliki role admin
        if (!$user->hasRole('admin')) {
            // Redirect ke panel app jika bukan admin
            return redirect('/app')->with('error', 'Anda tidak memiliki akses ke panel admin. Hanya user dengan role admin yang dapat mengakses panel ini.');
        }

        return $next($request);
    }
}
