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
}