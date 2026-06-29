<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Alinhamento horizontal de imagem dentro de uma área retangular.
 */
enum RbPdfImageAlign: string
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';
}
