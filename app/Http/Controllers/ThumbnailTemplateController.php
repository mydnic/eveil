<?php

namespace App\Http\Controllers;

use App\Models\ThumbnailTemplate;
use App\Models\ThumbnailTemplateText;
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

        $template = ThumbnailTemplate::create($data['template']);
        $template->texts()->createMany($this->orderedTexts($data['texts']));

        if ($data['template']['is_default'] ?? false) {
            $this->makeDefaultInternal($template);
        }

        return redirect()->route('settings.thumbnail-templates.index')->with('success', 'Template created.');
    }

    public function edit(ThumbnailTemplate $thumbnailTemplate): Response
    {
        return Inertia::render('Settings/ThumbnailTemplates/Form', [
            'template' => $thumbnailTemplate->load('texts'),
            'fonts' => $this->fontOptions(),
        ]);
    }

    public function update(Request $request, ThumbnailTemplate $thumbnailTemplate): RedirectResponse
    {
        $data = $this->validateTemplate($request);

        $thumbnailTemplate->update($data['template']);
        $thumbnailTemplate->texts()->delete();
        $thumbnailTemplate->texts()->createMany($this->orderedTexts($data['texts']));

        if ($data['template']['is_default'] ?? false) {
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
        $data = $this->validateTemplate($request);

        $template = new ThumbnailTemplate($data['template']);
        $texts = collect($this->orderedTexts($data['texts']))->map(fn (array $text) => new ThumbnailTemplateText($text));

        return response()->json([
            'data_url' => 'data:image/png;base64,'.base64_encode($composer->composeSample($template, $texts)),
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
     * @param  array<int, array<string, mixed>>  $texts
     * @return array<int, array<string, mixed>>
     */
    private function orderedTexts(array $texts): array
    {
        return collect($texts)
            ->values()
            ->map(fn (array $text, int $index) => [...$text, 'sort_order' => $index])
            ->all();
    }

    /**
     * @return array{template: array<string, mixed>, texts: array<int, array<string, mixed>>}
     */
    private function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
            'game_keywords' => ['nullable', 'string', 'max:500'],
            'gradient_height_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'gradient_position' => ['required', Rule::in(['top', 'bottom', 'both'])],

            'texts' => ['required', 'array', 'min:1'],
            'texts.*.kind' => ['required', Rule::in(['game', 'boss', 'fixed'])],
            'texts.*.content' => ['nullable', 'string', 'max:200'],
            'texts.*.font' => ['required', Rule::in(array_keys(ThumbnailTemplate::FONTS))],
            'texts.*.font_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'texts.*.font_size' => ['required', 'integer', 'min:10', 'max:200'],
            'texts.*.x_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'texts.*.y_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'texts.*.align' => ['required', Rule::in(['left', 'center', 'right'])],
            'texts.*.rotation' => ['required', 'integer', 'min:-180', 'max:180'],
            'texts.*.stroke_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'texts.*.stroke_width' => ['required', 'integer', 'min:0', 'max:20'],
            'texts.*.uppercase' => ['sometimes', 'boolean'],
        ]);

        return [
            'template' => collect($validated)->except('texts')->all(),
            'texts' => $validated['texts'],
        ];
    }
}
