<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPhase extends Model
{
    use HasUuids;

    protected $fillable = [
        'tournament_id',
        'phase_order',
        'phase_type',
        'name',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'phase_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
