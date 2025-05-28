<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TeamPlayer extends Pivot
{
    use HasUuids;

    protected $table = 'team_player';

    public $incrementing = false;
    protected $keyType = 'string';
}
