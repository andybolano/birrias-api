<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'start_date',
        'inscription_fee_money',
        'currency',
        'owner',
        'status',
        'format',
        'groups',
        'teams_per_group',
        'playoff_size',
        'rounds',
        'home_away'
    ];

    protected $casts = [
        'start_date' => 'date',
        'inscription_fee_money' => 'decimal:2',
        'home_away' => 'boolean',
        'groups' => 'integer',
        'teams_per_group' => 'integer',
        'playoff_size' => 'integer',
        'rounds' => 'integer'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_team');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(Bracket::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(TournamentPhase::class);
    }
}
