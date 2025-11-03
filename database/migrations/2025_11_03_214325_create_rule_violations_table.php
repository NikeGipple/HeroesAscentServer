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
        Schema::create('rule_violations', function (Blueprint $table) {
            $table->id();

            // Collegamento al personaggio che ha commesso la violazione
            $table->foreignId('character_id')
                ->constrained()
                ->onDelete('cascade');

            // Codice e descrizione violazione
            $table->string('violation_code');      // es. RULE_MAP_001, RULE_FOOD_002
            $table->text('details')->nullable();   // eventuale descrizione aggiuntiva
            $table->timestamp('detected_at');      // quando è stata rilevata

            $table->timestamps();
            $table->softDeletes(); // utile per nascondere o annullare segnalazioni errate
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_violations');
    }
};
