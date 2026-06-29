<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Estilo de ponta de linha (line cap) para operações de desenho PDF.
 *
 * Backed values correspondem aos nomes aceitos pelo PDFDrawTrait::SetLineStyle.
 */
enum RbPdfLineCap: string
{
    /** Ponta reta (padrão PDF). */
    case Butt = 'butt';

    /** Ponta arredondada. */
    case Round = 'round';

    /** Ponta quadrada (projetada além do endpoint). */
    case Square = 'square';
}
