<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'tagline', 'tone_of_voice', 'audience', 'extra_instructions'])]
class ChannelProfile extends Model
{
    /**
     * The app has a single, shared channel profile.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * Render the filled-in fields as a block of context for AI agents, or
     * an empty string if nothing has been configured yet.
     */
    public function promptContext(): string
    {
        $fields = [
            'Channel name' => $this->name,
            'Tagline' => $this->tagline,
            'Tone of voice' => $this->tone_of_voice,
            'Audience' => $this->audience,
            'Additional instructions' => $this->extra_instructions,
        ];

        $lines = [];

        foreach ($fields as $label => $value) {
            if (filled($value)) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return $lines === [] ? '' : "About this channel:\n".implode("\n", $lines);
    }
}
