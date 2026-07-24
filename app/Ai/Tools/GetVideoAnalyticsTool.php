<?php

namespace App\Ai\Tools;

use App\Models\YoutubeAccount;
use App\Services\YouTube\YoutubeAnalyticsClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class GetVideoAnalyticsTool implements Tool
{
    public function __construct(
        private readonly YoutubeAccount $account,
        private readonly string $videoId,
        private readonly YoutubeAnalyticsClient $analytics,
    ) {}

    public function description(): Stringable|string
    {
        return 'Get this video\'s daily views and likes for a given number of past days (default 28, max 90).';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = (int) ($request['days'] ?? 28);
        $days = max(1, min(90, $days));

        try {
            $metrics = $this->analytics->videoDailyMetrics($this->account, $this->videoId, $days);
        } catch (Throwable $e) {
            return 'Analytics are not available: '.$e->getMessage();
        }

        return json_encode($metrics);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->description('Number of past days to fetch, between 1 and 90.'),
        ];
    }
}
