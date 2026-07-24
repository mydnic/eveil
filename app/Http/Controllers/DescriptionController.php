<?php

namespace App\Http\Controllers;

use App\Ai\Agents\DescriptionWriter;
use App\Services\YouTube\YoutubeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DescriptionController extends Controller
{
    public function generate(Request $request, string $video, YoutubeClient $youtube): JsonResponse
    {
        $account = $request->user()->youtubeAccount;

        if (! $account) {
            return response()->json(['message' => 'YouTube is not connected.'], 403);
        }

        try {
            $data = $youtube->fetchVideo($account, $video);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not load the video: '.$e->getMessage()], 422);
        }

        $currentDescription = $data['snippet']['description'] ?? '';

        $context = "Title: {$data['snippet']['title']}\n".
            'Views: '.($data['statistics']['viewCount'] ?? 'unknown')."\n".
            ($currentDescription !== '' ? "Current description:\n{$currentDescription}\n" : '').
            "\nWrite a new description for this video.";

        try {
            // Local "thinking" models can take well over a minute to respond.
            $response = (new DescriptionWriter)->prompt($context, timeout: 170);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'AI generation failed: '.$e->getMessage()], 502);
        }

        return response()->json([
            'description' => trim((string) $response),
            'current_description' => $currentDescription,
        ]);
    }

    public function publish(Request $request, string $video, YoutubeClient $youtube): JsonResponse
    {
        $data = $request->validate(['description' => ['required', 'string', 'max:5000']]);

        $account = $request->user()->youtubeAccount;

        if (! $account) {
            return response()->json(['message' => 'YouTube is not connected.'], 403);
        }

        try {
            $youtube->updateDescription($account, $video, $data['description']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not update the description: '.$e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }
}
