<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Metric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class MetricasController extends Controller
{

    /**
     * CAPTURAR ISSUE DE JIRA
     * Registra información del issue/ticket de Jira
     */
    public function recordJiraIssue(Request $request)
    {
        // $validated = $request->validate([
        //     'tool' => 'required|string|in:jira,jenkins',
        //     'issue_key' => 'required|string', // KAN-1, KAN-2, etc
        //     'issue_type' => 'required|string|in:Task,Bug,Feature,Epic,Story,Subtask',
        //     'summary' => 'required|string',
        //     'description' => 'nullable|string',
        //     'status' => 'required|string|in:To Do,In Progress,In Review,Done',
        //     'assignee' => 'required|string',
        //     'reporter' => 'required|string',
        //     'created_at' => 'required|date_format:Y-m-d H:i:s',
        //     'completed_at' => 'nullable|date_format:Y-m-d H:i:s',
        //     'sprint_id' => 'nullable|integer',
        //     'story_points' => 'nullable|integer',
        // ]);

        try {
            // Guardar en tabla metrics (general)
            Metric::create([
                'type' => 'jira-issue',
                'tool' => 'jira',
                'data' => json_encode($request->all()),
                'timestamp' => now(),
            ]);

            // Opcional: Guardar en tabla jira_issues (si existe)
            if (DB::table('jira_issues')->exists()) {
                DB::table('jira_issues')->updateOrInsert(
                    ['jira_key' => $request->issue_key],
                    [
                        'issue_type' => $request->issue_type,
                        'summary' => $request->summary,
                        'description' => $request->description ?? null,
                        'status' => $request->status,
                        'assignee' => $request->assignee,
                        'reporter' => $request->reporter,
                        'created_at' => $request->created_at,
                        'completed_at' => $request->completed_at,
                        'sprint_id' => $request->sprint_id,
                        'story_points' => $request->story_points,
                        'updated_at' => now(),
                    ]
                );
            }

            return response()->json([
                'status' => 'recorded',
                'id' => Metric::max('id'),
                'type' => 'jira-issue',
                'issue_key' => $request->issue_key,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to record Jira issue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * CAPTURAR SPRINT DE JIRA
     * Registra información del sprint actual
     */
    public function recordJiraSprint(Request $request)
    {
        // $validated = $request->validate([
        //     'tool' => 'required|string|in:jira,jenkins',
        //     'sprint_id' => 'required|integer',
        //     'sprint_name' => 'required|string',
        //     'start_date' => 'required|date_format:Y-m-d',
        //     'end_date' => 'required|date_format:Y-m-d',
        //     'goal' => 'nullable|string',
        //     'state' => 'required|string|in:future,active,closed',
        // ]);

        try {
            Metric::create([
                'type' => 'jira-sprint',
                'tool' => $request->tool,
                'data' => json_encode($request->all()),
                'timestamp' => now(),
            ]);

            return response()->json([
                'status' => 'recorded',
                'id' => Metric::max('id'),
                'type' => 'jira-sprint',
                'sprint_name' => $request->sprint_name,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to record Jira sprint',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * OBTENER ISSUES ASOCIADOS AL COMMIT ACTUAL
     * Busca en la rama/commit message y devuelve issues relacionados
     * Ejemplo: Si el commit message es "KAN-1: Update controller"
     * extrae "KAN-1" y busca ese issue
     */
    public function getRelatedJiraIssues(Request $request)
    {
        // $validated = $request->validate([
        //     'commit_message' => 'required|string',
        //     'branch_name' => 'required|string',
        // ]);

        try {
            $issues = [];

            // 1. Buscar en el commit message (patrón: KAN-123, PROJ-456, etc)
            preg_match_all('/([A-Z]+-\d+)/', $request->commit_message, $matches);
            $issuesFromMessage = array_unique($matches[1] ?? []);

            // 2. Buscar en el branch name (patrón: feature/KAN-123-description)
            preg_match_all('/([A-Z]+-\d+)/', $request->branch_name, $branchMatches);
            $issuesFromBranch = array_unique($branchMatches[1] ?? []);

            // 3. Combinar y deduplicar
            $allIssueKeys = array_unique(array_merge($issuesFromMessage, $issuesFromBranch));

            if (empty($allIssueKeys)) {
                return response()->json([
                    'status' => 'no_issues_found',
                    'message' => 'No Jira issues found in commit message or branch name',
                    'commit_message' => $request->commit_message,
                    'branch_name' => $request->branch_name,
                ], 200);
            }

            // 4. Buscar en BD local (si tienes datos previos)
            $localIssues = DB::table('metrics')
                ->where('type', 'jira-issue')
                ->get()
                ->filter(function ($metric) use ($allIssueKeys) {
                    $data = json_decode($metric->data, true);
                    return in_array($data['issue_key'] ?? null, $allIssueKeys);
                })
                ->map(function ($metric) {
                    return json_decode($metric->data, true);
                })
                ->values();

            return response()->json([
                'status' => 'found',
                'issue_keys_found' => $allIssueKeys,
                'issues_from_db' => $localIssues,
                'count' => count($localIssues),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get related Jira issues',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getJiraCommitStats()
    {
        try {
            $stats = Metric::where('type', 'jira-issue')
                ->get()
                ->map(function ($metric) {
                    // 1. Decodificar el JSON de la columna 'data'
                    $content = is_array($metric->data) ? $metric->data : json_decode($metric->data, true);
                    
                    // Manejar posible doble encoding (si se guardó como string JSON)
                    if (is_string($content)) {
                        $content = json_decode($content, true);
                    }

                    // 2. Normalizar: ¿Viene en la raíz o dentro de ['data']?
                    // Los nuevos registros de Jenkins vienen envueltos en una llave 'data'
                    $innerData = (isset($content['data']) && is_array($content['data'])) 
                        ? $content['data'] 
                        : $content;

                    return [
                        'issue_key'   => $innerData['issue_key'] ?? null,
                        'assignee'    => $innerData['assignee'] ?? null,
                        // Usamos el timestamp de la métrica como fecha de actividad
                        'activity_at' => $metric->timestamp ?? $metric->created_at,
                    ];
                })
                // 3. Filtrar registros corruptos o sin llave para no ensuciar la estadística
                ->filter(fn($item) => !empty($item['issue_key']))
                ->groupBy('issue_key')
                ->map(function ($group, $key) {
                    return [
                        'issue_key'              => $key,
                        'total_commits_detected' => $group->count(),
                        'last_activity'          => $group->max('activity_at'),
                        'developers'             => $group->pluck('assignee')
                                                        ->filter() // Quita nulos
                                                        ->unique()
                                                        ->values()
                    ];
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * INTEGRACIÓN JIRA API (Opcional)
     * Si tienes acceso a la API de Jira, obtén datos directamente desde allí
     * Necesitas: JIRA_URL, JIRA_USERNAME, JIRA_API_TOKEN en .env
     */
    public function fetchJiraIssueFromAPI(Request $request)
    {
        // $validated = $request->validate([
        //     'issue_key' => 'required|string', // KAN-1, KAN-2, etc
        // ]);

        try {
            $jiraUrl = env('JIRA_URL');
            $jiraUsername = env('JIRA_USERNAME');
            $jiraApiToken = env('JIRA_API_TOKEN');

            if (!$jiraUrl || !$jiraUsername || !$jiraApiToken) {
                return response()->json([
                    'error' => 'Jira API credentials not configured',
                    'message' => 'Set JIRA_URL, JIRA_USERNAME, JIRA_API_TOKEN in .env',
                ], 400);
            }

            // Llamar a Jira API
            $response = Http::withBasicAuth($jiraUsername, $jiraApiToken)
                ->get("{$jiraUrl}/rest/api/3/issue/{$request->issue_key}");

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to fetch from Jira API',
                    'status' => $response->status(),
                ], $response->status());
            }

            $issueData = $response->json();

            // Extraer datos importantes
            $extractedData = [
                'tool' => 'jira-api',
                'issue_key' => $issueData['key'],
                'issue_type' => $issueData['fields']['issuetype']['name'] ?? 'Unknown',
                'summary' => $issueData['fields']['summary'] ?? '',
                'description' => $issueData['fields']['description'] ?? '',
                'status' => $issueData['fields']['status']['name'] ?? 'Unknown',
                'assignee' => $issueData['fields']['assignee']['displayName'] ?? 'Unassigned',
                'reporter' => $issueData['fields']['reporter']['displayName'] ?? 'Unknown',
                'created_at' => date('Y-m-d H:i:s', strtotime($issueData['fields']['created'] ?? now())),
                'updated_at' => date('Y-m-d H:i:s', strtotime($issueData['fields']['updated'] ?? now())),
                'story_points' => $issueData['fields']['customfield_10016'] ?? null, // Campo personalizado
                'raw_data' => $issueData,
            ];

            // Guardar en BD
            Metric::create([
                'type' => 'jira-issue-api',
                'tool' => 'jira-api',
                'data' => json_encode($extractedData),
                'timestamp' => now(),
            ]);

            return response()->json([
                'status' => 'fetched_and_recorded',
                'issue_key' => $extractedData['issue_key'],
                'issue_type' => $extractedData['issue_type'],
                'summary' => $extractedData['summary'],
                'status' => $extractedData['status'],
                'assignee' => $extractedData['assignee'],
                'story_points' => $extractedData['story_points'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch Jira issue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * OBTENER RESUMEN DE JIRA (Velocidad, Burndown, etc)
     * Calcula métricas de productividad basadas en datos de Jira
     */
    public function getJiraSummary(Request $request)
    {
        $days = $request->get('days', 30);

        try {
            // Obtener todos los issues del período
            $issues = DB::table('metrics')
                ->where('type', 'jira-issue')
                ->where('timestamp', '>=', now()->subDays($days))
                ->get()
                ->map(function ($metric) {
                    return json_decode($metric->data, true);
                });

            // Calcular estadísticas
            $totalIssues = $issues->count();
            $completedIssues = $issues->filter(fn($i) => $i['status'] === 'Done')->count();
            $inProgressIssues = $issues->filter(fn($i) => $i['status'] === 'In Progress')->count();
            $todoIssues = $issues->filter(fn($i) => $i['status'] === 'To Do')->count();

            $totalStoryPoints = $issues->sum(fn($i) => $i['story_points'] ?? 0);
            $completedStoryPoints = $issues
                ->filter(fn($i) => $i['status'] === 'Done')
                ->sum(fn($i) => $i['story_points'] ?? 0);

            // Calcular velocity (story points por día)
            $velocity = $days > 0 ? round($completedStoryPoints / $days, 2) : 0;

            // Calcular lead time promedio por issue
            $leadTimes = $issues
                ->filter(fn($i) => $i['completed_at'] && $i['created_at'])
                ->map(function ($i) {
                    $created = new \DateTime($i['created_at']);
                    $completed = new \DateTime($i['completed_at']);
                    return $completed->diff($created)->days;
                });

            $avgLeadTime = $leadTimes->isNotEmpty() ? round($leadTimes->avg(), 2) : 0;

            return response()->json([
                'period_days' => $days,
                'issues' => [
                    'total' => $totalIssues,
                    'completed' => $completedIssues,
                    'in_progress' => $inProgressIssues,
                    'todo' => $todoIssues,
                    'completion_rate_percent' => $totalIssues > 0
                        ? round(($completedIssues / $totalIssues) * 100, 2)
                        : 0,
                ],
                'story_points' => [
                    'total' => $totalStoryPoints,
                    'completed' => $completedStoryPoints,
                    'pending' => $totalStoryPoints - $completedStoryPoints,
                    'completion_percent' => $totalStoryPoints > 0
                        ? round(($completedStoryPoints / $totalStoryPoints) * 100, 2)
                        : 0,
                ],
                'velocity' => [
                    'story_points_per_day' => $velocity,
                    'issues_per_day' => round($totalIssues / $days, 2),
                ],
                'lead_time' => [
                    'average_days' => $avgLeadTime,
                    'total_measured' => count($leadTimes),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate Jira summary',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
     // ============================================
    // NUEVOS ENDPOINTS PARA GITHUB
    // ============================================

    /**
     * Capturar datos de commit desde GitHub
     * POST /api/metrics/github-commit
     */
    public function captureGithubCommit(Request $request)
    {
        $validated = $request->validate([
            'tool' => 'required|string',
            'commit_sha' => 'required|string',
            'branch' => 'required|string',
            'author' => 'required|string',
            'message' => 'nullable|string',
            'timestamp' => 'required|date_format:Y-m-d H:i:s'
        ]);

        $metric = Metric::create([
            'type' => 'github_commit',
            'tool' => 'github',
            'data' => $validated,
            'timestamp' => $validated['timestamp']
        ]);

        return response()->json([
            'status' => 'recorded',
            'type' => 'github_commit',
            'id' => $metric->id
        ], 201);
    }

    /**
     * Capturar datos de Pull Request desde GitHub
     * POST /api/metrics/github-pr
     */
    public function captureGithubPR(Request $request)
    {
        $validated = $request->validate([
            'pr_number' => 'required|integer',
            'title' => 'required|string',
            'branch' => 'nullable|string',
            'author' => 'required|string',
            'created_at' => 'required|date_format:Y-m-d H:i:s',
            'merged_at' => 'nullable|date_format:Y-m-d H:i:s',
            'review_count' => 'nullable|integer',
            'commits_count' => 'nullable|integer',
        ]);

        // Calcular tiempo de merge si existe
        if ($validated['merged_at']) {
            $createdTime = Carbon::createFromFormat('Y-m-d H:i:s', $validated['created_at']);
            $mergedTime = Carbon::createFromFormat('Y-m-d H:i:s', $validated['merged_at']);
            $validated['time_to_merge_minutes'] = $mergedTime->diffInMinutes($createdTime);
        }

        $metric = Metric::create([
            'type' => 'github_pr',
            'tool' => 'github',
            'data' => $validated,
            'timestamp' => $validated['created_at']
        ]);

        return response()->json([
            'status' => 'recorded',
            'type' => 'github_pr',
            'id' => $metric->id
        ], 201);
    }
    
    // ============================================
    // NUEVOS ENDPOINTS PARA JIRA
    // ============================================

    /**
     * Capturar datos de Issue desde Jira
     * POST /api/metrics/jira-issue
     */
    public function captureJiraIssue(Request $request)
    {
        $validated = $request->validate([
            'issue_key' => 'required|string',      // ej: KAN-1
            'issue_type' => 'required|string',     // Task, Bug, Feature, etc
            'summary' => 'required|string',
            'status' => 'required|string',         // To Do, In Progress, Done, etc
            'assignee' => 'nullable|string',
            'created_at' => 'required|date_format:Y-m-d H:i:s',
            'updated_at' => 'nullable|date_format:Y-m-d H:i:s',
            'completed_at' => 'nullable|date_format:Y-m-d H:i:s',
            'story_points' => 'nullable|integer',
            'sprint_name' => 'nullable|string'
        ]);

        // Calcular tiempo de completación
        if ($validated['completed_at']) {
            $createdTime = Carbon::createFromFormat('Y-m-d H:i:s', $validated['created_at']);
            $completedTime = Carbon::createFromFormat('Y-m-d H:i:s', $validated['completed_at']);
            $validated['time_to_complete_hours'] = $createdTime->diffInHours($completedTime);
        }

        $metric = Metric::create([
            'type' => 'jira_issue',
            'tool' => 'jira',
            'data' => $validated,
            'timestamp' => $validated['created_at']
        ]);

        return response()->json([
            'status' => 'recorded',
            'type' => 'jira_issue',
            'id' => $metric->id,
            'issue_key' => $validated['issue_key']
        ], 201);
    }

    public function prometheusMetrics()
    {
        $tools = ['github-actions', 'jenkins'];
        $output = "";

        foreach ($tools as $tool) {
            $metrics = $this->calculateMetricsForTool($tool, 30); // Usamos tu lógica existente

            // Formato Prometheus: nombre_metrica{etiquetas} valor
            $output .= "# HELP dora_deployment_frequency Frecuencia de despliegue por dia\n";
            $output .= "# TYPE dora_deployment_frequency gauge\n";
            $output .= "dora_deployment_frequency{tool=\"$tool\"} " . $metrics['deployment_frequency']['value'] . "\n\n";

            $output .= "# HELP dora_lead_time_hours Tiempo promedio de cambios en horas\n";
            $output .= "# TYPE dora_lead_time_hours gauge\n";
            $output .= "dora_lead_time_hours{tool=\"$tool\"} " . $metrics['lead_time']['hours'] . "\n\n";

            $output .= "# HELP dora_change_failure_rate Porcentaje de fallos en cambios\n";
            $output .= "# TYPE dora_change_failure_rate gauge\n";
            $output .= "dora_change_failure_rate{tool=\"$tool\"} " . $metrics['change_failure_rate']['percentage'] . "\n\n";

            $output .= "# HELP dora_mttr_hours Tiempo medio de recuperacion en horas\n";
            $output .= "# TYPE dora_mttr_hours gauge\n";
            $output .= "dora_mttr_hours{tool=\"$tool\"} " . $metrics['mttr']['hours'] . "\n";
        }

        return response($output)->header('Content-Type', 'text/plain; version=0.0.4');
    }
    // ============================================
    // ALMACENAR MÉTRICAS
    // ============================================

    /**
     * Almacenar una métrica genérica
     */
    public function store(Request $request, string $type)
    {
        // var_dump($request->all());
        $validator = Validator::make($request->all(), [
            // 'tool' => 'required|in:github-actions,jenkins',
            'timestamp' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {

            $data = $request->all();

            if ($type === 'leadtime') {
                $now = Carbon::now();

                // Lead Time Técnico: Desde el commit (eficiencia del dev)
                if ($request->filled('commit_at')) {
                    $data['lead_time_seconds'] = $now->diffInSeconds(Carbon::parse($request->commit_at));
                }

                // Lead Time de Negocio: Desde Jira (agilidad organizacional)
                if ($request->filled('jira_created_at')) {
                    $data['business_lead_time_seconds'] = $now->diffInSeconds(Carbon::parse($request->jira_created_at));
                }
            }

            $metric = Metric::create([
                'type' => $type,
                'tool' => $request->tool,
                'data' => $data,
                'timestamp' => $request->timestamp ?? now(),
            ]);

            return response()->json([
                'status' => 'recorded',
                'id' => $metric->id,
                'type' => $type,
                'tool' => $request->tool,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to store metric',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolver un incidente (MTTR)
     */
    public function resolveIncident(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'tool' => 'required|in:github-actions,jenkins',
            'resolution_time' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar el último incidente abierto para esta herramienta
            $incident = Metric::where('type', 'incident')
                ->where('tool', $request->tool)
                ->whereNull('data->resolution_time')
                ->latest('timestamp')
                ->first();

            if (!$incident) {
                return response()->json([
                    'status' => 'no_open_incident',
                    'message' => 'No open incident found to resolve'
                ], 404);
            }

            // Actualizar con tiempo de resolución
            $data = $incident->data;
            $data['resolution_time'] = $request->resolution_time;
            $data['status'] = 'resolved';

            $incident->update(['data' => $data]);

            // Calcular MTTR
            $startTime = Carbon::parse($incident->data['start_time']);
            $endTime = Carbon::parse($request->resolution_time);
            $mttrSeconds = $endTime->diffInSeconds($startTime);

            return response()->json([
                'status' => 'resolved',
                'incident_id' => $incident->id,
                'mttr_seconds' => $mttrSeconds,
                'mttr_minutes' => round($mttrSeconds / 60, 2),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to resolve incident',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // CONSULTAR MÉTRICAS DORA
    // ============================================

    /**
     * Obtener métricas DORA de una herramienta
     */
    public function getDORAMetrics(Request $request)
    {
        $tool = $request->query('tool');
        $period = (int) $request->query('period', 30);

        if (!$tool || !in_array($tool, ['github-actions', 'jenkins'])) {
            return response()->json([
                'error' => 'Invalid tool parameter. Must be: github-actions or jenkins'
            ], 400);
        }

        $startDate = now()->subDays($period);

        try {
            // MÉTRICA 1: Deployment Frequency
            $deploymentsCount = Metric::ofType('deployment')
                ->forTool($tool)
                ->where('timestamp', '>=', $startDate)
                ->count();

            $deploymentFrequency = $period > 0 ? $deploymentsCount / $period : 0;

            // MÉTRICA 2: Lead Time for Changes
            $avgLeadTime = Metric::ofType('leadtime')
                ->forTool($tool)
                ->where('timestamp', '>=', $startDate)
                ->avg('data->lead_time_seconds');

            // MÉTRICA 3: Change Failure Rate
            $totalDeployments = Metric::ofType('deployment-result')
                ->forTool($tool)
                ->where('timestamp', '>=', $startDate)
                ->count();

            $failedDeployments = Metric::ofType('deployment-result')
                ->forTool($tool)
                ->where('data->is_failure', true)
                ->where('timestamp', '>=', $startDate)
                ->count();

            $changeFailureRate = $totalDeployments > 0
                ? ($failedDeployments / $totalDeployments) * 100
                : 0;

            // MÉTRICA 4: Mean Time to Recovery (MTTR)
            $resolvedIncidents = Metric::ofType('incident')
                ->forTool($tool)
                ->whereNotNull('data->resolution_time')
                ->where('timestamp', '>=', $startDate)
                ->get();

            $recoveryTimes = $resolvedIncidents->map(function ($incident) {
                $start = Carbon::parse($incident->data['start_time']);
                $end = Carbon::parse($incident->data['resolution_time']);
                return $end->diffInSeconds($start);
            });

            $mttr = $recoveryTimes->avg() ?? 0;

            return response()->json([
                'tool' => $tool,
                'period_days' => $period,
                'date_range' => [
                    'from' => $startDate->toDateTimeString(),
                    'to' => now()->toDateTimeString(),
                ],
                'metrics' => [
                    'deployment_frequency' => [
                        'value' => round($deploymentFrequency, 2),
                        'unit' => 'deployments/day',
                        'count' => $deploymentsCount,
                        'rating' => $this->rateDeploymentFrequency($deploymentFrequency),
                    ],
                    'lead_time_for_changes' => [
                        'value_seconds' => round($avgLeadTime ?? 0, 2),
                        'value_minutes' => round(($avgLeadTime ?? 0) / 60, 2),
                        'value_hours' => round(($avgLeadTime ?? 0) / 3600, 2),
                        'unit' => 'hours',
                        'rating' => $this->rateLeadTime($avgLeadTime ?? 0),
                    ],
                    'change_failure_rate' => [
                        'value' => round($changeFailureRate, 2),
                        'unit' => '%',
                        'total_deployments' => $totalDeployments,
                        'failed_deployments' => $failedDeployments,
                        'rating' => $this->rateChangeFailureRate($changeFailureRate),
                    ],
                    'mean_time_to_recovery' => [
                        'value_seconds' => round($mttr, 2),
                        'value_minutes' => round($mttr / 60, 2),
                        'value_hours' => round($mttr / 3600, 2),
                        'unit' => 'hours',
                        'incidents_resolved' => $resolvedIncidents->count(),
                        'rating' => $this->rateMTTR($mttr),
                    ],
                ],
                'overall_rating' => $this->calculateOverallRating([
                    $this->rateDeploymentFrequency($deploymentFrequency),
                    $this->rateLeadTime($avgLeadTime ?? 0),
                    $this->rateChangeFailureRate($changeFailureRate),
                    $this->rateMTTR($mttr),
                ]),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate DORA metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comparar métricas entre GitHub Actions y Jenkins
     */
    public function getComparison(Request $request)
    {
        $period = (int) $request->query('period', 30);

        try {
            $githubMetrics = $this->calculateMetricsForTool('github-actions', $period);
            $jenkinsMetrics = $this->calculateMetricsForTool('jenkins', $period);

            return response()->json([
                'period_days' => $period,
                'comparison' => [
                    'github-actions' => $githubMetrics,
                    'jenkins' => $jenkinsMetrics,
                ],
                'winner' => $this->determineWinner($githubMetrics, $jenkinsMetrics),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate comparison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // MÉTODOS PRIVADOS DE CLASIFICACIÓN DORA
    // ============================================

    private function rateDeploymentFrequency(float $frequency): string
    {
        if ($frequency >= 1) return 'Elite';        // On-demand (multiple per day)
        if ($frequency >= 0.14) return 'High';      // Between once per week and once per month
        if ($frequency >= 0.03) return 'Medium';    // Between once per month and once every 6 months
        return 'Low';                                // Fewer than once per six months
    }

    private function rateLeadTime(float $seconds): string
    {
        $hours = $seconds / 3600;
        if ($hours < 24) return 'Elite';      // Less than one day
        if ($hours < 168) return 'High';      // Between one day and one week
        if ($hours < 720) return 'Medium';    // Between one week and one month
        return 'Low';                          // Between one month and six months
    }

    private function rateChangeFailureRate(float $rate): string
    {
        if ($rate <= 15) return 'Elite';      // 0-15%
        if ($rate <= 30) return 'High';       // 16-30%
        if ($rate <= 45) return 'Medium';     // 31-45%
        return 'Low';                          // > 45%
    }

    private function rateMTTR(float $seconds): string
    {
        $hours = $seconds / 3600;
        if ($hours < 1) return 'Elite';       // Less than one hour
        if ($hours < 24) return 'High';       // Less than one day
        if ($hours < 168) return 'Medium';    // Between one day and one week
        return 'Low';                          // Between one week and one month
    }

    private function calculateOverallRating(array $ratings): string
    {
        $scores = [
            'Elite' => 4,
            'High' => 3,
            'Medium' => 2,
            'Low' => 1,
        ];

        $totalScore = 0;
        foreach ($ratings as $rating) {
            $totalScore += $scores[$rating] ?? 0;
        }

        $avgScore = $totalScore / count($ratings);

        if ($avgScore >= 3.5) return 'Elite';
        if ($avgScore >= 2.5) return 'High';
        if ($avgScore >= 1.5) return 'Medium';
        return 'Low';
    }

    private function calculateMetricsForTool(string $tool, int $period): array
    {
        $startDate = now()->subDays($period);

        $deploymentsCount = Metric::ofType('deployment')
            ->forTool($tool)
            ->where('timestamp', '>=', $startDate)
            ->count();

        $deploymentFrequency = $period > 0 ? $deploymentsCount / $period : 0;

        $avgLeadTime = Metric::ofType('leadtime')
            ->forTool($tool)
            ->where('timestamp', '>=', $startDate)
            ->avg('data->lead_time_seconds') ?? 0;

        $totalDeployments = Metric::ofType('deployment-result')
            ->forTool($tool)
            ->where('timestamp', '>=', $startDate)
            ->count();

        $failedDeployments = Metric::ofType('deployment-result')
            ->forTool($tool)
            ->where('data->is_failure', true)
            ->where('timestamp', '>=', $startDate)
            ->count();

        $changeFailureRate = $totalDeployments > 0
            ? ($failedDeployments / $totalDeployments) * 100
            : 0;

        $resolvedIncidents = Metric::ofType('incident')
            ->forTool($tool)
            ->whereNotNull('data->resolution_time')
            ->where('timestamp', '>=', $startDate)
            ->get();

        $recoveryTimes = $resolvedIncidents->map(function ($incident) {
            $start = Carbon::parse($incident->data['start_time']);
            $end = Carbon::parse($incident->data['resolution_time']);
            return $end->diffInSeconds($start);
        });

        $mttr = $recoveryTimes->avg() ?? 0;

        return [
            'deployment_frequency' => [
                'value' => round($deploymentFrequency, 2),
                'rating' => $this->rateDeploymentFrequency($deploymentFrequency),
            ],
            'lead_time' => [
                'hours' => round($avgLeadTime / 3600, 2),
                'rating' => $this->rateLeadTime($avgLeadTime),
            ],
            'change_failure_rate' => [
                'percentage' => round($changeFailureRate, 2),
                'rating' => $this->rateChangeFailureRate($changeFailureRate),
            ],
            'mttr' => [
                'hours' => round($mttr / 3600, 2),
                'rating' => $this->rateMTTR($mttr),
            ],
            'overall_rating' => $this->calculateOverallRating([
                $this->rateDeploymentFrequency($deploymentFrequency),
                $this->rateLeadTime($avgLeadTime),
                $this->rateChangeFailureRate($changeFailureRate),
                $this->rateMTTR($mttr),
            ]),
        ];
    }

    private function determineWinner(array $github, array $jenkins): array
    {
        $scores = [
            'Elite' => 4,
            'High' => 3,
            'Medium' => 2,
            'Low' => 1,
        ];

        $githubScore = $scores[$github['overall_rating']];
        $jenkinsScore = $scores[$jenkins['overall_rating']];

        if ($githubScore > $jenkinsScore) {
            return [
                'tool' => 'github-actions',
                'reason' => 'Better overall DORA performance',
            ];
        } elseif ($jenkinsScore > $githubScore) {
            return [
                'tool' => 'jenkins',
                'reason' => 'Better overall DORA performance',
            ];
        } else {
            return [
                'tool' => 'tie',
                'reason' => 'Both tools have equal DORA performance',
            ];
        }
    }
}
