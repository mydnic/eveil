<?php

namespace App\Services\Thumbnail;

use App\Models\ThumbnailTemplate;
use GdImage;
use Illuminate\Support\Facades\Image;

class ThumbnailComposer
{
    private const WIDTH = 1280;

    private const HEIGHT = 720;

    private const MAX_GRADIENT_OPACITY = 0.85;

    private const MIN_FONT_SIZE = 18;

    private const TEXT_MARGIN = 80;

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
     * the game/boss text on top per the given (or current) template.
     *
     * @return string PNG bytes
     */
    public function compose(string $imageUrl, string $game, string $boss, ?ThumbnailTemplate $template = null): string
    {
        $cropped = Image::fromUrl($imageUrl)->cover(self::WIDTH, self::HEIGHT)->toPng()->toBytes();

        return $this->renderOnto($cropped, $game, $boss, $template);
    }

    /**
     * Render a sample thumbnail (synthetic background) for the given
     * template, so the settings screen can preview changes before saving.
     *
     * @return string PNG bytes
     */
    public function composeSample(ThumbnailTemplate $template): string
    {
        return $this->renderOnto($this->placeholderCanvas(), 'Sample Game', 'Sample Boss', $template);
    }

    private function renderOnto(string $croppedPngBytes, string $game, string $boss, ?ThumbnailTemplate $template): string
    {
        $template ??= ThumbnailTemplate::current();

        $canvas = imagecreatefromstring($croppedPngBytes);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $this->drawGradient($canvas, $template);
        $this->drawText($canvas, $template, $game, $boss);

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

    private function drawText(GdImage $canvas, ThumbnailTemplate $template, string $game, string $boss): void
    {
        $gameColor = $this->allocateColor($canvas, $template->game_font_color);
        $bossColor = $this->allocateColor($canvas, $template->boss_font_color);
        $strokeColor = $this->allocateColor($canvas, $template->stroke_color);

        $gameText = mb_strtoupper($game);
        $bossText = mb_strtoupper($boss);

        $bossFont = $template->bossFontPath();
        $gameFont = $template->gameFontPath();
        $maxWidth = self::WIDTH - self::TEXT_MARGIN * 2;

        $bossSize = $this->fitFontSize($bossFont, $bossText, 90, $maxWidth);
        $gameSize = $this->fitFontSize($gameFont, $gameText, 48, $maxWidth);

        $bossY = self::HEIGHT - 60;
        $gameY = $bossY - $bossSize - 30;

        $this->drawStrokedText($canvas, $gameSize, $gameFont, $gameText, $gameColor, $strokeColor, max(2, (int) round($template->stroke_width / 2)), $gameY);
        $this->drawStrokedText($canvas, $bossSize, $bossFont, $bossText, $bossColor, $strokeColor, $template->stroke_width, $bossY);
    }

    private function drawStrokedText(GdImage $canvas, int $size, string $font, string $text, int $color, int $strokeColor, int $strokeWidth, int $y): void
    {
        $x = $this->centeredX($size, $font, $text);
        $steps = max(8, $strokeWidth * 4);

        for ($i = 0; $i < $steps; $i++) {
            $angle = 2 * M_PI * $i / $steps;
            $dx = (int) round(cos($angle) * $strokeWidth);
            $dy = (int) round(sin($angle) * $strokeWidth);
            imagettftext($canvas, $size, 0, (int) round($x) + $dx, $y + $dy, $strokeColor, $font, $text);
        }

        imagettftext($canvas, $size, 0, (int) round($x), $y, $color, $font, $text);
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

    private function centeredX(int $size, string $font, string $text): float
    {
        return (self::WIDTH - $this->textWidth($size, $font, $text)) / 2;
    }

    private function allocateColor(GdImage $canvas, string $hex): int
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        return imagecolorallocate($canvas, $r, $g, $b);
    }
}
