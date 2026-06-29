<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Layout de exibição do viewer PDF ao abrir o documento.
 *
 * Os backed values correspondem aos parâmetros aceitos pelo FPDF::SetDisplayMode().
 */
enum RbPdfDisplayLayout: string
{
    case Single = 'single';
    case Continuous = 'continuous';
    case Two = 'two';
    case Default = 'default';
}
