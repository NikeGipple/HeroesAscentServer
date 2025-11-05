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
        // Rimuoviamo eventuale tabella precedente
        Schema::dropIfExists('rule_violations');

        Schema::create('character_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')
                ->constrained()
                ->onDelete('cascade');

            // Informazioni evento
            $table->string('event_code', 100)->index(); // es: RULE_FOOD_001
            $table->string('title')->nullable();        // titolo leggibile
            $table->text('details')->nullable();        // descrizione evento
            $table->integer('points')->default(0);      // punti guadagnati o persi
            $table->timestamp('detected_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_events');
    }
};
