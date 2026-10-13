<?php

namespace App\Services\Khl;

use App\Models\Game;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StandingsRebuilder
{
    public function __construct(
        private readonly EloCalculator $elo,
    ) {}

    /**
     * Полный пересчёт Эло + standings для сезона.
     *
     * @return array{season_id:int, games:int, teams:int, duration:float}
     */
    public function rebuild(Season $season): array
    {
        $startedAt = microtime(true);

        // 1) Берём все завершённые матчи в хронологическом порядке.
        //    live игнорируем — считаем, что он не закончился.
        $games = Game::query()
            ->where('season_id', $season->id)
            ->where('status', 'finished')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        if ($games->isEmpty()) {
            Log::channel('khl')->warning('Rebuild: no finished games', [
                'season_id' => $season->id,
            ]);
            return [
                'season_id' => $season->id,
                'games'     => 0,
                'teams'     => 0,
                'duration'  => round(microtime(true) - $startedAt, 2),
            ];
        }

        // 2) Все команды начинают с 1500
        $teamIds = Team::query()->pluck('id')->all();
        $initial = array_fill_keys($teamIds, 1500.0);

        // 3) Прогон
        $result = $this->elo->run($games, $initial);

        // 4) Пишем в БД — одной транзакцией
        DB::transaction(function () use ($season, $result, $teamIds) {
            // 4.1. Очищаем старые history/standings для этого сезона
            Standing::where('season_id', $season->id)->delete();

            // История привязана к матчам сезона.
            // Проще: удалим историю для тех же команд + матчей этого сезона.
            DB::table('elo_history')
                ->whereIn('match_id', function ($q) use ($season) {
                    $q->select('id')->from('matches')
                      ->where('season_id', $season->id);
                })
                ->delete();

            // 4.2. Bulk insert истории
            $now = now();
            $historyRows = array_map(fn ($row) => $row + ['created_at' => $now], $result['history']);
            foreach (array_chunk($historyRows, 500) as $chunk) {
                DB::table('elo_history')->insert($chunk);
            }

            // 4.3. standings — но сначала позиции не заполнены
            $standings = $result['standings'];
            $rows = [];
            foreach ($standings as $teamId => $agg) {
                $rows[] = [
                    'team_id'       => $teamId,
                    'season_id'     => $season->id,
                    'games_played'  => $agg['games_played'],
                    'points'        => $agg['points'],
                    'wins'          => $agg['wins'],
                    'ot_wins'       => $agg['ot_wins'],
                    'losses'        => $agg['losses'],
                    'goals_for'     => $agg['goals_for'],
                    'goals_against' => $agg['goals_against'],
                    'position'      => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            Standing::insert($rows);

            // 4.4. Считаем позиции: очки → победы (рег+OT) → разница шайб
            $this->assignPositions($season->id);

            // 4.5. Обновляем денормализованный рейтинг на teams
            foreach ($result['final_ratings'] as $teamId => $rating) {
                Team::where('id', $teamId)->update(['elo_rating' => $rating]);
            }
        });

        $duration = round(microtime(true) - $startedAt, 2);

        Log::channel('khl')->info('Rebuild finished', [
            'season_id' => $season->id,
            'games'     => $games->count(),
            'teams'     => count($teamIds),
            'duration'  => $duration,
        ]);

        return [
            'season_id' => $season->id,
            'games'     => $games->count(),
            'teams'     => count($teamIds),
            'duration'  => $duration,
        ];
    }

    /**
     * Присваивает position по сортировке: points DESC, wins+ot_wins DESC,
     * (goals_for - goals_against) DESC.
     */
    private function assignPositions(int $seasonId): void
    {
        $rows = Standing::where('season_id', $seasonId)
            ->orderByDesc('points')
            ->orderByRaw('(wins + ot_wins) DESC')
            ->orderByRaw('(goals_for - goals_against) DESC')
            ->get();

        $position = 1;
        foreach ($rows as $row) {
            $row->position = $position++;
            $row->save();
        }
    }
}