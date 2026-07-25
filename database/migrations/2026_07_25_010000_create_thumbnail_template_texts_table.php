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
        Schema::create('thumbnail_template_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thumbnail_template_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('fixed'); // game|boss|fixed
            $table->string('content')->nullable(); // only used for kind=fixed
            $table->string('font')->default('oswald');
            $table->string('font_color')->default('#FFFFFF');
            $table->unsignedSmallInteger('font_size')->default(48);
            $table->unsignedTinyInteger('x_percent')->default(50);
            $table->unsignedTinyInteger('y_percent')->default(90);
            $table->string('align')->default('center'); // left|center|right
            $table->smallInteger('rotation')->default(0); // degrees, -180..180
            $table->string('stroke_color')->default('#000000');
            $table->unsignedTinyInteger('stroke_width')->default(6);
            $table->boolean('uppercase')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrate each existing template's flat game/boss styling into two
        // text rows that reproduce the previous fixed layout, so upgraded
        // templates keep looking the same until edited.
        foreach (DB::table('thumbnail_templates')->get() as $template) {
            DB::table('thumbnail_template_texts')->insert([
                [
                    'thumbnail_template_id' => $template->id,
                    'kind' => 'game',
                    'font' => $template->game_font,
                    'font_color' => $template->game_font_color,
                    'font_size' => 48,
                    'x_percent' => 50,
                    'y_percent' => 75,
                    'align' => 'center',
                    'rotation' => 0,
                    'stroke_color' => $template->stroke_color,
                    'stroke_width' => max(2, (int) round($template->stroke_width / 2)),
                    'uppercase' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'thumbnail_template_id' => $template->id,
                    'kind' => 'boss',
                    'font' => $template->boss_font,
                    'font_color' => $template->boss_font_color,
                    'font_size' => 90,
                    'x_percent' => 50,
                    'y_percent' => 92,
                    'align' => 'center',
                    'rotation' => 0,
                    'stroke_color' => $template->stroke_color,
                    'stroke_width' => $template->stroke_width,
                    'uppercase' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        Schema::table('thumbnail_templates', function (Blueprint $table) {
            $table->dropColumn([
                'game_font',
                'game_font_color',
                'boss_font',
                'boss_font_color',
                'stroke_color',
                'stroke_width',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thumbnail_templates', function (Blueprint $table) {
            $table->string('game_font')->default('oswald');
            $table->string('game_font_color')->default('#FF3B30');
            $table->string('boss_font')->default('anton');
            $table->string('boss_font_color')->default('#FFFFFF');
            $table->string('stroke_color')->default('#000000');
            $table->unsignedTinyInteger('stroke_width')->default(6);
        });

        foreach (DB::table('thumbnail_templates')->get() as $template) {
            $game = DB::table('thumbnail_template_texts')
                ->where('thumbnail_template_id', $template->id)->where('kind', 'game')->first();
            $boss = DB::table('thumbnail_template_texts')
                ->where('thumbnail_template_id', $template->id)->where('kind', 'boss')->first();

            DB::table('thumbnail_templates')->where('id', $template->id)->update([
                'game_font' => $game->font ?? 'oswald',
                'game_font_color' => $game->font_color ?? '#FF3B30',
                'boss_font' => $boss->font ?? 'anton',
                'boss_font_color' => $boss->font_color ?? '#FFFFFF',
                'stroke_color' => $boss->stroke_color ?? '#000000',
                'stroke_width' => $boss->stroke_width ?? 6,
            ]);
        }

        Schema::dropIfExists('thumbnail_template_texts');
    }
};
