<?php

namespace App\Http\Controllers;

use App\Models\ThumbnailTemplate;
use App\Services\Thumbnail\ThumbnailComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ThumbnailTemplateController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Settings/ThumbnailTemplate', [
            'template' => ThumbnailTemplate::current(),
            'fonts' => collect(ThumbnailTemplate::FONTS)
                ->map(fn ($font, $key) => ['value' => $key, 'label' => $font['label']])
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        ThumbnailTemplate::current()->update($this->validateTemplate($request));

        return back()->with('success', 'Thumbnail template updated.');
    }

    public function preview(Request $request, ThumbnailComposer $composer): JsonResponse
    {
        $template = new ThumbnailTemplate($this->validateTemplate($request));

        return response()->json([
            'data_url' => 'data:image/png;base64,'.base64_encode($composer->composeSample($template)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'game_font' => ['required', Rule::in(array_keys(ThumbnailTemplate::FONTS))],
            'game_font_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'boss_font' => ['required', Rule::in(array_keys(ThumbnailTemplate::FONTS))],
            'boss_font_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'stroke_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'stroke_width' => ['required', 'integer', 'min:0', 'max:20'],
            'gradient_height_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
    }
}
