<?php

namespace App\Http\Controllers;

use App\Services\YouTube\YoutubeClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class YoutubeAuthController extends Controller
{
    public function connect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/youtube'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function callback(Request $request, YoutubeClient $youtube): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        if (! $googleUser->refreshToken) {
            return to_route('dashboard')->with(
                'error',
                'Google did not return a refresh token. Remove Eveil from your Google account\'s connected apps and try connecting again.'
            );
        }

        try {
            $channel = $youtube->fetchChannel($googleUser->token);
        } catch (RequestException $e) {
            report($e);

            return to_route('dashboard')->with(
                'error',
                'Google rejected the request: '.($e->response->json('error.message') ?? $e->getMessage())
                .' — make sure "YouTube Data API v3" is enabled for your Google Cloud project.'
            );
        } catch (Throwable $e) {
            report($e);

            return to_route('dashboard')->with('error', 'Could not connect to YouTube: '.$e->getMessage());
        }

        $request->user()->youtubeAccount()->updateOrCreate([], [
            'channel_id' => $channel['id'],
            'channel_title' => $channel['title'],
            'channel_thumbnail_url' => $channel['thumbnail_url'],
            'uploads_playlist_id' => $channel['uploads_playlist_id'],
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken,
            'expires_at' => now()->addSeconds($googleUser->expiresIn),
        ]);

        return to_route('videos.index')->with('success', "Connected to {$channel['title']}.");
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->youtubeAccount?->delete();

        return to_route('dashboard')->with('success', 'YouTube account disconnected.');
    }
}
