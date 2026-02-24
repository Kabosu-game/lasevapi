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
        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->is('api/*')) {
                return null;
            }
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            $errMsg = config('app.debug') ? $e->getMessage() : ('Erreur serveur: ' . class_basename($e));
            $payload = ['success' => false, 'error' => $errMsg];
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = 422;
                $payload['errors'] = $e->errors();
                $payload['error'] = implode(' ', $e->validator->errors()->all());
            }
            $response = response()->json($payload, $status);
            $origin = $request->header('Origin') ?: '*';
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
            return $response;
        });
    })->create();
