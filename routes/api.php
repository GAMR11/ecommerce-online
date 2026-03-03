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

        Route::post('/github-commit', [MetricasController::class, 'captureGithubCommit']);
        Route::post('/github-pr', [MetricasController::class, 'captureGithubPR']);
        // Registrar un issue de Jira
        Route::post('/metrics/jira-issue', [MetricsController::class, 'recordJiraIssue'])
            ->name('metrics.jira-issue');

        // Registrar un sprint de Jira
        Route::post('/metrics/jira-sprint', [MetricsController::class, 'recordJiraSprint'])
            ->name('metrics.jira-sprint');

        // ============================================================================
        // JIRA DATA RETRIEVAL
        // ============================================================================

        // Obtener issues relacionados al commit (analiza commit message y branch)
        Route::post('/metrics/jira-issues/related', [MetricsController::class, 'getRelatedJiraIssues'])
            ->name('metrics.jira-issues.related');

        // Obtener un issue directamente desde Jira API
        Route::post('/metrics/jira-issue/fetch', [MetricsController::class, 'fetchJiraIssueFromAPI'])
            ->name('metrics.jira-issue.fetch');

        // Obtener resumen de Jira (velocidad, burndown, etc)
        Route::get('/metrics/jira-summary', [MetricsController::class, 'getJiraSummary'])
            ->name('metrics.jira-summary');

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
