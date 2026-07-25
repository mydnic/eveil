<?php

namespace App\Http\Controllers;

use App\Models\ThumbnailTemplate;
use App\Services\Thumbnail\ThumbnailComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ThumbnailTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/ThumbnailTemplates/Index', [
            'templates' => ThumbnailTemplate::query()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Settings/ThumbnailTemplates/Form', [
            'template' => null,
            'fonts' => $this->fontOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTemplate($request);
        $template = ThumbnailTemplate::create($data);

        if ($data['is_default'] ?? false) {
            $this->makeDefaultInternal($template);
        }

        return redirect()->route('settings.thumbnail-templates.index')->with('success', 'Template created.');
    }

    public function edit(ThumbnailTemplate $thumbnailTemplate): Response
    {
        return Inertia::render('Settings/ThumbnailTemplates/Form', [
            'template' => $thumbnailTemplate,
            'fonts' => $this->fontOptions(),
        ]);
    }

    public function update(Request $request, ThumbnailTemplate $thumbnailTemplate): RedirectResponse
    {
        $data = $this->validateTemplate($request);
        $thumbnailTemplate->update($data);

        if ($data['is_default'] ?? false) {
            $this->makeDefaultInternal($thumbnailTemplate);
        }

        return redirect()->route('settings.thumbnail-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(ThumbnailTemplate $thumbnailTemplate): RedirectResponse
    {
        if ($thumbnailTemplate->is_default) {
            return back()->with('error', "Can't delete the default template — set another one as default first.");
        }

        $thumbnailTemplate->delete();

        return back()->with('success', 'Template deleted.');
    }

    public function makeDefault(ThumbnailTemplate $thumbnailTemplate): RedirectResponse
    {
        $this->makeDefaultInternal($thumbnailTemplate);

        return back()->with('success', "\"{$thumbnailTemplate->name}\" is now the default template.");
    }

    public function preview(Request $request, ThumbnailComposer $composer): JsonResponse
    {
        $template = new ThumbnailTemplate($this->validateTemplate($request));

        return response()->json([
            'data_url' => 'data:image/png;base64,'.base64_encode($composer->composeSample($template)),
        ]);
    }

    private function makeDefaultInternal(ThumbnailTemplate $template): void
    {
        ThumbnailTemplate::query()->where('id', '!=', $template->id)->update(['is_default' => false]);
        $template->update(['is_default' => true]);
    }

    private function fontOptions(): Collection
    {
        return collect(ThumbnailTemplate::FONTS)
            ->map(fn ($font, $key) => ['value' => $key, 'label' => $font['label']])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
            'game_keywords' => ['nullable', 'string', 'max:500'],
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
