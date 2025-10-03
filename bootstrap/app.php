<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       // Alias middleware kamu
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Jika kamu mau masukkan ke dalam group tertentu, bisa juga:
        $middleware->web(append: [
            // contoh kalau ada web middleware tambahan
        ]);

        // kamu bisa atur prioritas atau pengurutan jika perlu
        // $middleware->priority([
        //     \App\Http\Middleware\SomeMiddleware::class,
        //     RoleMiddleware::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
