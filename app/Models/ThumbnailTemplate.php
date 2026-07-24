<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['game_font', 'game_font_color', 'boss_font', 'boss_font_color', 'stroke_color', 'stroke_width', 'gradient_height_percent'])]
class ThumbnailTemplate extends Model
{
    /**
     * Bundled fonts available for the game/boss text, keyed by the value
     * stored in game_font/boss_font.
     */
    public const FONTS = [
        'anton' => ['label' => 'Anton', 'file' => 'Anton-Regular.ttf'],
        'oswald' => ['label' => 'Oswald', 'file' => 'Oswald-Bold.ttf'],
        'bebas_neue' => ['label' => 'Bebas Neue', 'file' => 'BebasNeue-Regular.ttf'],
        'archivo_black' => ['label' => 'Archivo Black', 'file' => 'ArchivoBlack-Regular.ttf'],
    ];

    protected function casts(): array
    {
        return [
            'stroke_width' => 'integer',
            'gradient_height_percent' => 'integer',
        ];
    }

    /**
     * The app has a single, shared thumbnail template.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'game_font' => 'oswald',
            'game_font_color' => '#FF3B30',
            'boss_font' => 'anton',
            'boss_font_color' => '#FFFFFF',
            'stroke_color' => '#000000',
            'stroke_width' => 6,
            'gradient_height_percent' => 55,
        ]);
    }

    public function gameFontPath(): string
    {
        return resource_path('fonts/'.self::FONTS[$this->game_font]['file']);
    }

    public function bossFontPath(): string
    {
        return resource_path('fonts/'.self::FONTS[$this->boss_font]['file']);
    }
}
