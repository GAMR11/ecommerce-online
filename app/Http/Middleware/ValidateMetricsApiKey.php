<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateMetricsApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $validKey = config('services.metrics.api_key');

        // Si no hay API key configurada, permitir (desarrollo)
        if (empty($validKey)) {
            return $next($request);
        }

        // Validar API key
        if ($apiKey !== $validKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key'
            ], 401);
        }

        return $next($request);
    }
}
