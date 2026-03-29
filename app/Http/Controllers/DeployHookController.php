<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Route HTTP pour déploiement : migrations + vidage des caches.
 * Accès public sans clé — à protéger au niveau serveur (IP, Basic Auth, pare-feu) si l’API est exposée.
 */
class DeployHookController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $log = [];

        $run = function (string $command, array $parameters = []) use (&$log): void {
            Artisan::call($command, $parameters);
            $out = trim(Artisan::output());
            $log[$command] = $out !== '' ? $out : '(aucune sortie)';
        };

        try {
            $run('migrate', ['--force' => true]);
            $run('optimize:clear');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $log,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Migrations appliquées et caches vidés (optimize:clear).',
            'executed' => array_keys($log),
            'log' => $log,
        ]);
    }
}
