<?php

namespace App\Http\Controllers;

use App\Services\YouTube\YoutubeClient;
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
}
