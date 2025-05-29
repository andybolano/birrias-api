<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentPhase extends Model
{
    use HasUuids;

    protected $fillable = [
        'tournament_id',
        'phase_number',
        'name',
        'type',
        'status',
        'home_away',
        'teams_advance',
        'groups_count',
        'teams_per_group',
        'order'
    ];

    protected $casts = [
        'phase_number' => 'integer',
        'home_away' => 'boolean',
        'teams_advance' => 'integer',
        'groups_count' => 'integer',
        'teams_per_group' => 'integer',
        'order' => 'integer'
    ];

    // Tipos de fase disponibles (MVP - Solo 3 tipos)
    const TYPE_ROUND_ROBIN = 'round_robin';
    const TYPE_SINGLE_ELIMINATION = 'single_elimination';
    const TYPE_GROUPS = 'groups';

    // Estados de fase disponibles
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Tipos disponibles como array
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_ROUND_ROBIN,
            self::TYPE_SINGLE_ELIMINATION,
            self::TYPE_GROUPS
        ];
    }

    // Estados disponibles como array
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'phase_id');
    }

    /**
     * Scope para obtener fases en orden
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('phase_number');
    }

    /**
     * Scope para obtener la fase activa
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener fases completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope para obtener fases pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para obtener fases no canceladas
     */
    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * Verifica si la fase requiere configuración de grupos
     */
    public function requiresGroups(): bool
    {
        return $this->type === self::TYPE_GROUPS;
    }

    /**
     * Verifica si la fase soporta ida y vuelta
     */
    public function supportsHomeAway(): bool
    {
        return in_array($this->type, [
            self::TYPE_ROUND_ROBIN,
            self::TYPE_SINGLE_ELIMINATION
        ]);
    }

    /**
     * Verifica si la fase está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica si la fase está completada
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verifica si la fase está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica si la fase está cancelada
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Verifica si la fase puede ser iniciada
     */
    public function canBeStarted(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica si la fase puede ser completada
     */
    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica si la fase puede ser cancelada
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE]);
    }

    /**
     * Inicia la fase (cambia status a active)
     */
    public function start(): bool
    {
        if (!$this->canBeStarted()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Completa la fase (cambia status a completed)
     */
    public function complete(): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Cancela la fase (cambia status a cancelled)
     */
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Obtiene el progreso de la fase basado en partidos
     */
    public function getProgress(): array
    {
        $totalMatches = $this->matches()->count();
        $completedMatches = $this->matches()->where('status', 'finished')->count();
        $scheduledMatches = $this->matches()->where('status', 'scheduled')->count();
        $inProgressMatches = $this->matches()->where('status', 'in_progress')->count();

        return [
            'total_matches' => $totalMatches,
            'completed_matches' => $completedMatches,
            'scheduled_matches' => $scheduledMatches,
            'in_progress_matches' => $inProgressMatches,
            'completion_percentage' => $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 2) : 0
        ];
    }

    /**
     * Verifica si la fase debería completarse automáticamente
     */
    public function shouldAutoComplete(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $progress = $this->getProgress();
        return $progress['total_matches'] > 0 && $progress['completed_matches'] === $progress['total_matches'];
    }
}
