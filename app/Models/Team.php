<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'shield'
    ];

    /**
     * Get the shield URL attribute.
     */
    public function getShieldAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        return Storage::disk('public')->url($value);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'team_player')
            ->using(TeamPlayer::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team');
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_teams');
    }
}
