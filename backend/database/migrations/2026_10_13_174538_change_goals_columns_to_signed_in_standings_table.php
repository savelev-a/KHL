<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standings', function (Blueprint $table) {
            $table->smallInteger('goals_for')->default(0)->change();
            $table->smallInteger('goals_against')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('standings', function (Blueprint $table) {
            $table->unsignedSmallInteger('goals_for')->default(0)->change();
            $table->unsignedSmallInteger('goals_against')->default(0)->change();
        });
    }
};