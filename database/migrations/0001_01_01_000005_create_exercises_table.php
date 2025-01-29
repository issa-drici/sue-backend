<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('level');
            $table->text('banner_url')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration');
            $table->integer('xp_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
}; 