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
        $validKey = config('metrics.api_key'); // Cambio aquí

        // Si no hay API key configurada, denegar acceso en producción
        if (empty($validKey)) {
            if (app()->environment('production')) {
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => 'API key not configured'
                ], 500);
            }
            // En desarrollo, permitir
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
