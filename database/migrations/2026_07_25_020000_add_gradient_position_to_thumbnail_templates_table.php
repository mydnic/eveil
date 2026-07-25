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
        Schema::table('thumbnail_templates', function (Blueprint $table) {
            $table->string('gradient_position')->default('bottom')->after('gradient_height_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thumbnail_templates', function (Blueprint $table) {
            $table->dropColumn('gradient_position');
        });
    }
};
