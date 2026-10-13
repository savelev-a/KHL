<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| KHL Analyzer — расписание синхронизаций
|--------------------------------------------------------------------------
|
| Порядок важен: sync-teams обновляет команды и текущий сезон,
| sync-schedule наполняет matches, recalculate-elo пересчитывает
| standings и Эло, calculate-probabilities считает Монте-Карло.
|
*/

// Раз в сутки в 4:30 — команды и сезон.
Schedule::command('khl:sync-teams')
    ->dailyAt('04:30')
    ->timezone('Europe/Moscow')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Раз в 2 часа — календарь и результаты матчей.
// Чаще, чем раз в сутки, чтобы «live» матчи вовремя становились finished,
// а расписание обновлялось при переносах.
Schedule::command('khl:sync-schedule')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Раз в 2 часа после schedule — пересчёт Эло и standings.
// Смещение +5 минут, чтобы не пересекаться.
Schedule::command('khl:recalculate-elo')
    ->cron('5 */2 * * *')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Раз в 2 часа после Эло — вероятности.
// Смещение ещё +5 минут.
Schedule::command('khl:calculate-probabilities')
    ->cron('10 */2 * * *')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();