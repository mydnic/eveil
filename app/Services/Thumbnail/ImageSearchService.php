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

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    // Brave's plan caps requests at 1/second. findCandidates() makes two
    // calls (web search, then image search) per invocation, so without
    // spacing them out the second one reliably gets 429'd.
    private static ?float $lastRequestAt = null;

    /**
     * Find candidate boss art: the wiki's og:image first (usually the best
     * quality artwork), filled out with general image search results,
     * highest resolution first.
     *
     * @return array<int, array{url: string, width: ?int, height: ?int}> deduplicated by URL
     */
    public function findCandidates(string $game, string $boss, int $count = 20): array
    {
        $candidates = [];
        $seen = [];

        if ($wikiImage = $this->findWikiImage($game, $boss)) {
            $candidates[] = ['url' => $wikiImage, 'width' => null, 'height' => null];
            $seen[$wikiImage] = true;
        }

        $searched = $this->imageSearch("{$boss} {$game} boss", $count);

        // Highest resolution first, so the best-quality options surface at
        // the top of the picker.
        usort($searched, fn (array $a, array $b) => ($b['width'] * $b['height']) <=> ($a['width'] * $a['height']));

        foreach ($searched as $result) {
            if (count($candidates) >= $count) {
                break;
            }

            if (! isset($seen[$result['url']])) {
                $candidates[] = $result;
                $seen[$result['url']] = true;
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
        $response = $this->braveGet(self::WEB_SEARCH_URL, ['q' => $query, 'count' => 10]);

        return array_column($response['web']['results'] ?? [], 'url');
    }

    /**
     * @return array<int, array{url: string, width: int, height: int}>
     */
    private function imageSearch(string $query, int $count): array
    {
        $response = $this->braveGet(self::IMAGE_SEARCH_URL, ['q' => $query, 'count' => max(20, $count * 2)]);

        return array_map(
            fn (array $result) => [
                'url' => $result['properties']['url'],
                'width' => $result['properties']['width'] ?? null,
                'height' => $result['properties']['height'] ?? null,
            ],
            $response['results'] ?? []
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function braveGet(string $url, array $query): array
    {
        if (self::$lastRequestAt !== null) {
            $sinceLastRequest = microtime(true) - self::$lastRequestAt;
            $minInterval = 1.05;

            if ($sinceLastRequest < $minInterval) {
                usleep((int) round(($minInterval - $sinceLastRequest) * 1_000_000));
            }
        }

        $response = Http::withHeaders(['X-Subscription-Token' => $this->apiKey()])
            ->get($url, $query)
            ->throw()
            ->json();

        self::$lastRequestAt = microtime(true);

        return $response;
    }

    private function extractOgImage(string $url): ?string
    {
        try {
            $html = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(5)->get($url)->body();
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
