<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Contract;

use Rubick\RbPdf\ValueObject\RbPdfCmykColor;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Contrato para cores usadas em operações PDF (RGB ou CMYK).
 *
 * Implementado por {@see RbPdfColor} e
 * {@see RbPdfCmykColor}.
 */
interface RbPdfColorInterface
{
    /**
     * RGB retorna [r, g, b] (0–255). CMYK retorna [c, m, y, k] (0–100).
     *
     * @return array<int, int>
     */
    public function toArray(): array;
}
