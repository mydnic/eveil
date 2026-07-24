<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetVideoAnalyticsTool;
use App\Models\ChannelProfile;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class VideoAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        private readonly array $video,
        private readonly GetVideoAnalyticsTool $analyticsTool,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $base = <<<EOT
        You are an assistant helping a YouTube creator understand and improve one
        specific video. Be concise and concrete — suggest specific, actionable
        improvements (title, thumbnail, description, retention hooks, pacing) rather
        than generic advice. Use the analytics tool if the creator asks about trends
        or a time range you don't already have.

        Video you are discussing:
        Title: {$this->video['title']}
        Published: {$this->video['published_at']}
        Views: {$this->video['view_count']}
        Likes: {$this->video['like_count']}
        Comments: {$this->video['comment_count']}
        EOT;

        $channelContext = ChannelProfile::current()->promptContext();

        return $channelContext === '' ? $base : "{$base}\n\n{$channelContext}";
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [$this->analyticsTool];
    }
}
