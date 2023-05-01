<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('favorite_user_games', function (Blueprint $table) {
            $table->id();
			$table->unsignedBigInteger('game_id');
			$table->unsignedBigInteger('user_id');
			$table->unsignedSmallInteger('rating')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_user_games');
    }
};
