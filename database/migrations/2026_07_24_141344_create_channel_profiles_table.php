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
        Schema::create('channel_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('tagline')->nullable();
            $table->text('tone_of_voice')->nullable();
            $table->text('audience')->nullable();
            $table->text('extra_instructions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_profiles');
    }
};
