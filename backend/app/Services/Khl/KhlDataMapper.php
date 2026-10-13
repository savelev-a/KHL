<?php

namespace App\Services\Khl;

use App\Exceptions\KhlApiException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class KhlDataMapper
{
    /**
     * Извлекает текущий сезон из ответа /data.json.
     *
     * @return array{khl_stage_id:int, name:string, is_current:bool}
     */
    public function mapCurrentSeason(array $data): array
    {
        $stageId = $data['current_stage_id'] ?? null;

        if (!is_int($stageId)) {
            throw KhlApiException::unexpectedStructure(
                '/data.json',
                'current_stage_id missing or not int'
            );
        }

        $stages = $data['stages_v2'] ?? [];

        if (!is_array($stages) || $stages === []) {
            throw KhlApiException::unexpectedStructure(
                '/data.json',
                'stages_v2 missing or empty'
            );
        }

        $currentStage = null;
        foreach ($stages as $stage) {
            if (($stage['id'] ?? null) === $stageId) {
                $currentStage = $stage;
                break;
            }
        }

        if ($currentStage === null) {
            throw KhlApiException::unexpectedStructure(
                '/data.json',
                "stage with id={$stageId} not found in stages_v2"
            );
        }

        $season = $currentStage['season'] ?? null;

        if (!is_string($season) || $season === '') {
            throw KhlApiException::unexpectedStructure(
                '/data.json',
                'current stage has no season string'
            );
        }

        return [
            'khl_stage_id' => $stageId,
            'name'         => $season,
            'is_current'   => true,
        ];
    }

    /**
     * Преобразует ответ /teams_v2.json в плоский список команд.
     *
     * @return array<int, array{khl_id:int, name:string, city:?string, logo_url:?string}>
     */
    public function mapTeams(array $data): array
    {
        if (!array_is_list($data)) {
            throw KhlApiException::unexpectedStructure(
                '/teams_v2.json',
                'expected top-level list'
            );
        }

        $result = [];
        $skipped = 0;

        foreach ($data as $index => $item) {
            // Элемент — это объект с единственным ключом 'team'
            $team = $item['team'] ?? null;

            if (!is_array($team)) {
                $skipped++;
                Log::channel('khl')->warning('mapTeams: element has no "team" wrapper', [
                    'index' => $index,
                ]);
                continue;
            }

            $khlId = $team['khl_id'] ?? null;
            $name  = $team['name'] ?? null;

            if (!is_int($khlId) || !is_string($name) || $name === '') {
                $skipped++;
                Log::channel('khl')->warning('mapTeams: incomplete team record', [
                    'index'   => $index,
                    'khl_id'  => $khlId,
                    'name'    => $name,
                ]);
                continue;
            }

            $result[] = [
                'khl_id'   => $khlId,
                'name'     => trim($name),
                'city'     => isset($team['location']) && $team['location'] !== ''
                    ? trim((string) $team['location'])
                    : null,
                'logo_url' => isset($team['image']) && $team['image'] !== ''
                    ? (string) $team['image']
                    : null,
            ];
        }

        Log::channel('khl')->info('mapTeams: mapped teams', [
            'total'   => count($data),
            'mapped'  => count($result),
            'skipped' => $skipped,
        ]);

        return $result;
    }

    /**
    * Преобразует ответ /events_v2.json в массив для upsert в matches.
    *
    * @param  array  $data       сырой ответ API
    * @param  int    $seasonId   id сезона в нашей БД
    * @param  array<int,int>  $teamIdByKhlId  карта [khl_id команды => team.id в БД]
    * @return array<int, array>
    */
    public function mapSchedule(array $data, int $seasonId, array $teamIdByKhlId): array
    {
        if (!array_is_list($data)) {
            throw KhlApiException::unexpectedStructure(
                '/events_v2.json',
                'expected top-level list'
            );
        }

        $result  = [];
        $skipped = 0;

        foreach ($data as $index => $item) {
            $event = $item['event'] ?? null;

            if (!is_array($event)) {
                $skipped++;
                Log::channel('khl')->warning('mapSchedule: element has no "event" wrapper', [
                    'index' => $index,
                ]);
                continue;
            }

            // 1) ID матча в КХЛ
            $khlId = $event['khl_id'] ?? null;
            if (!is_int($khlId)) {
                $skipped++;
                Log::channel('khl')->warning('mapSchedule: no integer khl_id', [
                    'index' => $index,
                ]);
                continue;
            }

            // 2) Команды — ищем по khl_id, а не по внутреннему id
            $homeKhlId = $event['team_a']['khl_id'] ?? null;
            $awayKhlId = $event['team_b']['khl_id'] ?? null;

            $homeTeamId = $homeKhlId !== null ? ($teamIdByKhlId[$homeKhlId] ?? null) : null;
            $awayTeamId = $awayKhlId !== null ? ($teamIdByKhlId[$awayKhlId] ?? null) : null;

            if ($homeTeamId === null || $awayTeamId === null) {
                $skipped++;
                Log::channel('khl')->warning('mapSchedule: unknown team(s) in event', [
                    'khl_id'       => $khlId,
                    'home_khl_id'  => $homeKhlId,
                    'away_khl_id'  => $awayKhlId,
                ]);
                continue;
            }

            if ($homeTeamId === $awayTeamId) {
                $skipped++;
                Log::channel('khl')->warning('mapSchedule: same team on both sides', [
                    'khl_id'    => $khlId,
                    'team_id'   => $homeTeamId,
                ]);
                continue;
            }

            // 3) Дата старта. В API — unix в миллисекундах.
            $startAt = $event['start_at'] ?? null;
            if (!is_int($startAt) || $startAt <= 0) {
                $skipped++;
                Log::channel('khl')->warning('mapSchedule: invalid start_at', [
                    'khl_id'   => $khlId,
                    'start_at' => $startAt,
                ]);
                continue;
            }

            // 4) Статус
            $statusKey = (string) ($event['game_state_key'] ?? 'not_yet_started');
            $status    = $this->mapStatus($statusKey);

            // 5) Счёт — только для завершённых и текущих матчей
            $homeScore = null;
            $awayScore = null;

            if ($status !== 'scheduled') {
                [$homeScore, $awayScore] = $this->parseScore($event['score'] ?? null, $khlId);
            }

            $result[] = [
                'khl_id'       => $khlId,
                'season_id'    => $seasonId,
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'starts_at'    => Carbon::createFromTimestampMs($startAt)->utc(),
                'home_score'   => $homeScore,
                'away_score'   => $awayScore,
                'status'       => $status,
            ];
        }

        Log::channel('khl')->info('mapSchedule: mapped events', [
            'total'   => count($data),
            'mapped'  => count($result),
            'skipped' => $skipped,
        ]);

        return $result;
    }

    /**
     * "3:2" → [3, 2]. Если строку не распарсили — [null, null] + warning.
     *
     * @return array{0:?int, 1:?int}
     */
    private function parseScore(mixed $score, int $khlId): array
    {
        if (!is_string($score) || !preg_match('/^(\d+):(\d+)$/', trim($score), $m)) {
            Log::channel('khl')->warning('mapSchedule: cannot parse score', [
                'khl_id' => $khlId,
                'score'  => $score,
            ]);
            return [null, null];
        }

        return [(int) $m[1], (int) $m[2]];
    }

    /**
    * Маппинг строкового статуса из API в наш enum.
    * Неизвестные статусы → 'scheduled' + warning в лог.
    */
    private function mapStatus(string $key): string
    {
        return match ($key) {
            'not_yet_started'                => 'scheduled',
            'finished', 'ended'              => 'finished',
            'live', 'in_progress', 'online'  => 'live',
            'postponed', 'canceled',
            'cancelled'                      => 'postponed',
            default                          => 'scheduled',
        };
    }
}