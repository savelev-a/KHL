<?php

namespace App\Console\Commands\Khl;

use App\Exceptions\KhlApiException;
use App\Services\Khl\ScheduleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncScheduleCommand extends Command
{
    protected $signature = 'khl:sync-schedule
                            {--season-id= : ID сезона в БД; по умолчанию — текущий}';

    protected $description = 'Синхронизирует календарь матчей КХЛ (текущий срез событий)';

    public function handle(ScheduleSyncService $service): int
    {
        $seasonId = $this->option('season-id');
        $seasonId = $seasonId !== null ? (int) $seasonId : null;

        $this->info('→ Синхронизация календаря матчей КХЛ...');

        $startedAt = microtime(true);

        try {
            $result = $service->sync($seasonId);
            $duration = round(microtime(true) - $startedAt, 2);

            $this->newLine();
            $this->table(
                ['Показатель', 'Значение'],
                [
                    ['season_id', $result['season_id']],
                    ['Всего событий', $result['total']],
                    ['Создано матчей', $result['created']],
                    ['Обновлено матчей', $result['updated']],
                    ['Время, сек', $duration],
                ]
            );

            if ($result['total'] === 0) {
                $this->warn('⚠ API вернул 0 событий. Это нормально в межсезонье.');
            } else {
                $this->info('✓ Синхронизация успешно завершена.');
            }

            return self::SUCCESS;

        } catch (KhlApiException $e) {
            $this->error('✗ Ошибка API КХЛ: ' . $e->getMessage());
            Log::channel('khl')->error('khl:sync-schedule — KHL API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;

        } catch (Throwable $e) {
            $this->error('✗ Непредвиденная ошибка: ' . $e->getMessage());
            Log::channel('khl')->error('khl:sync-schedule — unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}