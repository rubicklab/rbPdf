<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Modo de ajuste de imagem dentro de uma área retangular.
 *
 * - None: dimensões originais, sem redimensionamento
 * - Fit: proporcional dentro da área (pode ter espaço vazio)
 * - Cover: proporcional cobrindo toda a área (pode cortar)
 */
enum RbPdfImageFit: string
{
    case None = 'none';
    case Fit = 'fit';
    case Cover = 'cover';
}
