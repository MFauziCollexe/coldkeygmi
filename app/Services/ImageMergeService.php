<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageMergeService
{
    /**
     * Merge signature PNG ke dokumen asli menggunakan GD
     *
     * @param string $documentPath    Path ke dokumen relatif ke storage disk (e.g., 'purchase-requisitions/abc.jpg')
     * @param string $signatureBase64 Base64 PNG signature (transparent)
     * @param array  $position        ['x' => normalized 0-1, 'y' => normalized 0-1]
     * @param float  $scale           Scale signature (default 1.0)
     * @param string $outputFormat    'jpg'|'png'|'webp'
     * @param int    $quality         85 untuk JPEG
     * @return string                Binary image data (blob)
     */
    public function merge(
        string $documentPath,
        string $signatureBase64,
        array $position,
        float $scale = 1.0,
        string $outputFormat = 'jpg',
        int $quality = 85
    ): string {
        try {
            // 1. Load dokumen dari storage
            if (!Storage::disk('public')->exists($documentPath)) {
                throw new \Exception("Document file not found: {$documentPath}");
            }

            $fullPath = Storage::disk('public')->path($documentPath);

            // Load document dengan GD
            $docInfo = getimagesize($fullPath);
            $docMime = $docInfo['mime'];

            switch ($docMime) {
                case 'image/jpeg':
                    $document = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $document = imagecreatefrompng($fullPath);
                    break;
                case 'image/webp':
                    $document = imagecreatefromwebp($fullPath);
                    break;
                default:
                    throw new \Exception("Unsupported document format: {$docMime}");
            }

            if (!$document) {
                throw new \Exception('Failed to load document image');
            }

            $docWidth = imagesx($document);
            $docHeight = imagesy($document);

            // 2. Decode signature dari base64 data URL
            $signatureData = base64_decode(preg_replace(
                '/^data:image\/\w+;base64,/',
                '',
                $signatureBase64
            ));

            if ($signatureData === false) {
                throw new \Exception('Failed to decode signature data');
            }

            // Load signature PNG (harus PNG untuk transparency)
            $signature = imagecreatefromstring($signatureData);
            if (!$signature) {
                throw new \Exception('Failed to decode signature image');
            }

            // Enable alpha blending for signature (preserve transparency)
            imagealphablending($signature, true);
            imagesavealpha($signature, true);

            $sigWidth = imagesx($signature);
            $sigHeight = imagesy($signature);

            // 3. Resize signature sesuai scale
            $newSigWidth = (int)($sigWidth * $scale);
            $newSigHeight = (int)($sigHeight * $scale);

            if ($newSigWidth > 0 && $newSigHeight > 0 && ($newSigWidth !== $sigWidth || $newSigHeight !== $sigHeight)) {
                $resizedSig = imagecreatetruecolor($newSigWidth, $newSigHeight);
                imagealphablending($resizedSig, false);
                imagesavealpha($resizedSig, true);

                $transparent = imagecolorallocatealpha($resizedSig, 0, 0, 0, 127);
                imagefilledrectangle($resizedSig, 0, 0, $newSigWidth, $newSigHeight, $transparent);

                imagecopyresampled(
                    $resizedSig, $signature,
                    0, 0, 0, 0,
                    $newSigWidth, $newSigHeight,
                    $sigWidth, $sigHeight
                );

                imagedestroy($signature);
                $signature = $resizedSig;
            }

            // 4. Calculate position (normalized 0-1 → pixels)
            $maxX = max(0, $docWidth - $newSigWidth);
            $maxY = max(0, $docHeight - $newSigHeight);

            $x = (int)($position['x'] * $maxX);
            $y = (int)($position['y'] * $maxY);

            // 5. Merge: copy signature ke document dengan alpha blending
            // For GD, we need to handle alpha manually for proper blending
            $this->mergeImagesWithAlpha($document, $signature, $x, $y);

            // 6. Output ke buffer sesuai format
            ob_start();
            $success = false;

            switch ($outputFormat) {
                case 'jpg':
                case 'jpeg':
                    // For JPEG, we need to flatten alpha to white background
                    $flattened = $this->flattenAlphaToWhite($document);
                    imagejpeg($flattened, null, $quality);
                    imagedestroy($flattened);
                    break;
                case 'png':
                    imagepng($document, null, 6); // compression level 6
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($document, null, $quality);
                    } else {
                        // Fallback to jpeg if webp not supported
                        $flattened = $this->flattenAlphaToWhite($document);
                        imagejpeg($flattened, null, $quality);
                        imagedestroy($flattened);
                    }
                    break;
                default:
                    throw new \Exception("Unsupported output format: {$outputFormat}");
            }

            $blob = ob_get_clean();

            // Cleanup
            imagedestroy($document);
            imagedestroy($signature);

            return $blob;

        } catch (\Exception $e) {
            \Log::error('ImageMergeService::merge failed (GD): ' . $e->getMessage(), [
                'documentPath' => $documentPath,
                'position' => $position,
                'scale' => $scale,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Merge signature dengan alpha blending menggunakan GD
     */
    private function mergeImagesWithAlpha(&$dest, &$src, int $dstX, int $dstY): void
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $destW = imagesx($dest);
        $destH = imagesy($dest);

        // Make sure destination has alpha blending enabled
        imagealphablending($dest, true);
        imagesavealpha($dest, true);

        for ($y = 0; $y < $srcH; $y++) {
            for ($x = 0; $x < $srcW; $x++) {
                $destX = $dstX + $x;
                $destY = $dstY + $y;

                // Skip if outside destination bounds
                if ($destX >= $destW || $destY >= $destH) {
                    continue;
                }

                // Get pixel colors
                $srcColor = imagecolorat($src, $x, $y);
                $destColor = imagecolorat($dest, $destX, $destY);

                // Extract alpha (0-127, 0 = opaque, 127 = transparent)
                $srcAlpha = ($srcColor >> 24) & 0x7F;
                $destAlpha = ($destColor >> 24) & 0x7F;

                // Convert to RGBA
                $srcR = ($srcColor >> 16) & 0xFF;
                $srcG = ($srcColor >> 8) & 0xFF;
                $srcB = $srcColor & 0xFF;

                $destR = ($destColor >> 16) & 0xFF;
                $destG = ($destColor >> 8) & 0xFF;
                $destB = $destColor & 0xFF;

                // GD alpha: 0 = opaque, 127 = transparent.
                // Convert to opacity so the signature color stays dominant when the source stroke is opaque.
                $srcOpacity = (127 - $srcAlpha) / 127;
                $destOpacity = (127 - $destAlpha) / 127;
                $outOpacity = $srcOpacity + ($destOpacity * (1 - $srcOpacity));

                if ($outOpacity <= 0) {
                    continue;
                }

                $finalR = (int) round((($srcR * $srcOpacity) + ($destR * $destOpacity * (1 - $srcOpacity))) / $outOpacity);
                $finalG = (int) round((($srcG * $srcOpacity) + ($destG * $destOpacity * (1 - $srcOpacity))) / $outOpacity);
                $finalB = (int) round((($srcB * $srcOpacity) + ($destB * $destOpacity * (1 - $srcOpacity))) / $outOpacity);
                $finalA = (int) round(127 * (1 - $outOpacity));
                $finalA = max(0, min(127, $finalA));

                // Allocate color and set pixel
                $finalColor = imagecolorallocatealpha(
                    $dest,
                    $finalR,
                    $finalG,
                    $finalB,
                    $finalA
                );
                imagesetpixel($dest, $destX, $destY, $finalColor);
            }
        }
    }

    /**
     * Flatten image with alpha channel to white background (for JPEG)
     */
    private function flattenAlphaToWhite($img): \GdImage
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Create white background
        $flat = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefilledrectangle($flat, 0, 0, $width, $height, $white);

        // Copy original image over white background
        imagecopy($flat, $img, 0, 0, 0, 0, $width, $height);

        return $flat;
    }

    /**
     * Generate thumbnail dari gambar (GD version)
     */
    public function createThumbnail(string $imagePath, int $maxWidth = 300): string
    {
        try {
            $fullPath = storage_path('app/' . $imagePath);
            if (!file_exists($fullPath)) {
                throw new \Exception("Image not found: {$imagePath}");
            }

            $imageInfo = getimagesize($fullPath);
            $mime = $imageInfo['mime'];

            switch ($mime) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($fullPath);
                    break;
                case 'image/webp':
                    $img = imagecreatefromwebp($fullPath);
                    break;
                default:
                    throw new \Exception("Unsupported image format: {$mime}");
            }

            $width = imagesx($img);
            $height = imagesy($img);

            // Only resize if exceeds maxWidth
            if ($width > $maxWidth) {
                $newHeight = (int)($maxWidth / $width * $height);
                $width = $maxWidth;
                $height = $newHeight;

                $resized = imagecreatetruecolor($width, $height);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));
                imagedestroy($img);
                $img = $resized;
            }

            // Convert to JPEG (consistent thumbnail format)
            $flat = $this->flattenAlphaToWhite($img);
            imagedestroy($img);

            // Output to buffer
            ob_start();
            imagejpeg($flat, null, 80);
            $blob = ob_get_clean();
            imagedestroy($flat);

            return $blob;

        } catch (\Exception $e) {
            \Log::error('ImageMergeService::createThumbnail failed (GD): ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validasi apakah file adalah image yang didukung
     */
    public function isSupportedImage(string $mimeType): bool
    {
        return in_array(strtolower($mimeType), [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
        ], true);
    }
}
