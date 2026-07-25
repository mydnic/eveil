<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'is_default',
    'game_keywords',
    'game_font',
    'game_font_color',
    'boss_font',
    'boss_font_color',
    'stroke_color',
    'stroke_width',
    'gradient_height_percent',
])]
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
            'is_default' => 'boolean',
            'stroke_width' => 'integer',
            'gradient_height_percent' => 'integer',
        ];
    }

    /**
     * The fallback template used when no other template's keywords match a
     * video's game. Created with the original single-template defaults if
     * none exists yet.
     */
    public static function default(): self
    {
        return static::query()->firstOrCreate(['is_default' => true], [
            'name' => 'Default',
            'is_default' => true,
            'game_font' => 'oswald',
            'game_font_color' => '#FF3B30',
            'boss_font' => 'anton',
            'boss_font_color' => '#FFFFFF',
            'stroke_color' => '#000000',
            'stroke_width' => 6,
            'gradient_height_percent' => 55,
        ]);
    }

    /**
     * Resolve the best template for a given game name: the first non-default
     * template with a matching keyword, falling back to the default template.
     */
    public static function forGame(string $game): self
    {
        $game = mb_strtolower($game);

        $match = static::query()
            ->where('is_default', false)
            ->whereNotNull('game_keywords')
            ->get()
            ->first(function (self $template) use ($game) {
                return collect($template->keywords())->contains(
                    fn (string $keyword) => $keyword !== '' && str_contains($game, $keyword)
                );
            });

        return $match ?? static::default();
    }

    /**
     * @return array<int, string>
     */
    public function keywords(): array
    {
        return collect(explode(',', (string) $this->game_keywords))
            ->map(fn (string $keyword) => mb_strtolower(trim($keyword)))
            ->filter()
            ->values()
            ->all();
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
