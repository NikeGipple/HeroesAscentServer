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

            // Relazione con l'account proprietario
            $table->foreignId('account_id')
                ->constrained()
                ->onDelete('cascade');

            // Dati base del personaggio
            $table->string('name')->index();               
            $table->unsignedInteger('level')->nullable();  
            $table->string('profession')->nullable();      

            // Dati runtime
            $table->timestamp('last_check_at')->nullable(); 

            // Stato del personaggio nel contesto dell'evento
            $table->integer('score')->default(0);           
            $table->timestamp('disqualified_at')->nullable(); 

            // Metadati Laravel
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
