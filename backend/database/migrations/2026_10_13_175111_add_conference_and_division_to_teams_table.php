<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // 'east' | 'west'
            $table->string('conference_key', 20)->nullable()->after('city')->index();
            // 'kharlamov' | 'chernyshev' | 'bobrov' | 'tarasov'
            $table->string('division_key', 20)->nullable()->after('conference_key');
            // Человекочитаемые названия — пригодятся фронту, чтобы не хардкодить
            $table->string('conference_name', 50)->nullable()->after('division_key');
            $table->string('division_name', 50)->nullable()->after('conference_name');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'conference_key', 'division_key',
                'conference_name', 'division_name',
            ]);
        });
    }
};