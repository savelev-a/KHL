<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Khl\Simulation\MonteCarloSimulator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MonteCarloSimulator::class, function () {
            return new MonteCarloSimulator(
                playoffSpots: (int) config('services.khl.playoff_spots', 8),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
