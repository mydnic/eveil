<?php

namespace App\Http\Controllers;

use App\Models\ThumbnailTemplate;
use App\Services\Thumbnail\ImageSearchService;
use App\Services\Thumbnail\ThumbnailComposer;
use App\Services\YouTube\YoutubeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ThumbnailController extends Controller
{
    public function candidates(Request $request, ThumbnailComposer $composer, ImageSearchService $search): JsonResponse
    {
        $data = $request->validate(['title' => ['required', 'string']]);

        $parsed = $composer->parseTitle($data['title']);

        if (! $parsed) {
            return response()->json([
                'message' => 'This video\'s title doesn\'t follow the "Game - Boss" format, so a thumbnail can\'t be generated automatically.',
            ], 422);
        }

        try {
            $candidates = $search->findCandidates($parsed['game'], $parsed['boss']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Image search failed: '.$e->getMessage()], 502);
        }

        return response()->json([
            'game' => $parsed['game'],
            'boss' => $parsed['boss'],
            'search_query' => $search->defaultQuery($parsed['game'], $parsed['boss']),
            'candidates' => $candidates,
            'template_id' => ThumbnailTemplate::forGame($parsed['game'])->id,
            'templates' => ThumbnailTemplate::query()->orderByDesc('is_default')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function search(Request $request, ImageSearchService $search): JsonResponse
    {
        $data = $request->validate(['query' => ['required', 'string', 'max:200']]);

        try {
            $candidates = $search->search($data['query']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Image search failed: '.$e->getMessage()], 502);
        }

        return response()->json(['candidates' => $candidates]);
    }

    public function preview(Request $request, ThumbnailComposer $composer): JsonResponse
    {
        $data = $this->validateComposeRequest($request);

        try {
            $bytes = $composer->compose($data['image_url'], $data['game'], $data['boss'], $this->resolveTemplate($data));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not generate the thumbnail: '.$e->getMessage()], 422);
        }

        return response()->json(['data_url' => $this->toDataUrl($bytes)]);
    }

    public function publish(Request $request, string $video, ThumbnailComposer $composer, YoutubeClient $youtube): JsonResponse
    {
        $data = $this->validateComposeRequest($request);

        $account = $request->user()->youtubeAccount;

        if (! $account) {
            return response()->json(['message' => 'YouTube is not connected.'], 403);
        }

        try {
            $bytes = $composer->compose($data['image_url'], $data['game'], $data['boss'], $this->resolveTemplate($data));
            $youtube->setThumbnail($account, $video, $bytes);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not upload the thumbnail: '.$e->getMessage()], 422);
        }

        return response()->json(['data_url' => $this->toDataUrl($bytes)]);
    }

    /**
     * @param  array{image_url: string, game: string, boss: string, template_id: int|null}  $data
     */
    private function resolveTemplate(array $data): ThumbnailTemplate
    {
        return $data['template_id']
            ? ThumbnailTemplate::findOrFail($data['template_id'])
            : ThumbnailTemplate::forGame($data['game']);
    }

    /**
     * @return array{image_url: string, game: string, boss: string, template_id: int|null}
     */
    private function validateComposeRequest(Request $request): array
    {
        return $request->validate([
            'image_url' => ['required', 'url'],
            'game' => ['required', 'string'],
            'boss' => ['required', 'string'],
            'template_id' => ['nullable', 'integer', 'exists:thumbnail_templates,id'],
        ]);
    }

    private function toDataUrl(string $pngBytes): string
    {
        return 'data:image/png;base64,'.base64_encode($pngBytes);
    }
}
