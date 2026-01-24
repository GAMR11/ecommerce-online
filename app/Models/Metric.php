<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Metric extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'type',
        'tool',
        'data',
        'timestamp',
    ];

    /**
     * Los atributos que deben ser casteados.
     */
    protected $casts = [
        'data' => 'array',  // Importante: convierte JSON a array automáticamente
        'timestamp' => 'datetime',
    ];

    /**
     * Scopes para queries comunes
     */

    // Filtrar por tipo de métrica
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // Filtrar por herramienta
    public function scopeForTool(Builder $query, string $tool): Builder
    {
        return $query->where('tool', $tool);
    }

    // Filtrar por rango de fechas
    public function scopeInPeriod(Builder $query, int $days): Builder
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }

    // Filtrar desde una fecha específica
    public function scopeSince(Builder $query, Carbon $date): Builder
    {
        return $query->where('timestamp', '>=', $date);
    }

    // Filtrar entre fechas
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }

    /**
     * Accesores para datos específicos del JSON
     */

    // Obtener el lead time en segundos (si existe)
    public function getLeadTimeSecondsAttribute(): ?int
    {
        return $this->data['lead_time_seconds'] ?? null;
    }

    // Obtener si fue un fallo (para deployment-result)
    public function getIsFailureAttribute(): bool
    {
        return $this->data['is_failure'] ?? false;
    }

    // Obtener el commit hash
    public function getCommitAttribute(): ?string
    {
        return $this->data['commit'] ?? null;
    }

    // Obtener el tiempo de resolución (para incidents)
    public function getResolutionTimeAttribute(): ?Carbon
    {
        if (isset($this->data['resolution_time'])) {
            return Carbon::parse($this->data['resolution_time']);
        }
        return null;
    }

    /**
     * Métodos helper para crear métricas específicas
     */

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
