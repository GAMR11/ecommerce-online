<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Metric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MetricasController extends Controller
{
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
            $metric = Metric::create([
                'type' => $type,
                'tool' => $request->tool,
                'data' => $request->all(),
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

            $recoveryTimes = $resolvedIncidents->map(function($incident) {
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

        $recoveryTimes = $resolvedIncidents->map(function($incident) {
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
