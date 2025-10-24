<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;

class Log
{
    public function handle(Request $request, Closure $next)
    {
        LaravelLog::info('Request URL: '.$request->fullUrl());
        return $next($request);
    }
}
