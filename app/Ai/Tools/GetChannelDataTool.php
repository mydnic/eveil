<?php

namespace App\Ai\Tools;

use App\Models\YoutubeAccount;
use App\Services\YouTube\YoutubeAnalyticsClient;
use App\Services\YouTube\YoutubeClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

// A single tool covering both video listing and analytics, rather than two
// separate tools: giving one agent multiple tool schemas at once currently
// trips a bug somewhere in the llama.cpp/LiteLLM function-calling path we
// use locally (each tool alone works fine; two together return a "Cannot
// determine type of 'item'" 400 on every call, regardless of which tool
// the model picks).
class GetChannelDataTool implements Tool
{
    public function __construct(
        private readonly YoutubeAccount $account,
        private readonly YoutubeClient $youtube,
        private readonly YoutubeAnalyticsClient $analytics,
    ) {}

    public function description(): Stringable|string
    {
        return 'Get data about the channel. Use type "videos" for a list of recent videos '
            .'(title, publish date, views, likes, comments), or type "analytics" for the '
            .'channel\'s daily views/likes trend.';
    }

    public function handle(Request $request): Stringable|string
    {
        return match ($request['type'] ?? null) {
            'analytics' => $this->analytics($request),
            'videos' => $this->videos($request),
            default => 'Invalid type: must be "videos" or "analytics".',
        };
    }

    private function videos(Request $request): string
    {
        $limit = max(1, min(50, (int) ($request['limit'] ?? 20)));

        try {
            $videos = $this->youtube->listVideos($this->account, $limit);
        } catch (Throwable $e) {
            return 'Could not list videos: '.$e->getMessage();
        }

        return json_encode(array_map(fn (array $v) => [
            'title' => $v['title'],
            'published_at' => $v['published_at'],
            'views' => $v['view_count'],
            'likes' => $v['like_count'],
            'comments' => $v['comment_count'],
        ], $videos));
    }

    private function analytics(Request $request): string
    {
        $days = max(1, min(90, (int) ($request['days'] ?? 28)));

        try {
            return json_encode($this->analytics->channelDailyMetrics($this->account, $days));
        } catch (Throwable $e) {
            return 'Analytics are not available: '.$e->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required()->description('Either "videos" or "analytics".'),
            'limit' => $schema->integer()->description('For type=videos: how many recent videos, 1-50. Defaults to 20.'),
            'days' => $schema->integer()->description('For type=analytics: how many past days, 1-90. Defaults to 28.'),
        ];
    }
}
