<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Component;

use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Exception\RbPdfException;

/**
 * Coluna de tabela para uso em {@see RbPdfTableBuilder} e {@see RbPdfTableWriter}.
 *
 * Objeto imutável com rótulo, largura em milímetros e alinhamento horizontal.
 * Largura deve ser estritamente positiva.
 *
 * @example new RbPdfTableColumn(label: 'Nome', width: 50, align: RbPdfTextAlignment::Left)
 */
final class RbPdfTableColumn
{
    /**
     * @param  string  $label  Texto do cabeçalho da coluna (UTF-8)
     * @param  float  $width  Largura da coluna em milímetros (&gt; 0)
     * @param  RbPdfTextAlignment  $align  Alinhamento horizontal do texto na coluna
     *
     * @throws RbPdfException Se {@see $width} for menor ou igual a zero
     */
    public function __construct(
        public readonly string $label,
        public readonly float $width,
        public readonly RbPdfTextAlignment $align = RbPdfTextAlignment::Left,
    ) {
        if ($width <= 0.0) {
            throw new RbPdfException(
                'RbPdfTableColumn: column width must be greater than zero (mm). '
                ."Received value: {$width}."
            );
        }
    }
}
