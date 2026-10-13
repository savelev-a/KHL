<?php

namespace App\Services\Khl;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleSyncService
{
    public function __construct(
        private readonly KhlApiClient $api,
        private readonly KhlDataMapper $mapper,
    ) {}

    /**
     * @param  int|null  $seasonId  если null — берём текущий сезон
     * @return array{season_id:int, created:int, updated:int, total:int}
     */
    public function sync(?int $seasonId = null): array
    {
        $season = $seasonId !== null
            ? Season::findOrFail($seasonId)
            : Season::current()->first();

        if (!$season) {
            throw new \RuntimeException(
                'Нет текущего сезона. Сначала выполните khl:sync-teams.'
            );
        }

        // Карта [khl_id команды => id в БД] — нужна мапперу
        $teamIdByKhlId = Team::query()
            ->pluck('id', 'khl_id')
            ->all();

        if ($teamIdByKhlId === []) {
            throw new \RuntimeException(
                'В БД нет ни одной команды. Сначала выполните khl:sync-teams.'
            );
        }

        $raw      = $this->api->get('/events_v2.json', ['stage_id' => $season->khl_stage_id]);
        $events   = $this->mapper->mapSchedule($raw, $season->id, $teamIdByKhlId);

        if ($events === []) {
            Log::channel('khl')->warning('Schedule sync: no events mapped', [
                'season_id' => $season->id,
            ]);
            return [
                'season_id' => $season->id,
                'created'   => 0,
                'updated'   => 0,
                'total'     => 0,
            ];
        }

        return DB::transaction(function () use ($season, $events) {
            $created = 0;
            $updated = 0;

            foreach ($events as $row) {
                $existing = Game::where('khl_id', $row['khl_id'])->first();

                if ($existing) {
                    $existing->fill([
                        'season_id'    => $row['season_id'],
                        'home_team_id' => $row['home_team_id'],
                        'away_team_id' => $row['away_team_id'],
                        'starts_at'    => $row['starts_at'],
                        // Счёт обновляем только если API его отдал —
                        // иначе не затираем ранее записанный.
                        'home_score'   => $row['home_score'] ?? $existing->home_score,
                        'away_score'   => $row['away_score'] ?? $existing->away_score,
                        'status'       => $row['status'],
                    ])->save();
                    $updated++;
                } else {
                    Game::create($row);
                    $created++;
                }
            }

            Log::channel('khl')->info('Schedule sync finished', [
                'season_id' => $season->id,
                'created'   => $created,
                'updated'   => $updated,
                'total'     => count($events),
            ]);

            return [
                'season_id' => $season->id,
                'created'   => $created,
                'updated'   => $updated,
                'total'     => count($events),
            ];
        });
    }
}