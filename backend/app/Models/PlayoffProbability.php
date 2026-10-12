<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayoffProbability extends Model
{
    protected $fillable = [
        'team_id',
        'season_id',
        'probability',
        'simulations_count',
        'calculated_at',
    ];

    protected $casts = [
        'probability'       => 'decimal:4',
        'simulations_count' => 'integer',
        'calculated_at'     => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}