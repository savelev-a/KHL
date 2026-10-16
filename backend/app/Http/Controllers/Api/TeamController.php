<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function show(int $id, Request $request)
    {
        $season = Season::current()->firstOrFail();

        $team = Team::with([
            'standings'     => fn ($q) => $q->where('season_id', $season->id),
            'eloHistory'    => fn ($q) => $q->orderBy('created_at'),
            'probabilities' => fn ($q) => $q->where('season_id', $season->id),
        ])->findOrFail($id);

        $standing = $team->standings->first();

        if ($standing) {
            // В /standings playoff_probability приходит из JOIN-а (см. StandingController),
            // здесь наполняем то же поле вручную — чтобы контракт эндпоинтов совпадал.
            $probability = $team->probabilities->first()?->probability;

            $standing->setAttribute(
                'playoff_probability',
                $probability !== null ? round((float) $probability, 4) : null
            );
        }

        return response()->json([
            'id'              => $team->id,
            'name'            => $team->name,
            'city'            => $team->city,
            'logo_url'        => $team->logo_url,
            'conference_name' => $team->conference_name,
            'division_name'   => $team->division_name,
            'elo_rating'      => (float) $team->elo_rating,
            'standing'        => $standing,
            'elo_history'     => $team->eloHistory->map(fn ($h) => [
                'match_id'      => $h->match_id,
                'rating_before' => (float) $h->rating_before,
                'rating_after'  => (float) $h->rating_after,
                'created_at'    => $h->created_at->toIso8601String(),
            ]),
        ]);
    }
}