<?php

declare(strict_types=1);
use Rubick\RbPdf\Config\RbPdfConfiguration;
use Rubick\RbPdf\Templates\RbPdfTemplateWriter;

$packageRoot = dirname(__DIR__);
$imgDir = $packageRoot.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'img';

/**
 * Configuração portátil do RbPdf (espelha a árvore de chaves usada por {@see RbPdfConfiguration}).
 *
 * Fora do Laravel, injete paths via variáveis de ambiente ou mescle este retorno com um array
 * no construtor de {@see RbPdfTemplateWriter} / {@see RbPdfConfiguration::fromArray()}.
 *
 * Com Laravel, use {@code php artisan rbpdf:install} para publicar {@code config/rbpdf.php} no app
 * (ajusta {@code paths.public} para {@code public_path()}).
 *
 * Variáveis suportadas nos defaults:
 * - {@code PDF_FONTS_DIR}: diretório com arquivos .php/.z (MakeFont) da família Inter
 * - {@code RBPDF_HEADER_LOGO_PATH}, {@code RBPDF_FOOTER_LOGO_PATH}: logos absolutas
 * - {@code RBPDF_PUBLIC_PATH}: raiz para {@see RbPdfTemplateWriter::resolveLocalAssetPath()}
 * - {@code RBPDF_LOCALE}, {@code RBPDF_TIMEZONE}: Carbon no subtítulo padrão do template
 */
return [
    'fonts' => [
        'directory' => ($d = getenv('PDF_FONTS_DIR')) !== false && $d !== '' ? $d : '',
        'families' => [
            'Inter' => [
                'regular' => 'Inter-Regular.php',
                'bold' => 'Inter-Bold.php',
                'semibold' => 'Inter-SemiBold.php',
            ],
        ],
    ],

    'logos' => [
        'header' => ($h = getenv('RBPDF_HEADER_LOGO_PATH')) !== false && $h !== ''
            ? $h
            : $imgDir.DIRECTORY_SEPARATOR.'logo-rubick-white.png',
        'footer' => ($f = getenv('RBPDF_FOOTER_LOGO_PATH')) !== false && $f !== ''
            ? $f
            : $imgDir.DIRECTORY_SEPARATOR.'logo-rubick-footer.png',
    ],

    'paths' => [
        'public' => ($p = getenv('RBPDF_PUBLIC_PATH')) !== false && $p !== ''
            ? rtrim($p, '/\\')
            : $packageRoot.DIRECTORY_SEPARATOR.'public',
    ],

    'datetime' => [
        'locale' => ($l = getenv('RBPDF_LOCALE')) !== false && $l !== '' ? $l : 'en',
        'timezone' => ($t = getenv('RBPDF_TIMEZONE')) !== false && $t !== '' ? $t : 'UTC',
    ],

    'colors' => [
        'primary' => ['r' => 51, 'g' => 122, 'b' => 183],
        'text' => ['r' => 33, 'g' => 33, 'b' => 33],
        'text_light' => ['r' => 117, 'g' => 117, 'b' => 117],
        'border' => ['r' => 200, 'g' => 200, 'b' => 200],
        'row_separator' => ['r' => 220, 'g' => 220, 'b' => 220],
        'header_bg' => ['r' => 51, 'g' => 122, 'b' => 183],
        'header_fg' => ['r' => 255, 'g' => 255, 'b' => 255],
        'group_bg' => ['r' => 230, 'g' => 230, 'b' => 230],
    ],

    'margins' => [
        'left' => 5.0,
        'top' => 28.0,
        'right' => 5.0,
        'bottom' => 10.0,
        'footer_break' => 14.0,
    ],

    'layout' => [
        'header_bar_height' => 22.0,
        'header_logo' => [
            'x_offset' => 1.0,
            'y' => 5.0,
            'height' => 10.0,
            'text_start_x' => 22.0,
        ],
        'footer_logo' => [
            'width' => 20.0,
            'y_offset' => 1.5,
        ],
    ],
];
