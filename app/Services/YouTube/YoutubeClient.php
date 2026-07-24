<?php

namespace App\Services\YouTube;

use App\Models\YoutubeAccount;
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
     * List the channel's uploaded videos, most recent first.
     *
     * @return array<int, array{video_id: string, title: string, thumbnail_url: string, published_at: string}>
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
                $videos[] = [
                    'video_id' => $item['snippet']['resourceId']['videoId'],
                    'title' => $item['snippet']['title'],
                    'thumbnail_url' => $item['snippet']['thumbnails']['high']['url']
                        ?? $item['snippet']['thumbnails']['default']['url'],
                    'published_at' => $item['snippet']['publishedAt'],
                ];
            }

            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken && count($videos) < $limit);

        return $videos;
    }
}
