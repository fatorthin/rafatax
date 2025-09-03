<?php

namespace App\Http\Responses;

use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Http\Responses\Auth\LoginResponse as BaseLoginResponse;
 
class LoginResponse extends BaseLoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        $adminRoleNames = ['admin', 'administrator', 'superadmin', 'super-admin'];
        $isAdmin = false;
        $userRoleNames = [];
        $isAdminFlag = null;

        if ($user) {
            $isAdminFlag = isset($user->is_admin) ? (bool) $user->is_admin : null;
            if ($isAdminFlag === true) {
                $isAdmin = true;
            }

            if (!$isAdmin && method_exists($user, 'roles')) {
                $userRoleNames = $user->roles()->pluck('name')->map(fn ($n) => strtolower($n))->all();
                $isAdmin = collect($adminRoleNames)->contains(fn ($r) => in_array(strtolower($r), $userRoleNames, true));
            }
        }

        Log::info('[LoginRedirect] User login processed', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'roles' => $userRoleNames,
            'is_admin_flag' => $isAdminFlag,
            'is_admin_computed' => $isAdmin,
            'request_path' => $request->path(),
            'intended' => session('url.intended')
        ]);

        // Hapus URL intended agar tidak menimpa redirect kustom
        session()->forget('url.intended');

        if ($isAdmin) {
            return redirect()->to(Dashboard::getUrl(panel: 'admin'));
        }
 
        return redirect()->to(Dashboard::getUrl(panel: 'app'));
    }
}