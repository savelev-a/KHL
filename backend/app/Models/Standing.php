<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    protected $fillable = [
        'team_id',
        'season_id',
        'games_played',
        'points',
        'wins',
        'ot_wins',
        'losses',
        'goals_for',
        'goals_against',
        'position',
    ];

    protected $casts = [
        'games_played'  => 'integer',
        'points'        => 'integer',
        'wins'          => 'integer',
        'ot_wins'       => 'integer',
        'losses'        => 'integer',
        'goals_for'     => 'integer',
        'goals_against' => 'integer',
        'position'      => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function getGoalDifferenceAttribute(): int
    {
        return $this->goals_for - $this->goals_against;
    }
}