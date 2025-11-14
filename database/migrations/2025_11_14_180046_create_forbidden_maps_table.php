<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forbidden_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('map_id')->unique();    // ID mappa GW2
            $table->string('name')->nullable();             // nome opzionale della mappa
            $table->string('type')->nullable();             // city, pvp, wvw, instance, lounge, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forbidden_maps');
    }
};
