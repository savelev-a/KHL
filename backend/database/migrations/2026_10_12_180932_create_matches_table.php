<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('khl_id')->unique()
                ->comment('ID матча в API КХЛ');
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamp('starts_at')->index();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->enum('status', ['scheduled', 'finished', 'live', 'postponed'])
                ->default('scheduled')->index();
            $table->timestamps();

            $table->index(['season_id', 'status']);
            $table->index(['home_team_id', 'starts_at']);
            $table->index(['away_team_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};