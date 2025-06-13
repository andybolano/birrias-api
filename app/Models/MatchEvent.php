<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    use HasUuids;

    const TYPE_GOAL = 'goal';
    const TYPE_YELLOW_CARD = 'yellow_card';
    const TYPE_RED_CARD = 'red_card';
    const TYPE_BLUE_CARD = 'blue_card';
    const TYPE_SUBSTITUTION = 'substitution';

    protected $fillable = [
        'match_id',
        'player_id',
        'substitute_player_id',
        'type',
        'minute',
        'description'
    ];

    protected $casts = [
        'minute' => 'integer'
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function substitutePlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'substitute_player_id');
    }

    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_GOAL,
            self::TYPE_YELLOW_CARD,
            self::TYPE_RED_CARD,
            self::TYPE_BLUE_CARD,
            self::TYPE_SUBSTITUTION
        ];
    }
}
