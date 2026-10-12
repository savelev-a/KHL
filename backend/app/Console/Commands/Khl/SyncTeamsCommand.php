<?php

namespace App\Console\Commands\Khl;

use App\Exceptions\KhlApiException;
use App\Services\Khl\TeamSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTeamsCommand extends Command
{
    protected $signature = 'khl:sync-teams';

    protected $description = 'Синхронизирует список команд и текущий сезон из API КХЛ';

    public function handle(TeamSyncService $service): int
    {
        $this->info('→ Синхронизация команд КХЛ...');
        $startedAt = microtime(true);

        try {
            $result = $service->sync();

            $duration = round(microtime(true) - $startedAt, 2);

            $this->newLine();
            $this->table(
                ['Показатель', 'Значение'],
                [
                    ['season_id',     $result['season_id']],
                    ['Создано команд', $result['teams_created']],
                    ['Обновлено команд', $result['teams_updated']],
                    ['Время, сек',     $duration],
                ]
            );

            $this->info('✓ Синхронизация успешно завершена.');

            return self::SUCCESS;

        } catch (KhlApiException $e) {
            $this->error('✗ Ошибка при обращении к API КХЛ: ' . $e->getMessage());
            Log::channel('khl')->error('khl:sync-teams — KHL API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;

        } catch (Throwable $e) {
            $this->error('✗ Непредвиденная ошибка: ' . $e->getMessage());
            Log::channel('khl')->error('khl:sync-teams — unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}