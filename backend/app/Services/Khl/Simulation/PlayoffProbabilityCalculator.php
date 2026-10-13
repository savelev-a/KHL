<?php

namespace App\Services\Khl\Simulation;

use App\Models\PlayoffProbability;
use App\Models\Season;
use Illuminate\Support\Facades\DB;

class PlayoffProbabilityCalculator
{
    public function __construct(
        private readonly MonteCarloSimulator $simulator,
    ) {}

    public function calculate(Season $season, int $iterations = 10000): array
    {
        $result = $this->simulator->simulate($season, $iterations);
        $now = now();

        DB::transaction(function () use ($season, $result, $now) {
            // Обнуляем вероятности этого сезона
            PlayoffProbability::where('season_id', $season->id)->delete();

            $rows = [];
            foreach ($result['probabilities'] as $teamId => $prob) {
                $rows[] = [
                    'team_id'           => $teamId,
                    'season_id'         => $season->id,
                    'probability'       => round($prob, 4),
                    'simulations_count' => $result['simulations'],
                    'calculated_at'     => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }

            PlayoffProbability::insert($rows);
        });

        return $result;
    }
}