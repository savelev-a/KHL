<?php

namespace App\Console\Commands\Khl;

use App\Models\Season;
use App\Services\Khl\StandingsRebuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecalculateEloCommand extends Command
{
    protected $signature = 'khl:recalculate-elo
                            {--season-id= : ID сезона в БД; по умолчанию — текущий}';

    protected $description = 'Пересчитывает Эло по истории сезона и пересобирает standings';

    public function handle(StandingsRebuilder $rebuilder): int
    {
        $seasonId = $this->option('season-id');
        $season = $seasonId !== null
            ? Season::find((int) $seasonId)
            : Season::current()->first();

        if (!$season) {
            $this->error('✗ Сезон не найден. Сначала выполните khl:sync-teams.');
            return self::FAILURE;
        }

        $this->info("→ Пересчёт Эло для сезона #{$season->id} ({$season->name})...");

        try {
            $result = $rebuilder->rebuild($season);

            $this->newLine();
            $this->table(
                ['Показатель', 'Значение'],
                [
                    ['season_id',    $result['season_id']],
                    ['Матчей учтено', $result['games']],
                    ['Команд',        $result['teams']],
                    ['Время, сек',    $result['duration']],
                ]
            );

            if ($result['games'] === 0) {
                $this->warn('⚠ Завершённых матчей нет — Эло не изменился.');
            } else {
                $this->info('✓ Пересчёт завершён.');
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('✗ Ошибка: ' . $e->getMessage());
            Log::channel('khl')->error('khl:recalculate-elo failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}