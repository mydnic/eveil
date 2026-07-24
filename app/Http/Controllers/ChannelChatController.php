<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ChannelAssistant;
use App\Ai\Tools\GetChannelDataTool;
use App\Http\Controllers\Concerns\StreamsVercelProtocol;
use App\Services\YouTube\YoutubeAnalyticsClient;
use App\Services\YouTube\YoutubeClient;
use Illuminate\Http\Request;

class ChannelChatController extends Controller
{
    use StreamsVercelProtocol;

    public function stream(Request $request, YoutubeClient $youtube, YoutubeAnalyticsClient $analytics)
    {
        $data = $request->validate(['messages' => ['required', 'array']]);

        $account = $request->user()->youtubeAccount;

        abort_unless($account, 403, 'YouTube is not connected.');

        $lastMessage = collect($data['messages'])->last();
        $text = collect($lastMessage['parts'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $agent = new ChannelAssistant(
            $account,
            new GetChannelDataTool($account, $youtube, $analytics),
        );

        $response = $agent->continueLastConversation($request->user())->prompt($text);

        return $this->streamText((string) $response);
    }
}
