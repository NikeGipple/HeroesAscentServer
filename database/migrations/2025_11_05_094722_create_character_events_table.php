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

            // Codice tecnico dell'evento (es. LOGIN, DOWNED, DEAD…)
            $table->string('event_code', 100)->index();

            // Titolo e descrizione
            $table->string('title')->nullable();
            $table->text('details')->nullable();

            // Dati di contesto
            $table->unsignedTinyInteger('level')->nullable();
            $table->unsignedTinyInteger('effective_level')->nullable();
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
            $table->unsignedTinyInteger('mount_index')->nullable();

            // Posizione del personaggio
            $table->decimal('pos_x', 12, 6)->nullable();
            $table->decimal('pos_y', 12, 6)->nullable();
            $table->decimal('pos_z', 12, 6)->nullable();

            // Punteggio assegnato (derivato da EventType.points)
            $table->integer('points')->default(0);

            // Timestamp dell’evento
            $table->timestamp('detected_at')->nullable();

            // Default Laravel
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_events');
    }
};
