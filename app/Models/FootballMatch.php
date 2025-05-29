<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FootballMatch extends Model
{
    use HasUuids;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'phase_id',
        'round',
        'group_number',
        'match_type',
        'home_team',
        'away_team',
        'match_date',
        'venue',
        'stream_url',
        'status',
        'home_score',
        'away_score'
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'round' => 'integer',
        'group_number' => 'integer'
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'phase_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(Bracket::class, 'match_id');
    }
}
