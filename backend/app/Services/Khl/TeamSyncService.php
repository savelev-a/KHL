<?php

namespace App\Services\Khl;

use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamSyncService
{
    public function __construct(
        private readonly KhlApiClient $api,
        private readonly KhlDataMapper $mapper,
    ) {}

    /**
     * @return array{season_id:int, teams_created:int, teams_updated:int}
     */
    public function sync(): array
    {
        // 1. Сезон
        $data  = $this->api->get('/data.json');
        $seasonData = $this->mapper->mapCurrentSeason($data);

        // 2. Команды
        $teamsRaw = $this->api->get('/teams_v2.json');
        $teams    = $this->mapper->mapTeams($teamsRaw);

        if ($teams === []) {
            throw new \RuntimeException('KHL API returned zero teams — aborting sync');
        }

        // 3. Запись в БД — в одной транзакции
        return DB::transaction(function () use ($seasonData, $teams) {
            $season = $this->upsertCurrentSeason($seasonData);

            $created = 0;
            $updated = 0;

            foreach ($teams as $row) {
                $existing = Team::where('khl_id', $row['khl_id'])->first();

                if ($existing) {
                    $existing->fill([
                        'name'            => $row['name'],
                        'city'            => $row['city'],
                        'logo_url'        => $row['logo_url'],
                        'conference_key'  => $row['conference_key'],
                        'division_key'    => $row['division_key'],
                        'conference_name' => $row['conference_name'],
                        'division_name'   => $row['division_name'],
                    ])->save();
                    $updated++;
                } else {
                    Team::create([
                        'khl_id'     => $row['khl_id'],
                        'name'       => $row['name'],
                        'city'       => $row['city'],
                        'logo_url'   => $row['logo_url'],
                        'elo_rating' => 1500.00,
                        'conference_key'  => $row['conference_key'],
                        'division_key'    => $row['division_key'],
                        'conference_name' => $row['conference_name'],
                        'division_name'   => $row['division_name'],
                    ]);
                    $created++;
                }
            }

            Log::channel('khl')->info('Team sync finished', [
                'season_id'      => $season->id,
                'season_name'    => $season->name,
                'teams_created'  => $created,
                'teams_updated'  => $updated,
            ]);

            return [
                'season_id'     => $season->id,
                'teams_created' => $created,
                'teams_updated' => $updated,
            ];
        });
    }

    private function upsertCurrentSeason(array $data): Season
    {
        // Снимаем флаг is_current со всех сезонов, ставим только текущему
        Season::where('is_current', true)
            ->where('khl_stage_id', '!=', $data['khl_stage_id'])
            ->update(['is_current' => false]);

        return Season::updateOrCreate(
            ['khl_stage_id' => $data['khl_stage_id']],
            [
                'name'       => $data['name'],
                'is_current' => true,
            ]
        );
    }
}