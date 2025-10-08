<?php

namespace App\Http\Middleware;

use Closure;

class EnsureUserIsLoggedIn
{
    public function handle($request, Closure $next)
    {
        if (! $request->session()->get('is_logged_in')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
