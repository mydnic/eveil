<?php

namespace App\Http\Controllers;

use App\Models\ChannelProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChannelProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Settings/ChannelProfile', [
            'profile' => ChannelProfile::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:1000'],
            'tone_of_voice' => ['nullable', 'string', 'max:1000'],
            'audience' => ['nullable', 'string', 'max:1000'],
            'extra_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        ChannelProfile::current()->update($data);

        return back()->with('success', 'Channel profile updated.');
    }
}
