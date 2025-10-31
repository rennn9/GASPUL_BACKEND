<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Usage: ->middleware('role:superadmin,admin')
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Get user role from authenticated user
        $userRole = Auth::user()->role;

        // jika roles kosong, biarkan
        if (empty($roles)) {
            return $next($request);
        }

        if (! in_array($userRole, $roles)) {
            // bisa arahkan ke halaman 403 atau dashboard dengan pesan
            abort(403, 'Akses ditolak untuk role ' . $userRole);
        }

        return $next($request);
    }
}
