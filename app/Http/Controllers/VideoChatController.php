<?php

namespace App\Http\Controllers;

use App\Ai\Agents\VideoAssistant;
use App\Ai\Tools\GetVideoAnalyticsTool;
use App\Ai\Tools\SuggestVideoDescriptionTool;
use App\Http\Controllers\Concerns\StreamsVercelProtocol;
use App\Models\Video;
use App\Services\YouTube\YoutubeAnalyticsClient;
use App\Services\YouTube\YoutubeClient;
use Illuminate\Http\Request;

class VideoChatController extends Controller
{
    use StreamsVercelProtocol;

    public function stream(Request $request, string $video, YoutubeClient $youtube, YoutubeAnalyticsClient $analytics)
    {
        $data = $request->validate(['messages' => ['required', 'array']]);

        $account = $request->user()->youtubeAccount;

        abort_unless($account, 403, 'YouTube is not connected.');

        $videoData = $youtube->fetchVideo($account, $video);
        $videoModel = Video::forYoutubeId($video);

        $lastMessage = collect($data['messages'])->last();
        $text = collect($lastMessage['parts'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $descriptionTool = new SuggestVideoDescriptionTool;

        $agent = new VideoAssistant(
            [
                'title' => $videoData['snippet']['title'],
                'published_at' => $videoData['snippet']['publishedAt'],
                'view_count' => $videoData['statistics']['viewCount'] ?? 'unknown',
                'like_count' => $videoData['statistics']['likeCount'] ?? 'hidden',
                'comment_count' => $videoData['statistics']['commentCount'] ?? 'unknown',
                'description' => $videoData['snippet']['description'] ?? '',
            ],
            new GetVideoAnalyticsTool($account, $video, $analytics),
            $descriptionTool,
        );

        $response = $agent->continueLastConversation($videoModel)->prompt($text);

        $dataParts = $descriptionTool->suggestedDescription !== null ? [
            [
                'type' => 'data-description-suggestion',
                'data' => ['description' => $descriptionTool->suggestedDescription],
            ],
        ] : [];

        return $this->streamText((string) $response, $dataParts);
    }
}
