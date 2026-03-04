<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricasController;

// ============================================================
// RUTAS DE MÉTRICAS DORA
// ============================================================

Route::prefix('metrics')
    ->middleware('metrics.api')
    ->group(function () {

        // ── GitHub ────────────────────────────────────────────
        Route::post('/github-commit',  [MetricasController::class, 'captureGithubCommit']);
        Route::post('/github-pr',      [MetricasController::class, 'captureGithubPR']);
        Route::post('/github-pr-raw',  [MetricasController::class, 'captureGithubPRRaw']);   // ← NUEVO

        // ── Jira (actividad desde Jenkins) ───────────────────
        Route::post('/jira-issue',          [MetricasController::class, 'recordJiraIssue']);
        Route::post('/jira-issue/from-api', [MetricasController::class, 'captureJiraFromAPI']); // ← NUEVO
        Route::post('/jira-sprint',         [MetricasController::class, 'recordJiraSprint']);

        // ── Jira (consultas) ──────────────────────────────────
        Route::post('/jira-issues/related', [MetricasController::class, 'getRelatedJiraIssues']);
        Route::get('/jira-summary',         [MetricasController::class, 'getJiraSummary']);
        Route::get('/jira-stats',           [MetricasController::class, 'getJiraCommitStats']);

        // ── Métricas DORA genéricas ───────────────────────────
        // IMPORTANTE: la ruta /incident/resolve debe ir ANTES de /{type}
        // para que Laravel no la interprete como type='incident' con sub-ruta
        Route::post('/incident/resolve', [MetricasController::class, 'resolveIncident']);
        Route::post('/{type}', [MetricasController::class, 'store'])
            ->whereIn('type', ['deployment', 'leadtime', 'deployment-result', 'incident']);

        // ── Consultas DORA ────────────────────────────────────
        Route::get('/dora',       [MetricasController::class, 'getDORAMetrics']);
        Route::get('/comparison', [MetricasController::class, 'getComparison']);
        Route::get('/prometheus', [MetricasController::class, 'prometheusMetrics']);
    });

// Ruta de prueba (sin autenticación)
Route::get('/ping', function () {
    return response()->json([
        'status'    => 'ok',
        'timestamp' => now()->toDateTimeString(),
    ]);
});
