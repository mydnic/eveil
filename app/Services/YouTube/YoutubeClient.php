<?php

namespace App\Services\YouTube;

use App\Models\YoutubeAccount;
use DateInterval;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YoutubeClient
{
    private const API_BASE = 'https://www.googleapis.com/youtube/v3';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Fetch the authenticated Google account's own YouTube channel.
     *
     * @return array{id: string, title: string, thumbnail_url: ?string, uploads_playlist_id: string}
     */
    public function fetchChannel(string $accessToken): array
    {
        $channel = Http::withToken($accessToken)
            ->get(self::API_BASE.'/channels', [
                'part' => 'snippet,contentDetails',
                'mine' => 'true',
            ])
            ->throw()
            ->json('items.0');

        if (! $channel) {
            throw new RuntimeException('This Google account has no YouTube channel.');
        }

        return [
            'id' => $channel['id'],
            'title' => $channel['snippet']['title'],
            'thumbnail_url' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
            'uploads_playlist_id' => $channel['contentDetails']['relatedPlaylists']['uploads'],
        ];
    }

    /**
     * Return a valid access token for the account, refreshing it first if expired.
     */
    public function accessToken(YoutubeAccount $account): string
    {
        if (! $account->isExpired()) {
            return $account->access_token;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $account->update([
            'access_token' => $response['access_token'],
            'expires_at' => now()->addSeconds($response['expires_in']),
        ]);

        return $account->access_token;
    }

    /**
     * List the channel's uploaded videos, most recent first, enriched with
     * stats (views, likes, comments), visibility and duration.
     *
     * @return array<int, array{
     *     video_id: string, title: string, thumbnail_url: string, published_at: string,
     *     view_count: ?int, like_count: ?int, comment_count: ?int, privacy_status: string, duration: string
     * }>
     */
    public function listVideos(YoutubeAccount $account, int $limit = 50): array
    {
        $accessToken = $this->accessToken($account);
        $videos = [];
        $pageToken = null;

        do {
            $response = Http::withToken($accessToken)
                ->get(self::API_BASE.'/playlistItems', array_filter([
                    'part' => 'snippet',
                    'playlistId' => $account->uploads_playlist_id,
                    'maxResults' => min(50, $limit - count($videos)),
                    'pageToken' => $pageToken,
                ]))
                ->throw()
                ->json();

            foreach ($response['items'] as $item) {
                $videoId = $item['snippet']['resourceId']['videoId'];

                $videos[$videoId] = [
                    'video_id' => $videoId,
                    'title' => $item['snippet']['title'],
                    'thumbnail_url' => $item['snippet']['thumbnails']['high']['url']
                        ?? $item['snippet']['thumbnails']['default']['url'],
                    'published_at' => $item['snippet']['publishedAt'],
                ];
            }

            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken && count($videos) < $limit);

        $this->attachVideoDetails($accessToken, $videos);

        return array_values($videos);
    }

    /**
     * Fetch statistics, visibility and duration for a batch of videos and
     * merge them into the given (video_id-keyed) array, in place.
     */
    private function attachVideoDetails(string $accessToken, array &$videos): void
    {
        foreach (array_chunk(array_keys($videos), 50) as $chunk) {
            $response = Http::withToken($accessToken)
                ->get(self::API_BASE.'/videos', [
                    'part' => 'statistics,status,contentDetails',
                    'id' => implode(',', $chunk),
                ])
                ->throw()
                ->json();

            foreach ($response['items'] as $item) {
                $stats = $item['statistics'];

                $videos[$item['id']] = [
                    ...$videos[$item['id']],
                    'view_count' => isset($stats['viewCount']) ? (int) $stats['viewCount'] : null,
                    'like_count' => isset($stats['likeCount']) ? (int) $stats['likeCount'] : null,
                    'comment_count' => isset($stats['commentCount']) ? (int) $stats['commentCount'] : null,
                    'privacy_status' => $item['status']['privacyStatus'],
                    'duration' => $this->formatDuration($item['contentDetails']['duration']),
                ];
            }
        }
    }

    private function formatDuration(string $iso8601): string
    {
        $interval = new DateInterval($iso8601);
        $hours = $interval->h + ($interval->d * 24);

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $interval->i, $interval->s)
            : sprintf('%d:%02d', $interval->i, $interval->s);
    }
}
