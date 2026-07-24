<?php

namespace App\Http\Controllers;

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

        if (empty($candidates)) {
            return response()->json(['message' => 'No candidate images found for "'.$parsed['boss'].'".'], 404);
        }

        return response()->json([
            'game' => $parsed['game'],
            'boss' => $parsed['boss'],
            'candidates' => $candidates,
        ]);
    }

    public function preview(Request $request, ThumbnailComposer $composer): JsonResponse
    {
        $data = $this->validateComposeRequest($request);

        try {
            $bytes = $composer->compose($data['image_url'], $data['game'], $data['boss']);
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
            $bytes = $composer->compose($data['image_url'], $data['game'], $data['boss']);
            $youtube->setThumbnail($account, $video, $bytes);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not upload the thumbnail: '.$e->getMessage()], 422);
        }

        return response()->json(['data_url' => $this->toDataUrl($bytes)]);
    }

    /**
     * @return array{image_url: string, game: string, boss: string}
     */
    private function validateComposeRequest(Request $request): array
    {
        return $request->validate([
            'image_url' => ['required', 'url'],
            'game' => ['required', 'string'],
            'boss' => ['required', 'string'],
        ]);
    }

    private function toDataUrl(string $pngBytes): string
    {
        return 'data:image/png;base64,'.base64_encode($pngBytes);
    }
}
