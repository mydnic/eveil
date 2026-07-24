<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\YouTube\YoutubeAnalyticsClient;
use App\Services\YouTube\YoutubeClient;
use App\Support\ChatHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VideosController extends Controller
{
    public function index(Request $request, YoutubeClient $youtube): Response
    {
        $account = $request->user()->youtubeAccount;
        $videos = [];
        $error = null;

        if ($account) {
            try {
                $videos = $youtube->listVideos($account);
            } catch (Throwable $e) {
                $error = 'Could not load videos from YouTube: '.$e->getMessage();
            }
        }

        return Inertia::render('Videos/Index', [
            'connected' => (bool) $account,
            'channel' => $account ? [
                'title' => $account->channel_title,
                'thumbnail_url' => $account->channel_thumbnail_url,
            ] : null,
            'videos' => $videos,
            'error' => $error,
        ]);
    }

    public function show(Request $request, string $video, YoutubeClient $youtube, YoutubeAnalyticsClient $analytics): Response
    {
        $account = $request->user()->youtubeAccount;

        abort_unless($account, 403);

        $data = $youtube->fetchVideo($account, $video);

        $dailyMetrics = [];
        $analyticsError = null;

        try {
            $dailyMetrics = $analytics->videoDailyMetrics($account, $video, 28);
        } catch (Throwable $e) {
            $analyticsError = "Analytics aren't available: {$e->getMessage()} You may need to reconnect your YouTube account to grant analytics access.";
        }

        $videoModel = Video::forYoutubeId($video);
        $conversation = $videoModel->conversations()->latest('updated_at')->first();
        $chatMessages = $conversation
            ? ChatHistory::toUiMessages($conversation->messages()->orderBy('created_at')->get())
            : [];

        return Inertia::render('Videos/Show', [
            'video' => [
                'video_id' => $video,
                'title' => $data['snippet']['title'],
                'description' => $data['snippet']['description'] ?? '',
                'thumbnail_url' => $data['snippet']['thumbnails']['high']['url']
                    ?? $data['snippet']['thumbnails']['default']['url'],
                'published_at' => $data['snippet']['publishedAt'],
                'view_count' => isset($data['statistics']['viewCount']) ? (int) $data['statistics']['viewCount'] : null,
                'like_count' => isset($data['statistics']['likeCount']) ? (int) $data['statistics']['likeCount'] : null,
                'comment_count' => isset($data['statistics']['commentCount']) ? (int) $data['statistics']['commentCount'] : null,
            ],
            'dailyMetrics' => $dailyMetrics,
            'analyticsError' => $analyticsError,
            'chatMessages' => $chatMessages,
        ]);
    }
}
