<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Estilo de fonte para texto em documentos PDF.
 *
 * Os backed values correspondem aos parâmetros de estilo do fawno/fpdf.
 * Regular usa string vazia, conforme a API do FPDF.
 */
enum RbPdfFontStyle: string
{
    case Regular = '';
    case Bold = 'B';
    case Italic = 'I';
    case BoldItalic = 'BI';
}
