<?php

namespace App\Support;

use Throwable;

/**
 * Turns a local image into a small data: URI for embedding in PDFs. Images
 * are scaled down first so a report with many photos stays small and fast
 * to render; the PDF renderer never fetches files or URLs itself.
 */
final class PdfImage
{
    /** Larger files are not read at all. */
    private const MAX_FILE_BYTES = 15 * 1024 * 1024;

    /**
     * @param  int  $maxSize  Longest side, in pixels, of the embedded image.
     * @param  bool  $keepTransparency  PNG output (for logos) instead of JPEG.
     * @param  bool  $square  Centre-crop to a square first, since PDFs cannot crop a photo into a square frame.
     */
    public static function dataUri(string $path, int $maxSize, bool $keepTransparency = false, bool $square = false): ?string
    {
        if (! is_file($path) || filesize($path) > self::MAX_FILE_BYTES) {
            return null;
        }

        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                return null;
            }

            if (! function_exists('imagecreatefromstring')) {
                return self::encode($contents, (string) mime_content_type($path));
            }

            $source = imagecreatefromstring($contents);

            if ($source === false) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $sourceX = 0;
            $sourceY = 0;

            if ($square) {
                $side = min($width, $height);
                $sourceX = intdiv($width - $side, 2);
                $sourceY = intdiv($height - $side, 2);
                $width = $height = $side;
            }

            $scale = min(1, $maxSize / max($width, $height));
            $image = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

            if ($keepTransparency) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
            } else {
                imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 255, 255));
            }

            imagecopyresampled($image, $source, 0, 0, $sourceX, $sourceY, imagesx($image), imagesy($image), $width, $height);

            ob_start();
            $keepTransparency ? imagepng($image, null, 9) : imagejpeg($image, null, 82);

            return self::encode((string) ob_get_clean(), $keepTransparency ? 'image/png' : 'image/jpeg');
        } catch (Throwable) {
            return null;
        }
    }

    private static function encode(string $contents, string $mimeType): ?string
    {
        if (! in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return null;
        }

        return "data:{$mimeType};base64,".base64_encode($contents);
    }
}
