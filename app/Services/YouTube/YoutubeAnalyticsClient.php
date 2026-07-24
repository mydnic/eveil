<?php

namespace App\Services\YouTube;

use App\Models\YoutubeAccount;
use Illuminate\Support\Facades\Http;

class YoutubeAnalyticsClient
{
    private const API_BASE = 'https://youtubeanalytics.googleapis.com/v2';

    public function __construct(private readonly YoutubeClient $youtube) {}

    /**
     * Daily views/likes for a single video over the last $days days.
     *
     * @return array<int, array{date: string, views: int, likes: int}>
     */
    public function videoDailyMetrics(YoutubeAccount $account, string $videoId, int $days = 28): array
    {
        return $this->dailyMetrics($account, $days, "video=={$videoId}");
    }

    /**
     * Daily views/likes for the whole channel over the last $days days.
     *
     * @return array<int, array{date: string, views: int, likes: int}>
     */
    public function channelDailyMetrics(YoutubeAccount $account, int $days = 28): array
    {
        return $this->dailyMetrics($account, $days);
    }

    /**
     * @return array<int, array{date: string, views: int, likes: int}>
     */
    private function dailyMetrics(YoutubeAccount $account, int $days, ?string $filters = null): array
    {
        $accessToken = $this->youtube->accessToken($account);

        $response = Http::withToken($accessToken)
            ->get(self::API_BASE.'/reports', array_filter([
                'ids' => 'channel==MINE',
                'startDate' => now()->subDays($days)->toDateString(),
                'endDate' => now()->toDateString(),
                'metrics' => 'views,likes',
                'dimensions' => 'day',
                'filters' => $filters,
                'sort' => 'day',
            ]))
            ->throw()
            ->json();

        return collect($response['rows'] ?? [])
            ->map(fn (array $row) => [
                'date' => $row[0],
                'views' => (int) $row[1],
                'likes' => (int) $row[2],
            ])
            ->all();
    }
}
