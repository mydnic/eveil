<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kind',
    'content',
    'font',
    'font_color',
    'font_size',
    'x_percent',
    'y_percent',
    'align',
    'rotation',
    'stroke_color',
    'stroke_width',
    'uppercase',
    'sort_order',
])]
class ThumbnailTemplateText extends Model
{
    protected function casts(): array
    {
        return [
            'font_size' => 'integer',
            'x_percent' => 'integer',
            'y_percent' => 'integer',
            'rotation' => 'integer',
            'stroke_width' => 'integer',
            'uppercase' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function template(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ThumbnailTemplate::class, 'thumbnail_template_id');
    }

    public function fontPath(): string
    {
        return resource_path('fonts/'.ThumbnailTemplate::FONTS[$this->font]['file']);
    }

    /**
     * Resolve the actual text to draw: dynamic for game/boss, literal for fixed.
     */
    public function resolveContent(string $game, string $boss): string
    {
        $text = match ($this->kind) {
            'game' => $game,
            'boss' => $boss,
            default => (string) $this->content,
        };

        return $this->uppercase ? mb_strtoupper($text) : $text;
    }
}
