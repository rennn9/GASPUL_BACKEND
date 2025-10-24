<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // <-- pastikan ini ada


class SuperAdminMiddleware
{
public function handle(Request $request, Closure $next)
{
    Log::info('Masuk SuperAdminMiddleware');

    if (auth()->check()) {
        Log::info('User login: ID=' . auth()->id() . ', role=' . auth()->user()->role);
    } else {
        Log::info('Tidak ada user yang login di middleware');
    }

    if (auth()->check() && auth()->user()->role === 'superadmin') {
        Log::info('User adalah superadmin, lanjut ke route');
        return $next($request);
    }

    Log::warning('User bukan superadmin, abort 403');
    abort(403, 'Unauthorized');
}

}
