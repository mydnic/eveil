<?php

namespace App\Http\Controllers;

use App\Services\YouTube\YoutubeAnalyticsClient;
use App\Services\YouTube\YoutubeClient;
use App\Support\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request, YoutubeClient $youtube, YoutubeAnalyticsClient $analytics): Response
    {
        $account = $request->user()->youtubeAccount;

        if (! $account) {
            return Inertia::render('Dashboard', ['connected' => false]);
        }

        $channel = null;
        $dailyMetrics = [];
        $analyticsError = null;

        try {
            $channel = $youtube->fetchChannel($youtube->accessToken($account));
        } catch (Throwable $e) {
            $analyticsError = "Could not load channel stats: {$e->getMessage()}";
        }

        try {
            $dailyMetrics = $analytics->channelDailyMetrics($account, 28);
        } catch (Throwable $e) {
            $analyticsError = "Analytics aren't available: {$e->getMessage()} You may need to reconnect your YouTube account to grant analytics access.";
        }

        $conversation = $request->user()->conversations()->latest('updated_at')->first();
        $chatMessages = ChatHistory::toUiMessages(
            $conversation ? $conversation->messages()->orderBy('created_at')->get() : new Collection
        );

        return Inertia::render('Dashboard', [
            'connected' => true,
            'channel' => $channel,
            'dailyMetrics' => $dailyMetrics,
            'analyticsError' => $analyticsError,
            'chatMessages' => $chatMessages,
        ]);
    }
}
