<?php

namespace App\Helpers;

class PdfHelper
{
    /**
     * Retourne le logo en base64, redimensionné à max 200×80 px et compressé.
     * Réduit drastiquement le poids du PDF généré par DomPDF.
     */
    public static function logoBase64(?string $configuredPath = null): ?string
    {
        $path = null;

        if ($configuredPath) {
            $candidate = storage_path('app/public/' . $configuredPath);
            if (file_exists($candidate)) {
                $path = $candidate;
            }
        }

        if (! $path) {
            $fallback = public_path('logo_scn.jpg');
            if (file_exists($fallback)) {
                $path = $fallback;
            }
        }

        if (! $path) {
            return null;
        }

        return self::resizeAndEncode($path);
    }

    private static function resizeAndEncode(string $path): ?string
    {
        $mime = mime_content_type($path);

        // Si GD n'est pas disponible on encode brut (comportement précédent)
        if (! extension_loaded('gd')) {
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }

        $src = match (true) {
            str_contains($mime, 'png')  => @imagecreatefrompng($path),
            str_contains($mime, 'gif')  => @imagecreatefromgif($path),
            default                     => @imagecreatefromjpeg($path),
        };

        if (! $src) {
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }

        [$origW, $origH] = [imagesx($src), imagesy($src)];

        $maxW = 200;
        $maxH = 80;

        $ratio  = min($maxW / $origW, $maxH / $origH, 1.0); // ne pas agrandir
        $newW   = (int) round($origW * $ratio);
        $newH   = (int) round($origH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        // Préserver la transparence pour PNG
        if (str_contains($mime, 'png')) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        ob_start();
        if (str_contains($mime, 'png')) {
            imagepng($dst, null, 7); // compression 0-9, 7 = bon compromis
            $outMime = 'image/png';
        } else {
            imagejpeg($dst, null, 75); // qualité 75 % suffit pour un logo en PDF
            $outMime = 'image/jpeg';
        }
        $data = ob_get_clean();
        imagedestroy($dst);

        return 'data:' . $outMime . ';base64,' . base64_encode($data);
    }
}
