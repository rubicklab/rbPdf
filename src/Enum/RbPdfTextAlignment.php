<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Alinhamento horizontal de texto em células PDF.
 *
 * Os backed values correspondem aos parâmetros de alinhamento do fawno/fpdf.
 */
enum RbPdfTextAlignment: string
{
    case Left = 'L';
    case Center = 'C';
    case Right = 'R';
    case Justify = 'J';
}
