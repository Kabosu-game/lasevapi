<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Si la requête est pour une route admin, rediriger vers admin.login
        if ($request->is('admin*')) {
            return route('admin.login');
        }

        // Pour les autres routes, rediriger vers admin.login par défaut
        // (ou créer une route login si nécessaire)
        return route('admin.login');
    }
}

