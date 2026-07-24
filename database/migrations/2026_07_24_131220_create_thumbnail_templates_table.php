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
        Schema::create('thumbnail_templates', function (Blueprint $table) {
            $table->id();
            $table->string('game_font')->default('oswald');
            $table->string('game_font_color')->default('#FF3B30');
            $table->string('boss_font')->default('anton');
            $table->string('boss_font_color')->default('#FFFFFF');
            $table->string('stroke_color')->default('#000000');
            $table->unsignedTinyInteger('stroke_width')->default(6);
            $table->unsignedTinyInteger('gradient_height_percent')->default(55);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thumbnail_templates');
    }
};
