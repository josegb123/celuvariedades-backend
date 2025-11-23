<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // Source - https://stackoverflow.com/a
// Posted by Madusha Prasad
// Retrieved 2025-11-22, License - CC BY-SA 4.0

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('api', \Illuminate\Http\Middleware\HandleCors::class);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
