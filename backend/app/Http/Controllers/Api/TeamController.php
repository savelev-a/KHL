<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function show(int $id, Request $request)
    {
        $team = Team::with([
            'standings' => fn ($q) => $q->whereHas('season', fn ($q2) => $q2->where('is_current', true)),
            'eloHistory' => fn ($q) => $q->orderBy('created_at'),
        ])->findOrFail($id);

        return response()->json([
            'id'              => $team->id,
            'name'            => $team->name,
            'city'            => $team->city,
            'logo_url'        => $team->logo_url,
            'conference_name' => $team->conference_name,
            'division_name'   => $team->division_name,
            'elo_rating'      => (float) $team->elo_rating,
            'standing'        => $team->standings->first(),
            'elo_history'     => $team->eloHistory->map(fn ($h) => [
                'match_id'      => $h->match_id,
                'rating_before' => (float) $h->rating_before,
                'rating_after'  => (float) $h->rating_after,
                'created_at'    => $h->created_at->toIso8601String(),
            ]),
        ]);
    }
}