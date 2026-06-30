<?php

declare(strict_types=1);

/**
 * Configuração publicada no app Laravel (rodar {@code php artisan rbpdf:install}).
 *
 * Reaproveita os defaults do pacote (inclui caminhos absolutos para logos em vendor) e fixa
 * {@code paths.public} para a pasta {@code public} do projeto.
 */

use Rubick\RbPdf\Laravel\RbPdfServiceProvider;

$ref = new \ReflectionClass(RbPdfServiceProvider::class);
$packageRoot = dirname($ref->getFileName(), 3);
/** @var array<string, mixed> $base */
$base = require $packageRoot.'/config/rbpdf.php';

return array_replace_recursive($base, [
    'paths' => [
        'public' => public_path(),
    ],
]);
