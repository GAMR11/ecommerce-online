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
        Route::post('/jira-issue', [MetricasController::class, 'recordJiraIssue'])
            ->name('metrics.jira-issue');

        // Registrar un sprint de Jira
        Route::post('/jira-sprint', [MetricasController::class, 'recordJiraSprint'])
            ->name('metrics.jira-sprint');

        // ============================================================================
        // JIRA DATA RETRIEVAL
        // ============================================================================

        // Obtener issues relacionados al commit (analiza commit message y branch)
        Route::post('/jira-issues/related', [MetricasController::class, 'getRelatedJiraIssues'])
            ->name('metrics.jira-issues.related');

        // Obtener un issue directamente desde Jira API
        Route::post('/jira-issue/fetch', [MetricasController::class, 'fetchJiraIssueFromAPI'])
            ->name('metrics.jira-issue.fetch');

        // Obtener resumen de Jira (velocidad, burndown, etc)
        Route::get('/jira-summary', [MetricasController::class, 'getJiraSummary'])
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

        Route::get('/jira-stats', [MetricasController::class, 'getJiraCommitStats']);
    });

// Ruta de prueba (sin autenticación)
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
    ]);
});
