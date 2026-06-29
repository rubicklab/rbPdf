<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Orientação da página do documento PDF.
 *
 * Os backed values correspondem aos parâmetros esperados pela API do fawno/fpdf.
 */
enum RbPdfPageOrientation: string
{
    case Portrait = 'P';
    case Landscape = 'L';
}
