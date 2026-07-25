<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetVideoAnalyticsTool;
use App\Ai\Tools\SuggestVideoDescriptionTool;
use App\Models\ChannelProfile;
use App\Support\ChatHistory;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Promptable;
use Stringable;

class VideoAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        private readonly array $video,
        private readonly GetVideoAnalyticsTool $analyticsTool,
        private readonly SuggestVideoDescriptionTool $descriptionTool,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $description = $this->video['description'] !== ''
            ? "Current description:\n{$this->video['description']}"
            : 'Current description: (none)';

        $base = <<<EOT
        You are an assistant helping a YouTube creator understand and improve one
        specific video. Be concise and concrete — suggest specific, actionable
        improvements (title, thumbnail, description, retention hooks, pacing) rather
        than generic advice. Use the analytics tool if the creator asks about trends
        or a time range you don't already have. Use the description tool whenever
        asked to write, rewrite, or tweak the description.

        Video you are discussing:
        Title: {$this->video['title']}
        Published: {$this->video['published_at']}
        Views: {$this->video['view_count']}
        Likes: {$this->video['like_count']}
        Comments: {$this->video['comment_count']}
        {$description}
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
        return [$this->analyticsTool, $this->descriptionTool];
    }

    /**
     * Overrides RemembersConversations' default history loading to strip
     * tool_calls/tool_results — see App\Support\ChatHistory::toPlainMessages().
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        $conversation = $this->currentConversation() ? Conversation::find($this->currentConversation()) : null;

        return $conversation
            ? ChatHistory::toPlainMessages($conversation->messages()->orderBy('created_at')->get())
            : [];
    }
}
