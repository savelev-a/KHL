<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = [
        'khl_stage_id',
        'name',
        'is_current',
    ];

    protected $casts = [
        'khl_stage_id' => 'integer',
        'is_current'   => 'boolean',
    ];

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function probabilities(): HasMany
    {
        return $this->hasMany(PlayoffProbability::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}