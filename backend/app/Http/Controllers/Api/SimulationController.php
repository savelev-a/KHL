<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayoffProbability;
use App\Models\Season;

class SimulationController extends Controller
{
    public function latest()
    {
        $season = Season::current()->firstOrFail();

        $latest = PlayoffProbability::query()
            ->where('season_id', $season->id)
            ->orderByDesc('calculated_at')
            ->first();

        if (!$latest) {
            return response()->json(['calculated_at' => null, 'simulations' => 0]);
        }

        return response()->json([
            'season_id'     => $season->id,
            'calculated_at' => $latest->calculated_at->toIso8601String(),
            'simulations'   => $latest->simulations_count,
            'age_seconds'   => now()->diffInSeconds($latest->calculated_at),
        ]);
    }
}