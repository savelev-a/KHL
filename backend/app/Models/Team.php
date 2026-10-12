<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'khl_id',
        'name',
        'city',
        'logo_url',
        'elo_rating',
    ];

    protected $casts = [
        'khl_id'     => 'integer',
        'elo_rating' => 'decimal:2',
    ];

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }

    public function eloHistory(): HasMany
    {
        return $this->hasMany(EloHistory::class)->orderBy('created_at');
    }

    public function probabilities(): HasMany
    {
        return $this->hasMany(PlayoffProbability::class);
    }
}