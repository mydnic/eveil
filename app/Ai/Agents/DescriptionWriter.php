<?php

namespace App\Ai\Agents;

use App\Models\ChannelProfile;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class DescriptionWriter implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $base = <<<'EOT'
        You write YouTube video descriptions for a gaming channel. Videos are
        boss fights, titled "Game - Boss".

        Rules:
        - 2 to 4 short paragraphs, plain and direct, no clickbait or hype words.
        - Mention the game and the boss naturally.
        - It's fine to briefly reference the fight's difficulty or notable
          mechanics if the title or stats imply them, but never invent details
          you weren't given.
        - End with up to 5 relevant, lowercase hashtags on their own line.
        - Output only the description text, no title, no markdown, no preamble.
        EOT;

        $channelContext = ChannelProfile::current()->promptContext();

        return $channelContext === '' ? $base : "{$base}\n\n{$channelContext}";
    }
}
