<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Concerns\HasConversations;

// Video data itself always comes live from the YouTube API — this model
// exists only so a per-video AI chat has something to anchor its
// conversation history to.
#[Fillable(['youtube_video_id'])]
class Video extends Model
{
    use HasConversations;

    public static function forYoutubeId(string $youtubeVideoId): self
    {
        return static::query()->firstOrCreate(['youtube_video_id' => $youtubeVideoId]);
    }
}
