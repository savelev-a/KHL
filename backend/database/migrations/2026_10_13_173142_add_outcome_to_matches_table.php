<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // regular | overtime | shootout
            // null для матчей без счёта (scheduled)
            $table->enum('outcome', ['regular', 'overtime', 'shootout'])
                ->nullable()
                ->after('away_score');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('outcome');
        });
    }
};