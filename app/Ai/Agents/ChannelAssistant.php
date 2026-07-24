<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetChannelDataTool;
use App\Models\ChannelProfile;
use App\Models\YoutubeAccount;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class ChannelAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        private readonly YoutubeAccount $account,
        private readonly GetChannelDataTool $dataTool,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $base = <<<EOT
        You are an assistant helping a YouTube creator understand and grow their
        channel as a whole. Be concise and concrete. Use the available tool to
        look up the channel's recent videos or its views/likes trend whenever the
        creator asks something you don't already know — don't guess at numbers.

        Channel: {$this->account->channel_title}
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
        return [$this->dataTool];
    }
}
