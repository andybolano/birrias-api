<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasUuids;

    protected $fillable = [
        'position',
        'jersey',
        'birthDay',
        'personId'
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
        return $this->belongsToMany(Team::class, 'team_player');
    }

    public function matchEvents(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }
}
