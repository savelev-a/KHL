<?php

namespace App\Services\Khl;

class EloCalculator
{
    private const INITIAL_RATING = 1500.0;
    private const K_FACTOR       = 20.0;
    private const HOME_BONUS     = 50.0;

    /**
     * Прогоняет все матчи по хронологии.
     *
     * @param  iterable  $games  коллекция моделей Game (уже отсортированная по starts_at ASC),
     *                           с загруженными homeTeam/awayTeam (или без — используем *_team_id)
     * @param  array<int,float>  $initialRatings  карта [team_id => начальный рейтинг].
     *                                            Если команды нет — берём INITIAL_RATING.
     *
     * @return array{
     *   final_ratings: array<int,float>,
     *   history: array<int, array>,
     *   standings: array<int, array>
     * }
     */
    public function run(iterable $games, array $initialRatings = []): array
    {
        // Текущие рейтинги
        $ratings = $initialRatings;
        $history = [];   // строки для elo_history
        $agg     = [];   // агрегаты по каждой команде для standings

        foreach ($games as $game) {
            $homeId = (int) $game->home_team_id;
            $awayId = (int) $game->away_team_id;

            $rHome = $ratings[$homeId] ?? self::INITIAL_RATING;
            $rAway = $ratings[$awayId] ?? self::INITIAL_RATING;

            // Результат матча по итогам (включая ОТ/буллиты)
            $homeWon = $game->home_score > $game->away_score;

            // Вероятность победы хозяев
            $pHome = 1.0 / (1.0 + 10 ** (($rAway - $rHome - self::HOME_BONUS) / 400.0));

            // Обновление Эло
            $sHome = $homeWon ? 1.0 : 0.0;
            $sAway = 1.0 - $sHome;

            $newHome = $rHome + self::K_FACTOR * ($sHome - $pHome);
            $newAway = $rAway + self::K_FACTOR * ($sAway - (1.0 - $pHome));

            $history[] = [
                'team_id'       => $homeId,
                'match_id'      => $game->id,
                'rating_before' => round($rHome, 2),
                'rating_after'  => round($newHome, 2),
            ];
            $history[] = [
                'team_id'       => $awayId,
                'match_id'      => $game->id,
                'rating_before' => round($rAway, 2),
                'rating_after'  => round($newAway, 2),
            ];

            $ratings[$homeId] = $newHome;
            $ratings[$awayId] = $newAway;

            // Агрегаты для таблицы
            $this->accumulateStandings($agg, $game, $homeWon);
        }

        // Досоздаём агрегаты для команд, которые не сыграли ни одного матча
        foreach ($ratings as $teamId => $rating) {
            if (!isset($agg[$teamId])) {
                $agg[$teamId] = $this->emptyAggregate();
            }
        }

        return [
            'final_ratings' => array_map(fn ($v) => round($v, 2), $ratings),
            'history'       => $history,
            'standings'     => $agg,
        ];
    }

    /**
     * Накапливает статистику матча в агрегат для standings.
     */
    private function accumulateStandings(array &$agg, $game, bool $homeWon): void
    {
        $homeId = (int) $game->home_team_id;
        $awayId = (int) $game->away_team_id;

        $agg[$homeId] ??= $this->emptyAggregate();
        $agg[$awayId] ??= $this->emptyAggregate();

        $agg[$homeId]['games_played']++;
        $agg[$awayId]['games_played']++;

        $agg[$homeId]['goals_for']     += $game->home_score;
        $agg[$homeId]['goals_against'] += $game->away_score;
        $agg[$awayId]['goals_for']     += $game->away_score;
        $agg[$awayId]['goals_against'] += $game->home_score;

        // Определяем тип исхода: OT/SO или основное время.


        if ($homeWon) {
            $agg[$homeId]['points'] += 2;
            if ($this->isOvertime($game)) {
                $agg[$homeId]['ot_wins']++;
                $agg[$awayId]['points'] += 1;
                $agg[$awayId]['losses']++;
            } else {
                $agg[$homeId]['wins']++;
                $agg[$awayId]['losses']++;
            }
        } else {
            $agg[$awayId]['points'] += 2;
            if ($this->isOvertime($game)) {
                $agg[$awayId]['ot_wins']++;
                $agg[$homeId]['points'] += 1;
                $agg[$homeId]['losses']++;
            } else {
                $agg[$awayId]['wins']++;
                $agg[$homeId]['losses']++;
            }
        }
    }

    private function isOvertime($game): bool
    {
        return in_array($game->outcome, ['overtime', 'shootout'], true);
    }

    private function emptyAggregate(): array
    {
        return [
            'games_played'  => 0,
            'points'        => 0,
            'wins'          => 0,
            'ot_wins'       => 0,
            'losses'        => 0,
            'goals_for'     => 0,
            'goals_against' => 0,
        ];
    }
}