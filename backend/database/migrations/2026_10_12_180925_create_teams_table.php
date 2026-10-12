<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('khl_id')->unique()
                ->comment('ID команды в API КХЛ (для ссылок)');
            $table->string('name', 150);
            $table->string('city', 100)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->decimal('elo_rating', 8, 2)->default(1500.00)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};