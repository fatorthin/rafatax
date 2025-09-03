<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Pages\Dashboard;

class RedirectAdminToAdminPanel
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Hanya jalankan di path panel app
        if ($user && str_starts_with($request->path(), 'app')) {
            $isAdmin = false;

            if (isset($user->is_admin) && (bool) $user->is_admin === true) {
                $isAdmin = true;
            }

            if (!$isAdmin && method_exists($user, 'roles')) {
                $names = $user->roles()->pluck('name')->map(fn ($n) => strtolower($n))->all();
                foreach (['admin','administrator','superadmin','super-admin'] as $r) {
                    if (in_array($r, $names, true)) {
                        $isAdmin = true;
                        break;
                    }
                }
            }

            if ($isAdmin) {
                return redirect()->to(Dashboard::getUrl(panel: 'admin'));
            }
        }

        return $next($request);
    }
}
