<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Called by GitHub Actions right after new files are uploaded:
 *   POST /_deploy   with header  X-Deploy-Token: <DEPLOY_TOKEN from .env>
 * Runs new database migrations and refreshes Laravel's caches.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('site.deploy_token');

        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Deploy-Token'))) {
            abort(404); // look like any missing page to everyone else
        }

        @set_time_limit(300);
        $output = [];

        try {
            foreach ([
                ['migrate', ['--force' => true]],
                ['optimize:clear', []],
                ['optimize', []],
            ] as [$command, $args]) {
                Artisan::call($command, $args);
                $output[$command] = trim(Artisan::output());
            }
        } catch (Throwable $e) {
            Log::error('Deploy hook failed: '.$e->getMessage());

            return response()->json(['ok' => false, 'error' => $e->getMessage(), 'output' => $output], 500);
        }

        Log::info('Deploy hook finished', ['commit' => $request->input('commit')]);

        return response()->json(['ok' => true, 'output' => $output]);
    }
}
