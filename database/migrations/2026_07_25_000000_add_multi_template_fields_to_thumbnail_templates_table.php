<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('thumbnail_templates', function (Blueprint $table) {
            $table->string('name')->default('Default')->after('id');
            $table->boolean('is_default')->default(false)->after('name');
            $table->text('game_keywords')->nullable()->after('is_default');
        });

        DB::table('thumbnail_templates')->update(['is_default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thumbnail_templates', function (Blueprint $table) {
            $table->dropColumn(['name', 'is_default', 'game_keywords']);
        });
    }
};
