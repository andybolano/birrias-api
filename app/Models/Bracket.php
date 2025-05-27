<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bracket extends Model
{
    use HasUuids;

    protected $fillable = [
        'tournament_id',
        'round',
        'match_id',
        'home_source',
        'away_source'
    ];

    protected $casts = [
        'round' => 'integer'
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }
}
