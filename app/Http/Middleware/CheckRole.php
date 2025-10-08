<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    /**
     * Usage: ->middleware('role:superadmin,admin')
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $userRole = $request->session()->get('user_role');

        if (! $userRole) {
            return redirect()->route('login');
        }

        // jika roles kosong, biarkan
        if (empty($roles)) {
            return $next($request);
        }

        if (! in_array($userRole, $roles)) {
            // bisa arahkan ke halaman 403 atau dashboard dengan pesan
            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}
