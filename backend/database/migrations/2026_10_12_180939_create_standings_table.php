<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('games_played')->default(0);
            $table->unsignedSmallInteger('points')->default(0)->index();
            $table->unsignedSmallInteger('wins')->default(0)
                ->comment('Победы в основное время');
            $table->unsignedSmallInteger('ot_wins')->default(0)
                ->comment('Победы в OT/SO');
            $table->unsignedSmallInteger('losses')->default(0);
            $table->unsignedSmallInteger('goals_for')->default(0);
            $table->unsignedSmallInteger('goals_against')->default(0);
            $table->unsignedTinyInteger('position')->nullable()->index();
            $table->timestamps();

            $table->unique(['team_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};