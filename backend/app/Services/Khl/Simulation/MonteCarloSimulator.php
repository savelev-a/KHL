<?php

namespace App\Services\Khl\Simulation;

use App\Models\Game;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use SplFixedArray;

class MonteCarloSimulator
{
    private const HOME_BONUS = 50.0;
    private const RANDOM_SCALE = 1_000_000;

    public function __construct(
        private readonly int $playoffSpots = 8,
        private readonly float $otProbability = 0.25,
    ) {}

    /**
     * @return array{
     *     probabilities: array<int,float>,
     *     simulations: int,
     *     games: int,
     *     duration: float
     * }
     */
    public function simulate(Season $season, int $iterations = 10000): array
    {
        $startedAt = microtime(true);

        // 1. Команды с конференциями
        $teams = Team::query()
            ->whereNotNull('conference_key')
            ->get(['id', 'elo_rating', 'conference_key']);

        if ($teams->isEmpty()) {
            throw new \RuntimeException(
                'Нет команд с заполненной конференцией. Запустите khl:sync-teams.'
            );
        }

        $teamIds   = $teams->pluck('id')->all();
        $teamCount = count($teamIds);
        $idToIndex = array_flip($teamIds);  // [team_id => 0..N-1]

        // 2. Рейтинги — SplFixedArray для чтения, это ок
        $ratings = new SplFixedArray($teamCount);
        foreach ($teams as $t) {
            $ratings[$idToIndex[$t->id]] = (float) $t->elo_rating;
        }

        // 3. Конференции — индексы команд
        $conferences = [];
        foreach ($teams as $t) {
            $conferences[$t->conference_key][] = $idToIndex[$t->id];
        }

        // 4. Базовая таблица — обычные массивы (числовые ключи, эффективно)
        $basePoints   = array_fill(0, $teamCount, 0);
        $baseWins     = array_fill(0, $teamCount, 0);
        $baseOtWins   = array_fill(0, $teamCount, 0);
        $baseGoalDiff = array_fill(0, $teamCount, 0);

        $standings = Standing::where('season_id', $season->id)->get();
        foreach ($standings as $s) {
            $idx = $idToIndex[$s->team_id] ?? null;
            if ($idx === null) continue;
            $basePoints[$idx]   = (int) $s->points;
            $baseWins[$idx]     = (int) $s->wins;
            $baseOtWins[$idx]   = (int) $s->ot_wins;
            $baseGoalDiff[$idx] = (int) ($s->goals_for - $s->goals_against);
        }

        // 5. Оставшиеся матчи
        $games = Game::query()
            ->where('season_id', $season->id)
            ->where('status', 'scheduled')
            ->get(['id', 'home_team_id', 'away_team_id']);

        $gamesCount = $games->count();

        // 6. Предвычисляем pHome и индексы команд для каждого матча
        //    Храним всё в обычных массивах — только читаем в цикле.
        $pHomes    = array_fill(0, $gamesCount, 0.5);
        $homeIdxs  = array_fill(0, $gamesCount, -1);
        $awayIdxs  = array_fill(0, $gamesCount, -1);

        $i = 0;
        foreach ($games as $g) {
            $hIdx = $idToIndex[$g->home_team_id] ?? -1;
            $aIdx = $idToIndex[$g->away_team_id] ?? -1;

            $homeIdxs[$i] = $hIdx;
            $awayIdxs[$i] = $aIdx;

            if ($hIdx >= 0 && $aIdx >= 0) {
                $rH = $ratings[$hIdx];
                $rA = $ratings[$aIdx];
                $pHomes[$i] = 1.0 / (1.0 + 10 ** (($rA - $rH - self::HOME_BONUS) / 400.0));
            }
            $i++;
        }

        // 7. Счётчик попаданий
        $hits = array_fill(0, $teamCount, 0);

        // 8. Основной цикл
        for ($iter = 0; $iter < $iterations; $iter++) {
            // Копирование обычных массивов по значению — быстро и безопасно
            $points   = $basePoints;
            $wins     = $baseWins;
            $otWins   = $baseOtWins;
            $goalDiff = $baseGoalDiff;

            // 8.1. Разыгрываем матчи
            for ($m = 0; $m < $gamesCount; $m++) {
                $hIdx = $homeIdxs[$m];
                $aIdx = $awayIdxs[$m];
                if ($hIdx < 0 || $aIdx < 0) continue;

                $p    = $pHomes[$m];
                $roll = random_int(0, self::RANDOM_SCALE) / self::RANDOM_SCALE;
                $isOt = random_int(0, self::RANDOM_SCALE) / self::RANDOM_SCALE < $this->otProbability;

                if ($roll < $p) {
                    // Победили хозяева
                    $points[$hIdx] += 2;
                    if ($isOt) {
                        $otWins[$hIdx] += 1;
                        $points[$aIdx] += 1;
                    } else {
                        $wins[$hIdx] += 1;
                    }
                    $goalDiff[$hIdx] += 1;
                    $goalDiff[$aIdx] -= 1;
                } else {
                    // Победили гости
                    $points[$aIdx] += 2;
                    if ($isOt) {
                        $otWins[$aIdx] += 1;
                        $points[$hIdx] += 1;
                    } else {
                        $wins[$aIdx] += 1;
                    }
                    $goalDiff[$aIdx] += 1;
                    $goalDiff[$hIdx] -= 1;
                }
            }

            // 8.2. Топ-N в каждой конференции
            foreach ($conferences as $confIndexes) {
                $ranked = [];
                foreach ($confIndexes as $idx) {
                    $ranked[] = [
                        'idx'       => $idx,
                        'points'    => $points[$idx],
                        'win_total' => $wins[$idx] + $otWins[$idx],
                        'goal_diff' => $goalDiff[$idx],
                    ];
                }

                usort($ranked, function ($a, $b) {
                    return [$b['points'], $b['win_total'], $b['goal_diff']]
                       <=> [$a['points'], $a['win_total'], $a['goal_diff']];
                });

                $spots = min($this->playoffSpots, count($ranked));
                for ($k = 0; $k < $spots; $k++) {
                    $hits[$ranked[$k]['idx']]++;
                }
            }
        }

        // 9. Вероятности
        $probabilities = [];
        foreach ($idToIndex as $teamId => $idx) {
            $probabilities[$teamId] = $hits[$idx] / $iterations;
        }

        $duration = round(microtime(true) - $startedAt, 3);

        Log::channel('khl')->info('Monte Carlo finished', [
            'season_id'   => $season->id,
            'iterations'  => $iterations,
            'games'       => $gamesCount,
            'teams'       => $teamCount,
            'duration'    => $duration,
        ]);

        return [
            'probabilities' => $probabilities,
            'simulations'   => $iterations,
            'games'         => $gamesCount,
            'duration'      => $duration,
        ];
    }
}