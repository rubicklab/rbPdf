<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Tamanho da página do documento PDF.
 *
 * Os backed values correspondem aos nomes de tamanho aceitos pelo fawno/fpdf.
 */
enum RbPdfPageSize: string
{
    case A4 = 'A4';
    case Letter = 'Letter';
    case Legal = 'Legal';
    case A3 = 'A3';
    case A5 = 'A5';
    case A6 = 'A6';
    case B4 = 'B4';
    case B5 = 'B5';
    case Folio = 'Folio';
    case Executive = 'Executive';
    case Tabloid = 'Tabloid';
}
