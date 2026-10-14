<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StandingResource;
use App\Models\Season;
use App\Models\Standing;
use Illuminate\Http\Request;

class StandingController extends Controller
{
    public function index(Request $request)
    {
        $season = Season::current()->firstOrFail();

        $standings = Standing::query()
            ->with('team')
            ->leftJoin('playoff_probabilities', function ($join) use ($season) {
                $join->on('standings.team_id', '=', 'playoff_probabilities.team_id')
                     ->where('playoff_probabilities.season_id', '=', $season->id);
            })
            ->where('standings.season_id', $season->id)
            ->orderBy('standings.position')
            ->select('standings.*', 'playoff_probabilities.probability as playoff_probability')
            ->get();

        return StandingResource::collection($standings);
    }
}