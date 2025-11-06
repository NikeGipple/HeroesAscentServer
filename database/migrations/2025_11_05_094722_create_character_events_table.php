<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('character_events');

        Schema::create('character_events', function (Blueprint $table) {
            $table->id();

            // Relazione con il personaggio
            $table->foreignId('character_id')
                ->constrained()
                ->onDelete('cascade');

            // Tipologia di evento (es: login, violation, score, ecc.)
            $table->string('type', 50)->index();

            // Codice tecnico dell'evento (es: RULE_FOOD_001, LOGIN_START, ecc.)
            $table->string('event_code', 100)->nullable()->index();

            // Titolo e descrizione dell'evento
            $table->string('title')->nullable();
            $table->text('details')->nullable();

            // Dati principali di contesto al momento dell'evento
            $table->unsignedInteger('map_id')->nullable()->index();
            $table->unsignedTinyInteger('map_type')->nullable();
            $table->unsignedTinyInteger('profession')->nullable();
            $table->unsignedTinyInteger('elite_spec')->nullable();
            $table->unsignedTinyInteger('race')->nullable();
            $table->unsignedTinyInteger('state')->nullable();
            $table->unsignedTinyInteger('group_type')->nullable();
            $table->unsignedTinyInteger('group_count')->nullable();
            $table->boolean('commander')->default(false);
            $table->boolean('is_login')->default(false);

            // Posizione (utile per analisi geografiche o di movimento)
            $table->decimal('pos_x', 12, 6)->nullable();
            $table->decimal('pos_y', 12, 6)->nullable();
            $table->decimal('pos_z', 12, 6)->nullable();


            $table->timestamp('detected_at')->nullable();

            // Laravel defaults
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_events');
    }
};
