<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Alinhamento vertical de imagem dentro de uma área retangular.
 */
enum RbPdfImageValign: string
{
    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';
}
