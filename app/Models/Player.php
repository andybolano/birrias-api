<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Player extends Model
{
    use HasUuids;

    protected $fillable = [
        'position',
        'jersey',
        'birthDay',
        'personId',
        'first_name',
        'last_name',
        'identification_number',
        'eps'
    ];

    protected $casts = [
        'birthDay' => 'date',
        'jersey' => 'integer'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(User::class, 'personId');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_player')
            ->using(TeamPlayer::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function matchEvents(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }

    /**
     * Check if player can be added to a team without violating tournament constraints
     * A player cannot be in multiple teams within the same tournament
     */
    public function canJoinTeam($teamId): array
    {
        // Get all tournaments where the target team participates
        $targetTeamTournaments = \DB::table('tournament_team')
            ->where('team_id', $teamId)
            ->pluck('tournament_id');

        if ($targetTeamTournaments->isEmpty()) {
            return ['can_join' => true, 'conflicts' => []];
        }

        // Get all teams where this player is currently assigned
        $playerTeams = $this->teams()->pluck('teams.id');

        if ($playerTeams->isEmpty()) {
            return ['can_join' => true, 'conflicts' => []];
        }

        // Check for tournament conflicts
        $conflicts = \DB::table('tournament_team')
            ->join('tournaments', 'tournament_team.tournament_id', '=', 'tournaments.id')
            ->join('teams', 'tournament_team.team_id', '=', 'teams.id')
            ->whereIn('tournament_team.team_id', $playerTeams)
            ->whereIn('tournament_team.tournament_id', $targetTeamTournaments)
            ->select('tournaments.name as tournament_name', 'teams.name as team_name', 'tournaments.id as tournament_id')
            ->get();

        return [
            'can_join' => $conflicts->isEmpty(),
            'conflicts' => $conflicts->toArray()
        ];
    }

    /**
     * Get tournaments where this player participates through their teams
     */
    public function tournaments()
    {
        return Tournament::whereHas('teams', function ($query) {
            $query->whereHas('players', function ($subQuery) {
                $subQuery->where('players.id', $this->id);
            });
        });
    }

    /**
     * Get the player's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
