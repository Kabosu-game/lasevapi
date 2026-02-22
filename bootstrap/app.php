<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        
        // CORS doit s'exécuter en premier pour tous les appels API (y compris les erreurs 500)
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            // Si la requête est pour une route admin, rediriger vers admin.login
            if ($request->is('admin*')) {
                return route('admin.login');
            }
            return route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
