<?php

namespace App\Services\Thumbnail;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImageSearchService
{
    private const WEB_SEARCH_URL = 'https://api.search.brave.com/res/v1/web/search';

    private const IMAGE_SEARCH_URL = 'https://api.search.brave.com/res/v1/images/search';

    private const WIKI_HOST_HINTS = ['fandom.com', 'wiki', 'fextralife.com'];

    /**
     * Find candidate boss art: the wiki's og:image first (usually the best
     * quality artwork), filled out with general image search results.
     *
     * @return array<int, string> image URLs, deduplicated
     */
    public function findCandidates(string $game, string $boss, int $count = 8): array
    {
        $candidates = [];

        if ($wikiImage = $this->findWikiImage($game, $boss)) {
            $candidates[] = $wikiImage;
        }

        foreach ($this->imageSearch("{$boss} {$game} boss", $count) as $url) {
            if (count($candidates) >= $count) {
                break;
            }

            if (! in_array($url, $candidates, true)) {
                $candidates[] = $url;
            }
        }

        return $candidates;
    }

    private function findWikiImage(string $game, string $boss): ?string
    {
        foreach ($this->webSearch("{$game} {$boss} wiki") as $url) {
            if (! Str::contains($url, self::WIKI_HOST_HINTS)) {
                continue;
            }

            if ($image = $this->extractOgImage($url)) {
                return $image;
            }
        }

        return null;
    }

    /**
     * @return array<int, string> result page URLs
     */
    private function webSearch(string $query): array
    {
        $response = Http::withHeaders(['X-Subscription-Token' => $this->apiKey()])
            ->get(self::WEB_SEARCH_URL, ['q' => $query, 'count' => 10])
            ->throw()
            ->json();

        return array_column($response['web']['results'] ?? [], 'url');
    }

    /**
     * @return array<int, string> image URLs
     */
    private function imageSearch(string $query, int $count): array
    {
        $response = Http::withHeaders(['X-Subscription-Token' => $this->apiKey()])
            ->get(self::IMAGE_SEARCH_URL, ['q' => $query, 'count' => max(10, $count * 2)])
            ->throw()
            ->json();

        return array_map(
            fn (array $result) => $result['properties']['url'],
            $response['results'] ?? []
        );
    }

    private function extractOgImage(string $url): ?string
    {
        try {
            $html = Http::timeout(5)->get($url)->body();
        } catch (Throwable) {
            return null;
        }

        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }

        // Some pages declare the attributes in reverse order.
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function apiKey(): string
    {
        return config('services.brave.api_key') ?? throw new RuntimeException(
            'BRAVE_API_KEY is not configured. Add it to your .env to search for thumbnail images.'
        );
    }
}
