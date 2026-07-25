<?php

namespace App\Services\Thumbnail;

use App\Models\ThumbnailTemplate;
use App\Models\ThumbnailTemplateText;
use GdImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Image;
use RuntimeException;

class ThumbnailComposer
{
    private const WIDTH = 1280;

    private const HEIGHT = 720;

    private const MAX_GRADIENT_OPACITY = 0.85;

    private const MIN_FONT_SIZE = 12;

    private const TEXT_MARGIN = 60;

    // Some image hosts return an HTML challenge/error page instead of the
    // image for requests that don't look like a browser.
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    /**
     * Split a "Game - Boss" video title into its parts, or null if the
     * title doesn't follow that convention.
     *
     * @return array{game: string, boss: string}|null
     */
    public function parseTitle(string $title): ?array
    {
        if (! str_contains($title, ' - ')) {
            return null;
        }

        [$game, $boss] = explode(' - ', $title, 2);
        $game = trim($game);
        $boss = trim($boss);

        return $game !== '' && $boss !== '' ? compact('game', 'boss') : null;
    }

    /**
     * Download the source image, cover-crop it to thumbnail size, and draw
     * the template's text layers on top.
     *
     * @return string PNG bytes
     */
    public function compose(string $imageUrl, string $game, string $boss, ThumbnailTemplate $template): string
    {
        $cropped = Image::fromBytes($this->download($imageUrl))->cover(self::WIDTH, self::HEIGHT)->toPng()->toBytes();

        return $this->renderOnto($cropped, $game, $boss, $template, $template->texts);
    }

    private function download(string $url): string
    {
        $response = Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
            // GD can decode jpeg/png/webp/gif but not avif, so ask hosts that
            // do format negotiation (e.g. image CDNs) to avoid it.
            'Accept' => 'image/jpeg,image/png,image/webp,image/gif,image/*;q=0.5,*/*;q=0.1',
        ])->timeout(15)->get($url);

        $contentType = $response->header('Content-Type', '');

        if (! $response->successful() || ! str_starts_with($contentType, 'image/') || $contentType === 'image/avif') {
            throw new RuntimeException("That image couldn't be used (got \"{$contentType}\"). Try another candidate.");
        }

        return $response->body();
    }

    /**
     * Render a sample thumbnail (synthetic background) for the given
     * template, so the settings screen can preview changes before saving.
     * $texts overrides the template's persisted layers — used while editing,
     * before the template (and its new layers) has been saved.
     *
     * @return string PNG bytes
     */
    public function composeSample(ThumbnailTemplate $template, ?Collection $texts = null): string
    {
        return $this->renderOnto($this->placeholderCanvas(), 'Sample Game', 'Sample Boss', $template, $texts ?? $template->texts);
    }

    /**
     * @param  Collection<int, ThumbnailTemplateText>  $texts
     */
    private function renderOnto(string $croppedPngBytes, string $game, string $boss, ThumbnailTemplate $template, Collection $texts): string
    {
        $canvas = imagecreatefromstring($croppedPngBytes);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $this->drawGradient($canvas, $template);

        foreach ($texts as $layer) {
            $this->drawTextLayer($canvas, $layer, $layer->resolveContent($game, $boss));
        }

        ob_start();
        imagepng($canvas);
        $bytes = ob_get_clean();
        imagedestroy($canvas);

        return $bytes;
    }

    private function placeholderCanvas(): string
    {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / self::HEIGHT;
            $color = imagecolorallocate($canvas, (int) round(30 + 20 * $t), (int) round(35 + 20 * $t), (int) round(45 + 25 * $t));
            imageline($canvas, 0, $y, self::WIDTH, $y, $color);
        }

        ob_start();
        imagepng($canvas);
        $bytes = ob_get_clean();
        imagedestroy($canvas);

        return $bytes;
    }

    private function drawGradient(GdImage $canvas, ThumbnailTemplate $template): void
    {
        $gradientHeight = (int) round(self::HEIGHT * $template->gradient_height_percent / 100);
        $startY = self::HEIGHT - $gradientHeight;

        for ($y = $startY; $y < self::HEIGHT; $y++) {
            $progress = ($y - $startY) / $gradientHeight;
            $alpha = (int) round(127 * (1 - $progress * self::MAX_GRADIENT_OPACITY));
            $color = imagecolorallocatealpha($canvas, 0, 0, 0, $alpha);
            imageline($canvas, 0, $y, self::WIDTH, $y, $color);
        }
    }

    private function drawTextLayer(GdImage $canvas, ThumbnailTemplateText $layer, string $text): void
    {
        if (trim($text) === '') {
            return;
        }

        $font = $layer->fontPath();
        $maxWidth = self::WIDTH - self::TEXT_MARGIN * 2;
        $size = $this->fitFontSize($font, $text, $layer->font_size, $maxWidth);
        $width = $this->textWidth($size, $font, $text);

        $anchorX = self::WIDTH * $layer->x_percent / 100;
        $anchorY = self::HEIGHT * $layer->y_percent / 100;

        $x = match ($layer->align) {
            'left' => $anchorX,
            'right' => $anchorX - $width,
            default => $anchorX - $width / 2,
        };

        $color = $this->allocateColor($canvas, $layer->font_color);
        $strokeColor = $this->allocateColor($canvas, $layer->stroke_color);

        $this->drawStrokedText(
            $canvas, $size, $font, $text, $color, $strokeColor, $layer->stroke_width,
            (int) round($x), (int) round($anchorY), $layer->rotation,
        );
    }

    private function drawStrokedText(
        GdImage $canvas, int $size, string $font, string $text, int $color, int $strokeColor,
        int $strokeWidth, int $x, int $y, int $angle = 0,
    ): void {
        $steps = max(8, $strokeWidth * 4);

        for ($i = 0; $i < $steps; $i++) {
            $stepAngle = 2 * M_PI * $i / $steps;
            $dx = (int) round(cos($stepAngle) * $strokeWidth);
            $dy = (int) round(sin($stepAngle) * $strokeWidth);
            imagettftext($canvas, $size, $angle, $x + $dx, $y + $dy, $strokeColor, $font, $text);
        }

        imagettftext($canvas, $size, $angle, $x, $y, $color, $font, $text);
    }

    private function fitFontSize(string $font, string $text, int $baseSize, int $maxWidth): int
    {
        $size = $baseSize;

        while ($size > self::MIN_FONT_SIZE && $this->textWidth($size, $font, $text) > $maxWidth) {
            $size -= 2;
        }

        return $size;
    }

    private function textWidth(int $size, string $font, string $text): float
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[2] - $box[0];
    }

    private function allocateColor(GdImage $canvas, string $hex): int
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        return imagecolorallocate($canvas, $r, $g, $b);
    }
}
