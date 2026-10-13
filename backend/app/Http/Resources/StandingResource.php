<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StandingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'position'        => $this->position,
            'team' => [
                'id'              => $this->team->id,
                'name'            => $this->team->name,
                'city'            => $this->team->city,
                'logo_url'        => $this->team->logo_url,
                'conference_key'  => $this->team->conference_key,
                'conference_name' => $this->team->conference_name,
                'division_key'    => $this->team->division_key,
                'division_name'   => $this->team->division_name,
                'elo_rating'      => (float) $this->team->elo_rating,
            ],
            'games_played'    => $this->games_played,
            'points'          => $this->points,
            'wins'            => $this->wins,
            'ot_wins'         => $this->ot_wins,
            'losses'          => $this->losses,
            'goals_for'       => $this->goals_for,
            'goals_against'   => $this->goals_against,
            'goal_difference' => $this->goals_for - $this->goals_against,
            'playoff_probability' => $this->playoff_probability !== null
                ? round((float) $this->playoff_probability, 4)
                : null,
        ];
    }
}