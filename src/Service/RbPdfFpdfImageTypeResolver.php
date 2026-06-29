<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Service;

use Fawno\FPDF\FawnoFPDF;

/**
 * Descobre o tipo real de imagem em disco para encaixar na API {@see FawnoFPDF::Image()}
 * (tipos `jpg`, `png`, `gif`) ou, quando necessário, delega via GD.
 *
 * Retorna sempre um dos modos {@see self::MODE_FILE} (caminho + tipo FPDF) ou {@see self::MODE_GD}
 * (imagem rasterizada em memória para formatos que o FPDF não aceita diretamente do arquivo).
 */
final class RbPdfFpdfImageTypeResolver
{
    public const MODE_FILE = 'file';

    public const MODE_GD = 'gd';

    /** Tipo vazio: caller pode usar extensão do arquivo como fallback. */
    public const TYPE_UNKNOWN = '';

    /**
     * @return array{mode: self::MODE_FILE, path: string, type: string}|array{mode: self::MODE_GD, gd: \GdImage}
     */
    public static function resolve(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return ['mode' => self::MODE_FILE, 'path' => $path, 'type' => self::TYPE_UNKNOWN];
        }

        $header = @file_get_contents($path, false, null, 0, 16);
        if ($header !== false && $header !== '') {
            if (str_starts_with($header, "\xFF\xD8\xFF")) {
                return ['mode' => self::MODE_FILE, 'path' => $path, 'type' => 'jpg'];
            }

            if (str_starts_with($header, "\x89PNG\r\n\x1A\n") || str_starts_with($header, "\x89PNG")) {
                return ['mode' => self::MODE_FILE, 'path' => $path, 'type' => 'png'];
            }

            if (str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a')) {
                return ['mode' => self::MODE_FILE, 'path' => $path, 'type' => 'gif'];
            }
        }

        $info = @getimagesize($path);
        if ($info !== false) {
            $fpdfType = match ($info[2]) {
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_GIF => 'gif',
                default => null,
            };

            if ($fpdfType !== null) {
                return ['mode' => self::MODE_FILE, 'path' => $path, 'type' => $fpdfType];
            }
        }

        if (function_exists('imagecreatefromstring')) {
            $blob = @file_get_contents($path);
            if ($blob !== false && $blob !== '') {
                $gd = @imagecreatefromstring($blob);
                if ($gd !== false) {
                    return ['mode' => self::MODE_GD, 'gd' => $gd];
                }
            }
        }

        return ['mode' => self::MODE_FILE, 'path' => $path, 'type' => self::TYPE_UNKNOWN];
    }
}
