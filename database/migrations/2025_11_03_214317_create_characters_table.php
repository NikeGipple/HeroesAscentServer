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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained()
                ->onDelete('cascade');

            // Dati base del personaggio
            $table->string('name');                        // nome in game
            $table->unsignedInteger('level')->nullable();  // livello attuale
            $table->string('profession')->nullable();      // professione GW2 (es: Warrior, Mesmer…)

            // Dati runtime usati dall’addon
            $table->unsignedInteger('last_map_id')->nullable();
            $table->unsignedInteger('last_state')->nullable();
            $table->timestamp('last_check_at')->nullable();

            $table->integer('score')->default(0);
            $table->timestamp('disqualified_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
