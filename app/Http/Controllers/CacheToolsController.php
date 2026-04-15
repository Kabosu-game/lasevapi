<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class CacheToolsController extends Controller
{
    public function index(): View
    {
        return view('cache-tools');
    }

    public function clear(): RedirectResponse
    {
        $log = [];

        $run = function (string $command) use (&$log): void {
            Artisan::call($command);
            $out = trim(Artisan::output());
            $log[$command] = $out !== '' ? $out : '(aucune sortie)';
        };

        try {
            $run('optimize:clear');

            return redirect()
                ->route('cache.tools.index')
                ->with('success', 'Caches vides avec succes.')
                ->with('executed', array_keys($log))
                ->with('log', $log);
        } catch (\Throwable $e) {
            return redirect()
                ->route('cache.tools.index')
                ->with('error', 'Echec du vidage des caches: ' . $e->getMessage())
                ->with('log', $log);
        }
    }
}
