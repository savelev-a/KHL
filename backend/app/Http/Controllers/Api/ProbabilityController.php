<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayoffProbability;
use App\Models\Season;
use Illuminate\Http\Request;

class ProbabilityController extends Controller
{
    public function index(Request $request)
    {
        $season = Season::current()->firstOrFail();

        $rows = PlayoffProbability::query()
            ->where('season_id', $season->id)
            ->get(['team_id', 'probability', 'simulations_count', 'calculated_at']);

        return response()->json([
            'season_id'      => $season->id,
            'calculated_at'  => $rows->max('calculated_at'),
            'simulations'    => $rows->max('simulations_count'),
            'data'           => $rows->map(fn ($r) => [
                'team_id'     => $r->team_id,
                'probability' => (float) $r->probability,
            ])->values(),
        ]);
    }
}