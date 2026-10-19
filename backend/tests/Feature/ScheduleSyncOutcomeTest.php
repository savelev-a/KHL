<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Services\Khl\KhlApiClient;
use App\Services\Khl\KhlDataMapper;
use App\Services\Khl\ScheduleSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSyncOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private Season $season;
    private Team $home;
    private Team $away;

    protected function setUp(): void
    {
        parent::setUp();

        $this->season = Season::create([
            'khl_stage_id' => 407,
            'name'         => '2026/2027',
            'is_current'   => true,
        ]);

        $this->home = Team::create([
            'khl_id' => 101,
            'name'   => 'Хозяева',
            'city'   => 'Москва',
        ]);

        $this->away = Team::create([
            'khl_id' => 102,
            'name'   => 'Гости',
            'city'   => 'Санкт-Петербург',
        ]);
    }

    /**
     * Баг: при обновлении уже существующего матча outcome не записывался,
     * поэтому у всех завершённых матчей в БД оставался null, а ot_wins = 0.
     */
    public function test_finished_event_updates_outcome_of_existing_match(): void
    {
        $game = Game::create([
            'khl_id'       => 902145,
            'season_id'    => $this->season->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'starts_at'    => '2026-10-12 16:30:00',
            'home_score'   => 2,
            'away_score'   => 3,
            'status'       => 'finished',
            'outcome'      => null, // данные, синхронизированные до миграции
        ]);

        $this->syncWith([$this->event('finished', '2:3', [
            'first_period'  => '0:1',
            'second_period' => '1:0',
            'third_period'  => '1:1',
            'overtime'      => '0:1',
            'bullitt'       => null,
        ])]);

        $game->refresh();

        $this->assertSame('finished', $game->status);
        $this->assertSame('overtime', $game->outcome);
        $this->assertSame(2, $game->home_score);
        $this->assertSame(3, $game->away_score);
    }

    public function test_shootout_is_detected_by_bullitt_marker(): void
    {
        $game = Game::create([
            'khl_id'       => 902152,
            'season_id'    => $this->season->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'starts_at'    => '2026-10-12 16:30:00',
            'home_score'   => 2,
            'away_score'   => 1,
            'status'       => 'finished',
            'outcome'      => null,
        ]);

        $this->syncWith([$this->event('finished', '2:1', [
            'first_period'  => '0:0',
            'second_period' => '0:0',
            'third_period'  => '1:1',
            'overtime'      => '0:0',
            'bullitt'       => '2:1',
        ], 902152)]);

        $game->refresh();

        $this->assertSame('shootout', $game->outcome);
    }

    public function test_distribution_of_outcomes_sums_to_finished_games(): void
    {
        $events = [
            $this->event('finished', '3:1', [
                'overtime' => null, 'bullitt' => null,
            ], 902001),
            $this->event('finished', '2:3', [
                'overtime' => '0:1', 'bullitt' => null,
            ], 902002),
            $this->event('finished', '2:1', [
                'overtime' => '0:0', 'bullitt' => '2:1',
            ], 902003),
        ];

        $this->syncWith($events);

        $this->assertSame(1, Game::where('outcome', 'regular')->count());
        $this->assertSame(1, Game::where('outcome', 'overtime')->count());
        $this->assertSame(1, Game::where('outcome', 'shootout')->count());
    }

    public function test_scheduled_event_does_not_wipe_already_known_outcome(): void
    {
        $game = Game::create([
            'khl_id'       => 902002,
            'season_id'    => $this->season->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'starts_at'    => '2026-10-12 16:30:00',
            'home_score'   => 2,
            'away_score'   => 3,
            'status'       => 'finished',
            'outcome'      => 'overtime',
        ]);

        $this->syncWith([$this->event('not_yet_started', '0:0', [
            'overtime' => null,
            'bullitt'  => null,
        ])]);

        $game->refresh();

        $this->assertSame('overtime', $game->outcome);
        $this->assertSame(2, $game->home_score);
        $this->assertSame(3, $game->away_score);
    }

    /**
     * @param  array<int, array>  $events
     */
    private function syncWith(array $events): void
    {
        $api = $this->mock(KhlApiClient::class);
        $api->shouldReceive('getPaginated')
            ->once()
            ->andReturn($events);

        (new ScheduleSyncService($api, new KhlDataMapper()))
            ->sync($this->season->id);
    }

    /**
     * @param  array<string, ?string>  $scores
     * @return array{event: array}
     */
    private function event(
        string $state,
        string $score,
        array $scores,
        int $khlId = 902145,
    ): array {
        return [
            'event' => [
                'khl_id'         => $khlId,
                'stage_id'       => $this->season->khl_stage_id,
                'team_a'         => ['id' => 1, 'khl_id' => $this->home->khl_id],
                'team_b'         => ['id' => 2, 'khl_id' => $this->away->khl_id],
                'start_at'       => 1804176000000,
                'game_state_key' => $state,
                'score'          => $score,
                'scores'         => $scores,
            ],
        ];
    }
}
