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
        Schema::create('banned_skills', function (Blueprint $table) {
            $table->id();                                         
            $table->unsignedBigInteger('gw2_id')->unique();       
            $table->string('name');                               
            $table->string('icon_url')->nullable();               
            $table->string('slot')->nullable();                  
            $table->string('type')->nullable();                   
            $table->string('weapon_type')->nullable();            
            $table->text('description')->nullable();             
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banned_skills');
    }
};
