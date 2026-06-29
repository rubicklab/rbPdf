<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Zoom do viewer PDF ao abrir o documento.
 *
 * Os backed values correspondem aos parâmetros aceitos pelo FPDF::SetDisplayMode().
 */
enum RbPdfDisplayZoom: string
{
    case FullPage = 'fullpage';
    case FullWidth = 'fullwidth';
    case Real = 'real';
    case Default = 'default';
}
