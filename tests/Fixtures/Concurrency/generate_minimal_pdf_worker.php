<?php

declare(strict_types=1);

/**
 * Worker CLI disparado por {@see RbPdfConcurrentGenerationTest}
 * via proc_open. Variáveis de ambiente obrigatórias: RBPDF_OUT_FILE, RBPDF_REPO_ROOT.
 * Opcionais: RBPDF_CACHE_DIR, TEST_TOKEN, UNIQUE_TEST_TOKEN (isolamento de cache em paralelo).
 */
$root = getenv('RBPDF_REPO_ROOT');
$out = getenv('RBPDF_OUT_FILE');
if (! is_string($root) || $root === '' || ! is_string($out) || $out === '') {
    fwrite(STDERR, "RBPDF_REPO_ROOT and RBPDF_OUT_FILE are required.\n");
    exit(1);
}

require $root.'/vendor/autoload.php';

use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfOutputMode;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\Tests\Features\Concurrency\RbPdfConcurrentGenerationTest;

$label = getenv('TEST_TOKEN') ?: (getenv('UNIQUE_TEST_TOKEN') ?: 'worker');
$pdf = new RbPdf;
$pdf
    ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4)
    ->font('Helvetica', RbPdfFontStyle::Regular, 12)
    ->cell(80, 10, 'token:'.$label, 0, 1, RbPdfTextAlignment::Left, false);

$pdf->output($out, RbPdfOutputMode::File);

exit(0);
