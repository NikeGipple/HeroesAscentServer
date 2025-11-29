<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specializations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gw2_id')->unique();
            $table->string('name');

            $table->string('profession'); 

            $table->string('icon')->nullable();
            $table->string('background')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('specializations');
    }
};
