<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_default', 'game_keywords', 'gradient_height_percent'])]
class ThumbnailTemplate extends Model
{
    /**
     * Bundled fonts available for template text, keyed by the value stored
     * in thumbnail_template_texts.font.
     */
    public const FONTS = [
        'anton' => ['label' => 'Anton', 'file' => 'Anton-Regular.ttf'],
        'oswald' => ['label' => 'Oswald', 'file' => 'Oswald-Bold.ttf'],
        'bebas_neue' => ['label' => 'Bebas Neue', 'file' => 'BebasNeue-Regular.ttf'],
        'archivo_black' => ['label' => 'Archivo Black', 'file' => 'ArchivoBlack-Regular.ttf'],
        'bangers' => ['label' => 'Bangers', 'file' => 'Bangers-Regular.ttf'],
        'alfa_slab_one' => ['label' => 'Alfa Slab One', 'file' => 'AlfaSlabOne-Regular.ttf'],
        'black_ops_one' => ['label' => 'Black Ops One', 'file' => 'BlackOpsOne-Regular.ttf'],
        'russo_one' => ['label' => 'Russo One', 'file' => 'RussoOne-Regular.ttf'],
        'righteous' => ['label' => 'Righteous', 'file' => 'Righteous-Regular.ttf'],
        'passion_one' => ['label' => 'Passion One', 'file' => 'PassionOne-Bold.ttf'],
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'gradient_height_percent' => 'integer',
        ];
    }

    public function texts(): HasMany
    {
        return $this->hasMany(ThumbnailTemplateText::class)->orderBy('sort_order');
    }

    /**
     * The fallback template used when no other template's keywords match a
     * video's game. Created with the original single-template defaults if
     * none exists yet.
     */
    public static function default(): self
    {
        $template = static::query()->where('is_default', true)->first();

        if ($template) {
            return $template;
        }

        $template = static::query()->create([
            'name' => 'Default',
            'is_default' => true,
            'gradient_height_percent' => 55,
        ]);

        $template->texts()->createMany([
            [
                'kind' => 'game',
                'font' => 'oswald',
                'font_color' => '#FF3B30',
                'font_size' => 48,
                'x_percent' => 50,
                'y_percent' => 75,
                'align' => 'center',
                'stroke_color' => '#000000',
                'stroke_width' => 3,
                'sort_order' => 0,
            ],
            [
                'kind' => 'boss',
                'font' => 'anton',
                'font_color' => '#FFFFFF',
                'font_size' => 90,
                'x_percent' => 50,
                'y_percent' => 92,
                'align' => 'center',
                'stroke_color' => '#000000',
                'stroke_width' => 6,
                'sort_order' => 1,
            ],
        ]);

        return $template->load('texts');
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
}
