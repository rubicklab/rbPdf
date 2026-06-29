<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Direção de renderização do texto no PDF.
 *
 * Backed values correspondem aos parâmetros aceitos pelo RPDFTrait::TextWithDirection().
 */
enum RbPdfTextDirection: string
{
    /** Esquerda para direita (padrão). */
    case LeftToRight = 'R';

    /** De baixo para cima (vertical ascendente). */
    case BottomToTop = 'U';

    /** De cima para baixo (vertical descendente). */
    case TopToBottom = 'D';

    /** Direita para esquerda (invertido). */
    case RightToLeft = 'L';
}
