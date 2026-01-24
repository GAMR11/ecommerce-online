<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'tool',
        'data',
        'timestamp',
    ];

    protected $casts = [
        'data' => 'array',
        'timestamp' => 'datetime',
    ];

    // ============================================
    // SCOPES
    // ============================================

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForTool(Builder $query, string $tool): Builder
    {
        return $query->where('tool', $tool);
    }

    public function scopeInPeriod(Builder $query, int $days): Builder
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getLeadTimeSecondsAttribute(): ?int
    {
        return $this->data['lead_time_seconds'] ?? null;
    }

    public function getIsFailureAttribute(): bool
    {
        return $this->data['is_failure'] ?? false;
    }

    public function getCommitAttribute(): ?string
    {
        return $this->data['commit'] ?? null;
    }

    // ============================================
    // MÉTODOS ESTÁTICOS HELPER
    // ============================================

    public static function recordDeployment(string $tool, array $data): self
    {
        return self::create([
            'type' => 'deployment',
            'tool' => $tool,
            'data' => $data,
            'timestamp' => $data['timestamp'] ?? now(),
        ]);
    }

    public static function recordLeadTime(string $tool, array $data): self
    {
        return self::create([
            'type' => 'leadtime',
            'tool' => $tool,
            'data' => $data,
            'timestamp' => $data['timestamp'] ?? now(),
        ]);
    }

    public static function recordDeploymentResult(string $tool, array $data): self
    {
        return self::create([
            'type' => 'deployment-result',
            'tool' => $tool,
            'data' => $data,
            'timestamp' => $data['timestamp'] ?? now(),
        ]);
    }

    public static function recordIncident(string $tool, array $data): self
    {
        return self::create([
            'type' => 'incident',
            'tool' => $tool,
            'data' => $data,
            'timestamp' => $data['start_time'] ?? now(),
        ]);
    }
}
