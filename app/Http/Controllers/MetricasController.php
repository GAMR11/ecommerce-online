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
    // ============================================
    // JIRA — Registrar issue desde Jenkins
    // POST /api/metrics/jira-issue
    // Recibe el payload envuelto que manda Jenkins:
    // { type, tool, timestamp, data: { issue_key, ... } }
    // ============================================
    public function recordJiraIssue(Request $request)
    {
        try {
            $payload = $request->all();

            // Jenkins envía el issue_key dentro de 'data'
            // Normalizamos para siempre tener $innerData con los campos reales
            $innerData = (isset($payload['data']) && is_array($payload['data']))
                ? $payload['data']
                : $payload;

            $metric = Metric::create([
                'type'      => 'jira-issue',
                'tool'      => $payload['tool'] ?? 'jenkins',
                'data'      => $payload,          // guardamos todo tal cual llega
                'timestamp' => $payload['timestamp'] ?? now(),
            ]);

            return response()->json([
                'status'    => 'recorded',
                'id'        => $metric->id,
                'type'      => 'jira-issue',
                'issue_key' => $innerData['issue_key'] ?? null,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to record Jira issue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // JIRA — Recibir datos crudos de la API de Jira
    // POST /api/metrics/jira-issue/from-api
    // Recibe la respuesta JSON directa de:
    // GET /rest/api/3/issue/{KEY}?fields=summary,status,...
    // ============================================
    public function captureJiraFromAPI(Request $request)
    {
        try {
            $raw    = $request->all();
            $fields = $raw['fields'] ?? [];

            $issueKey  = $raw['key'] ?? null;
            $status    = $fields['status']['name'] ?? null;
            $assignee  = $fields['assignee']['displayName'] ?? 'Unassigned';
            $createdAt = isset($fields['created'])
                         ? date('Y-m-d H:i:s', strtotime($fields['created']))
                         : null;
            $updatedAt = isset($fields['updated'])
                         ? date('Y-m-d H:i:s', strtotime($fields['updated']))
                         : null;
            $storyPts  = $fields['customfield_10016'] ?? null;
            $priority  = $fields['priority']['name'] ?? null;
            $issueType = $fields['issuetype']['name'] ?? null;
            $summary   = $fields['summary'] ?? null;

            $metric = Metric::create([
                'type'      => 'jira_issue_api',
                'tool'      => 'jira',
                'data'      => [
                    'issue_key'    => $issueKey,
                    'summary'      => $summary,
                    'status'       => $status,
                    'assignee'     => $assignee,
                    'issue_type'   => $issueType,
                    'priority'     => $priority,
                    'story_points' => $storyPts,
                    'created_at'   => $createdAt,   // ← clave para Lead Time de negocio
                    'updated_at'   => $updatedAt,
                    'raw'          => $raw,
                ],
                'timestamp' => now(),
            ]);

            return response()->json([
                'status'       => 'recorded',
                'id'           => $metric->id,
                'type'         => 'jira_issue_api',
                'issue_key'    => $issueKey,
                'jira_status'  => $status,
                'created_at'   => $createdAt,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to capture Jira API data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // GITHUB — Recibir PRs crudos de la API de GitHub
    // POST /api/metrics/github-pr-raw
    // Recibe array JSON de PRs asociados al commit
    // ============================================
    public function captureGithubPRRaw(Request $request)
    {
        try {
            $prs = $request->all();

            // La API de GitHub devuelve un array de PRs
            // Si no hay PRs asociados al commit, el array llega vacío
            if (empty($prs)) {
                return response()->json([
                    'status'  => 'no_prs_found',
                    'message' => 'No pull requests associated with this commit',
                ], 200);
            }

            $saved = [];
            foreach ($prs as $pr) {
                // Calcular tiempo de review si el PR está mergeado
                $reviewMinutes = null;
                if (!empty($pr['created_at']) && !empty($pr['merged_at'])) {
                    $reviewMinutes = Carbon::parse($pr['created_at'])
                        ->diffInMinutes(Carbon::parse($pr['merged_at']));
                }

                $metric = Metric::create([
                    'type'      => 'github_pr',
                    'tool'      => 'github',
                    'data'      => [
                        'pr_number'      => $pr['number'] ?? null,
                        'title'          => $pr['title'] ?? null,
                        'state'          => $pr['state'] ?? null,
                        'branch'         => $pr['head']['ref'] ?? null,
                        'base_branch'    => $pr['base']['ref'] ?? null,
                        'author'         => $pr['user']['login'] ?? null,
                        'created_at'     => isset($pr['created_at'])
                                            ? date('Y-m-d H:i:s', strtotime($pr['created_at']))
                                            : null,
                        'merged_at'      => isset($pr['merged_at'])
                                            ? date('Y-m-d H:i:s', strtotime($pr['merged_at']))
                                            : null,
                        'review_minutes' => $reviewMinutes,  // tiempo de code review
                        'raw'            => $pr,
                    ],
                    'timestamp' => now(),
                ]);
                $saved[] = $metric->id;
            }

            return response()->json([
                'status'     => 'recorded',
                'prs_saved'  => count($saved),
                'metric_ids' => $saved,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to capture GitHub PR data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // GITHUB — Commit
    // POST /api/metrics/github-commit
    // ============================================
    public function captureGithubCommit(Request $request)
    {
        try {
            $data = $request->all();

            $metric = Metric::create([
                'type'      => 'github_commit',
                'tool'      => 'github',
                'data'      => $data,
                'timestamp' => $data['timestamp'] ?? now(),
            ]);

            return response()->json([
                'status' => 'recorded',
                'type'   => 'github_commit',
                'id'     => $metric->id,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to capture GitHub commit',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // JIRA — Sprint
    // POST /api/metrics/jira-sprint
    // ============================================
    public function recordJiraSprint(Request $request)
    {
        try {
            $metric = Metric::create([
                'type'      => 'jira-sprint',
                'tool'      => $request->input('tool', 'jira'),
                'data'      => $request->all(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'status'      => 'recorded',
                'id'          => $metric->id,
                'type'        => 'jira-sprint',
                'sprint_name' => $request->input('sprint_name'),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to record Jira sprint',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // JIRA — Stats consolidados por issue
    // GET /api/metrics/jira-stats
    // ============================================
    public function getJiraCommitStats()
    {
        try {
            $stats = Metric::where('type', 'jira-issue')
                ->get()
                ->map(function ($metric) {
                    $content = is_array($metric->data)
                               ? $metric->data
                               : json_decode($metric->data, true);

                    if (is_string($content)) {
                        $content = json_decode($content, true);
                    }

                    // Normalizar: Jenkins envuelve en 'data'
                    $innerData = (isset($content['data']) && is_array($content['data']))
                                 ? $content['data']
                                 : $content;

                    return [
                        'issue_key'   => $innerData['issue_key'] ?? null,
                        'assignee'    => $innerData['assignee'] ?? null,
                        'activity_at' => $metric->timestamp ?? $metric->created_at,
                    ];
                })
                ->filter(fn($item) => !empty($item['issue_key']))
                ->groupBy('issue_key')
                ->map(function ($group, $key) {
                    return [
                        'issue_key'              => $key,
                        'total_commits_detected' => $group->count(),
                        'last_activity'          => $group->max('activity_at'),
                        'developers'             => $group->pluck('assignee')
                                                        ->filter()
                                                        ->unique()
                                                        ->values(),
                    ];
                })
                ->values();

            return response()->json(['status' => 'success', 'data' => $stats]);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // JIRA — Issues relacionados al commit
    // POST /api/metrics/jira-issues/related
    // ============================================
    public function getRelatedJiraIssues(Request $request)
    {
        try {
            preg_match_all('/([A-Z]+-\d+)/', $request->input('commit_message', ''), $m1);
            preg_match_all('/([A-Z]+-\d+)/', $request->input('branch_name', ''), $m2);
            $allKeys = array_unique(array_merge($m1[1] ?? [], $m2[1] ?? []));

            if (empty($allKeys)) {
                return response()->json(['status' => 'no_issues_found'], 200);
            }

            $localIssues = DB::table('metrics')
                ->where('type', 'jira-issue')
                ->get()
                ->filter(function ($metric) use ($allKeys) {
                    $data = json_decode($metric->data, true);
                    $inner = isset($data['data']) ? $data['data'] : $data;
                    return in_array($inner['issue_key'] ?? null, $allKeys);
                })
                ->map(fn($m) => json_decode($m->data, true))
                ->values();

            return response()->json([
                'status'          => 'found',
                'issue_keys_found'=> $allKeys,
                'issues_from_db'  => $localIssues,
                'count'           => count($localIssues),
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // JIRA — Resumen de velocidad / burndown
    // GET /api/metrics/jira-summary
    // ============================================
    public function getJiraSummary(Request $request)
    {
        $days = (int) $request->get('days', 30);

        try {
            $issues = DB::table('metrics')
                ->where('type', 'jira-issue')
                ->where('timestamp', '>=', now()->subDays($days))
                ->get()
                ->map(function ($metric) {
                    $data = json_decode($metric->data, true);
                    return isset($data['data']) ? $data['data'] : $data;
                });

            $total     = $issues->count();
            $completed = $issues->filter(fn($i) => ($i['status'] ?? '') === 'Done')->count();
            $inProg    = $issues->filter(fn($i) => ($i['status'] ?? '') === 'In Progress')->count();
            $todo      = $issues->filter(fn($i) => ($i['status'] ?? '') === 'To Do')->count();

            $totalSP     = $issues->sum(fn($i) => $i['story_points'] ?? 0);
            $completedSP = $issues->filter(fn($i) => ($i['status'] ?? '') === 'Done')
                                  ->sum(fn($i) => $i['story_points'] ?? 0);

            $leadTimes = $issues
                ->filter(fn($i) => !empty($i['completed_at']) && !empty($i['created_at']))
                ->map(function ($i) {
                    return (new \DateTime($i['completed_at']))
                        ->diff(new \DateTime($i['created_at']))->days;
                });

            return response()->json([
                'period_days'  => $days,
                'issues'       => [
                    'total'                   => $total,
                    'completed'               => $completed,
                    'in_progress'             => $inProg,
                    'todo'                    => $todo,
                    'completion_rate_percent' => $total > 0
                        ? round(($completed / $total) * 100, 2) : 0,
                ],
                'story_points' => [
                    'total'              => $totalSP,
                    'completed'          => $completedSP,
                    'pending'            => $totalSP - $completedSP,
                    'completion_percent' => $totalSP > 0
                        ? round(($completedSP / $totalSP) * 100, 2) : 0,
                ],
                'velocity'     => [
                    'story_points_per_day' => $days > 0 ? round($completedSP / $days, 2) : 0,
                    'issues_per_day'       => $days > 0 ? round($total / $days, 2) : 0,
                ],
                'lead_time'    => [
                    'average_days'   => $leadTimes->isNotEmpty() ? round($leadTimes->avg(), 2) : 0,
                    'total_measured' => $leadTimes->count(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // PROMETHEUS — Formato para Grafana scraping
    // GET /api/metrics/prometheus
    // ============================================
    public function prometheusMetrics()
    {
        $tools  = ['github-actions', 'jenkins'];
        $output = '';

        foreach ($tools as $tool) {
            $metrics = $this->calculateMetricsForTool($tool, 30);

            $output .= "# HELP dora_deployment_frequency Frecuencia de despliegue por dia\n";
            $output .= "# TYPE dora_deployment_frequency gauge\n";
            $output .= "dora_deployment_frequency{tool=\"{$tool}\"} {$metrics['deployment_frequency']['value']}\n\n";

            $output .= "# HELP dora_lead_time_hours Tiempo promedio de cambios en horas\n";
            $output .= "# TYPE dora_lead_time_hours gauge\n";
            $output .= "dora_lead_time_hours{tool=\"{$tool}\"} {$metrics['lead_time']['hours']}\n\n";

            $output .= "# HELP dora_change_failure_rate Porcentaje de fallos en cambios\n";
            $output .= "# TYPE dora_change_failure_rate gauge\n";
            $output .= "dora_change_failure_rate{tool=\"{$tool}\"} {$metrics['change_failure_rate']['percentage']}\n\n";

            $output .= "# HELP dora_mttr_hours Tiempo medio de recuperacion en horas\n";
            $output .= "# TYPE dora_mttr_hours gauge\n";
            $output .= "dora_mttr_hours{tool=\"{$tool}\"} {$metrics['mttr']['hours']}\n\n";
        }

        return response($output)->header('Content-Type', 'text/plain; version=0.0.4');
    }

    // ============================================
    // ALMACENAR MÉTRICAS GENÉRICAS
    // POST /api/metrics/{type}
    // type: deployment | leadtime | deployment-result | incident
    // ============================================
    public function store(Request $request, string $type)
    {
        try {
            $data = $request->all();

            // Para leadtime calculamos segundos si vienen timestamps
            if ($type === 'leadtime') {
                $now = Carbon::now();
                if ($request->filled('commit_at')) {
                    $data['lead_time_seconds'] = $now->diffInSeconds(Carbon::parse($request->commit_at));
                }
                if ($request->filled('jira_created_at')) {
                    $data['business_lead_time_seconds'] = $now->diffInSeconds(Carbon::parse($request->jira_created_at));
                }
            }

            $metric = Metric::create([
                'type'      => $type,
                'tool'      => $request->input('tool'),
                'data'      => $data,
                'timestamp' => $request->input('timestamp') ?? now(),
            ]);

            return response()->json([
                'status' => 'recorded',
                'id'     => $metric->id,
                'type'   => $type,
                'tool'   => $request->input('tool'),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to store metric',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // RESOLVER INCIDENTE (MTTR)
    // POST /api/metrics/incident/resolve
    // ============================================
    public function resolveIncident(Request $request)
    {
        try {
            $resolutionTime = $request->input('resolution_time');

            if (!$resolutionTime) {
                return response()->json([
                    'error' => 'resolution_time is required',
                ], 422);
            }

            // Buscar el último incidente abierto para esta herramienta
            $incident = Metric::where('type', 'incident')
                ->where('tool', $request->input('tool'))
                ->whereNull('data->resolution_time')
                ->latest('timestamp')
                ->first();

            if (!$incident) {
                return response()->json([
                    'status'  => 'no_open_incident',
                    'message' => 'No open incident found to resolve',
                ], 404);
            }

            $data                    = $incident->data;
            $data['resolution_time'] = $resolutionTime;
            $data['status']          = 'resolved';
            $incident->update(['data' => $data]);

            $mttrSeconds = Carbon::parse($incident->data['start_time'])
                ->diffInSeconds(Carbon::parse($resolutionTime));

            return response()->json([
                'status'       => 'resolved',
                'incident_id'  => $incident->id,
                'mttr_seconds' => $mttrSeconds,
                'mttr_minutes' => round($mttrSeconds / 60, 2),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to resolve incident',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MÉTRICAS DORA DE UNA HERRAMIENTA
    // GET /api/metrics/dora?tool=jenkins&period=30
    // ============================================
    public function getDORAMetrics(Request $request)
    {
        $tool   = $request->query('tool');
        $period = (int) $request->query('period', 30);

        if (!$tool || !in_array($tool, ['github-actions', 'jenkins'])) {
            return response()->json([
                'error' => 'Invalid tool. Must be: github-actions or jenkins',
            ], 400);
        }

        $startDate = now()->subDays($period);

        try {
            $deploymentsCount  = Metric::ofType('deployment')->forTool($tool)
                                       ->where('timestamp', '>=', $startDate)->count();
            $deploymentFreq    = $period > 0 ? $deploymentsCount / $period : 0;

            $avgLeadTime       = Metric::ofType('leadtime')->forTool($tool)
                                       ->where('timestamp', '>=', $startDate)
                                       ->avg('data->lead_time_seconds');

            $totalDeploys      = Metric::ofType('deployment-result')->forTool($tool)
                                       ->where('timestamp', '>=', $startDate)->count();
            $failedDeploys     = Metric::ofType('deployment-result')->forTool($tool)
                                       ->where('data->is_failure', true)
                                       ->where('timestamp', '>=', $startDate)->count();
            $cfr               = $totalDeploys > 0
                                 ? ($failedDeploys / $totalDeploys) * 100 : 0;

            $resolvedIncidents = Metric::ofType('incident')->forTool($tool)
                                       ->whereNotNull('data->resolution_time')
                                       ->where('timestamp', '>=', $startDate)->get();
            $mttr              = $resolvedIncidents->map(function ($i) {
                return Carbon::parse($i->data['start_time'])
                    ->diffInSeconds(Carbon::parse($i->data['resolution_time']));
            })->avg() ?? 0;

            return response()->json([
                'tool'         => $tool,
                'period_days'  => $period,
                'date_range'   => [
                    'from' => $startDate->toDateTimeString(),
                    'to'   => now()->toDateTimeString(),
                ],
                'metrics'      => [
                    'deployment_frequency'  => [
                        'value'  => round($deploymentFreq, 2),
                        'unit'   => 'deployments/day',
                        'count'  => $deploymentsCount,
                        'rating' => $this->rateDeploymentFrequency($deploymentFreq),
                    ],
                    'lead_time_for_changes' => [
                        'value_seconds' => round($avgLeadTime ?? 0, 2),
                        'value_minutes' => round(($avgLeadTime ?? 0) / 60, 2),
                        'value_hours'   => round(($avgLeadTime ?? 0) / 3600, 2),
                        'unit'          => 'hours',
                        'rating'        => $this->rateLeadTime($avgLeadTime ?? 0),
                    ],
                    'change_failure_rate'   => [
                        'value'              => round($cfr, 2),
                        'unit'               => '%',
                        'total_deployments'  => $totalDeploys,
                        'failed_deployments' => $failedDeploys,
                        'rating'             => $this->rateChangeFailureRate($cfr),
                    ],
                    'mean_time_to_recovery' => [
                        'value_seconds'     => round($mttr, 2),
                        'value_minutes'     => round($mttr / 60, 2),
                        'value_hours'       => round($mttr / 3600, 2),
                        'unit'              => 'hours',
                        'incidents_resolved'=> $resolvedIncidents->count(),
                        'rating'            => $this->rateMTTR($mttr),
                    ],
                ],
                'overall_rating' => $this->calculateOverallRating([
                    $this->rateDeploymentFrequency($deploymentFreq),
                    $this->rateLeadTime($avgLeadTime ?? 0),
                    $this->rateChangeFailureRate($cfr),
                    $this->rateMTTR($mttr),
                ]),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to calculate DORA metrics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // COMPARACIÓN ENTRE STACKS
    // GET /api/metrics/comparison?period=30
    // ============================================
    public function getComparison(Request $request)
    {
        $period = (int) $request->query('period', 30);

        try {
            $githubMetrics  = $this->calculateMetricsForTool('github-actions', $period);
            $jenkinsMetrics = $this->calculateMetricsForTool('jenkins', $period);

            return response()->json([
                'period_days' => $period,
                'comparison'  => [
                    'github-actions' => $githubMetrics,
                    'jenkins'        => $jenkinsMetrics,
                ],
                'winner'      => $this->determineWinner($githubMetrics, $jenkinsMetrics),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to generate comparison',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MÉTODOS PRIVADOS — Ratings DORA
    // ============================================

    private function rateDeploymentFrequency(float $f): string
    {
        if ($f >= 1)    return 'Elite';
        if ($f >= 0.14) return 'High';
        if ($f >= 0.03) return 'Medium';
        return 'Low';
    }

    private function rateLeadTime(float $seconds): string
    {
        $h = $seconds / 3600;
        if ($h < 24)  return 'Elite';
        if ($h < 168) return 'High';
        if ($h < 720) return 'Medium';
        return 'Low';
    }

    private function rateChangeFailureRate(float $rate): string
    {
        if ($rate <= 15) return 'Elite';
        if ($rate <= 30) return 'High';
        if ($rate <= 45) return 'Medium';
        return 'Low';
    }

    private function rateMTTR(float $seconds): string
    {
        $h = $seconds / 3600;
        if ($h < 1)   return 'Elite';
        if ($h < 24)  return 'High';
        if ($h < 168) return 'Medium';
        return 'Low';
    }

    private function calculateOverallRating(array $ratings): string
    {
        $scores = ['Elite' => 4, 'High' => 3, 'Medium' => 2, 'Low' => 1];
        $avg    = array_sum(array_map(fn($r) => $scores[$r] ?? 0, $ratings)) / count($ratings);

        if ($avg >= 3.5) return 'Elite';
        if ($avg >= 2.5) return 'High';
        if ($avg >= 1.5) return 'Medium';
        return 'Low';
    }

    private function calculateMetricsForTool(string $tool, int $period): array
    {
        $startDate = now()->subDays($period);

        $deploymentsCount = Metric::ofType('deployment')->forTool($tool)
                                  ->where('timestamp', '>=', $startDate)->count();
        $deploymentFreq   = $period > 0 ? $deploymentsCount / $period : 0;

        $avgLeadTime      = Metric::ofType('leadtime')->forTool($tool)
                                  ->where('timestamp', '>=', $startDate)
                                  ->avg('data->lead_time_seconds') ?? 0;

        $totalDeploys     = Metric::ofType('deployment-result')->forTool($tool)
                                  ->where('timestamp', '>=', $startDate)->count();
        $failedDeploys    = Metric::ofType('deployment-result')->forTool($tool)
                                  ->where('data->is_failure', true)
                                  ->where('timestamp', '>=', $startDate)->count();
        $cfr              = $totalDeploys > 0
                            ? ($failedDeploys / $totalDeploys) * 100 : 0;

        $resolvedIncidents = Metric::ofType('incident')->forTool($tool)
                                   ->whereNotNull('data->resolution_time')
                                   ->where('timestamp', '>=', $startDate)->get();
        $mttr              = $resolvedIncidents->map(function ($i) {
            return Carbon::parse($i->data['start_time'])
                ->diffInSeconds(Carbon::parse($i->data['resolution_time']));
        })->avg() ?? 0;

        return [
            'deployment_frequency' => [
                'value'  => round($deploymentFreq, 2),
                'rating' => $this->rateDeploymentFrequency($deploymentFreq),
            ],
            'lead_time'            => [
                'hours'  => round($avgLeadTime / 3600, 2),
                'rating' => $this->rateLeadTime($avgLeadTime),
            ],
            'change_failure_rate'  => [
                'percentage' => round($cfr, 2),
                'rating'     => $this->rateChangeFailureRate($cfr),
            ],
            'mttr'                 => [
                'hours'  => round($mttr / 3600, 2),
                'rating' => $this->rateMTTR($mttr),
            ],
            'overall_rating'       => $this->calculateOverallRating([
                $this->rateDeploymentFrequency($deploymentFreq),
                $this->rateLeadTime($avgLeadTime),
                $this->rateChangeFailureRate($cfr),
                $this->rateMTTR($mttr),
            ]),
        ];
    }

    private function determineWinner(array $github, array $jenkins): array
    {
        $scores = ['Elite' => 4, 'High' => 3, 'Medium' => 2, 'Low' => 1];
        $gScore = $scores[$github['overall_rating']]  ?? 0;
        $jScore = $scores[$jenkins['overall_rating']] ?? 0;

        if ($gScore > $jScore) return ['tool' => 'github-actions', 'reason' => 'Better overall DORA performance'];
        if ($jScore > $gScore) return ['tool' => 'jenkins',        'reason' => 'Better overall DORA performance'];
        return ['tool' => 'tie', 'reason' => 'Both tools have equal DORA performance'];
    }
}