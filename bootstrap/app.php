<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Konfigurasi aplikasi
return Application::configure(basePath: dirname(__DIR__))
    // Routing utama
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // Middleware
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * 🌐 Daftar alias middleware
         * Pindahan dari Kernel.php — wajib untuk Laravel 11+
         */
        $middleware->alias([
            'log' => \App\Http\Middleware\Log::class,

            // 🔐 Middleware autentikasi
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

            // 🧑‍💼 Middleware admin & role
            'auth.admin' => \App\Http\Middleware\AdminAuth::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'session.auth' => \App\Http\Middleware\EnsureUserIsLoggedIn::class,
        ]);

        /**
         * 🔓 Exclude API routes from CSRF verification
         */
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    // Exception handling
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom exception handling bisa ditambahkan di sini
    })
    ->create();
