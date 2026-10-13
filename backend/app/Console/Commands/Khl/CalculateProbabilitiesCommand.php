<?php

namespace App\Console\Commands\Khl;

use App\Models\Season;
use App\Services\Khl\Simulation\PlayoffProbabilityCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculateProbabilitiesCommand extends Command
{
    protected $signature = 'khl:calculate-probabilities
                            {--season-id= : ID сезона в БД; по умолчанию — текущий}
                            {--iterations=10000 : Количество прогонов Монте-Карло}';

    protected $description = 'Считает вероятности выхода в плей-офф методом Монте-Карло';

    public function handle(PlayoffProbabilityCalculator $calculator): int
    {
        $seasonId = $this->option('season-id');
        $season = $seasonId !== null
            ? Season::find((int) $seasonId)
            : Season::current()->first();

        if (!$season) {
            $this->error('✗ Сезон не найден. Сначала выполните khl:sync-teams.');
            return self::FAILURE;
        }

        $iterations = (int) $this->option('iterations');

        $this->info("→ Симуляция Монте-Карло для сезона #{$season->id} ({$season->name})...");
        $this->info("   Итераций: {$iterations}");

        try {
            $result = $calculator->calculate($season, $iterations);

            $this->newLine();
            $this->info('✓ Симуляция завершена.');
            $this->table(
                ['Показатель', 'Значение'],
                [
                    ['season_id',  $season->id],
                    ['Итераций',   $result['simulations']],
                    ['Время, сек', $result['duration']],
                ]
            );

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('✗ Ошибка: ' . $e->getMessage());
            Log::channel('khl')->error('khl:calculate-probabilities failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}