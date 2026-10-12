<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playoff_probabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->decimal('probability', 5, 4)
                ->comment('Доля от 0.0000 до 1.0000');
            $table->unsignedInteger('simulations_count')->default(0);
            $table->timestamp('calculated_at')->index();
            $table->timestamps();

            $table->unique(['team_id', 'season_id']);
            $table->index(['season_id', 'probability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playoff_probabilities');
    }
};