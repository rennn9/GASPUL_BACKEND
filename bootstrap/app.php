<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Konfigurasi aplikasi
return Application::configure(basePath: dirname(__DIR__))
    // Routing utama
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
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
    })
    // Exception handling
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom exception handling bisa ditambahkan di sini
    })
    ->create();
