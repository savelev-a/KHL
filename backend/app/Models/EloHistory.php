<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloHistory extends Model
{
    protected $table = 'elo_history';

    // История неизменяема, updated_at не нужен
    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'match_id',
        'rating_before',
        'rating_after',
        'created_at',
    ];

    protected $casts = [
        'rating_before' => 'decimal:2',
        'rating_after'  => 'decimal:2',
        'created_at'    => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'match_id');
    }
}