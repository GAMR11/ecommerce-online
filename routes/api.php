<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricasController;

// ============================================
// RUTAS DE MÉTRICAS DORA
// ============================================

Route::prefix('metrics')
    ->middleware('metrics.api')
    ->group(function () {

        Route::get('/metrics/prometheus', [MetricasController::class, 'prometheusMetrics']);

        // Almacenar métricas
        Route::post('/{type}', [MetricasController::class, 'store'])
            ->whereIn('type', ['deployment', 'leadtime', 'deployment-result', 'incident']);

        // Resolver incidentes (MTTR)
        Route::post('/incident/resolve', [MetricasController::class, 'resolveIncident']);

        // Consultar métricas DORA
        Route::get('/dora', [MetricasController::class, 'getDORAMetrics']);

        // Comparar GitHub Actions vs Jenkins
        Route::get('/comparison', [MetricasController::class, 'getComparison']);
    });

// Ruta de prueba (sin autenticación)
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
    ]);
});
