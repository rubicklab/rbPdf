<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Component;

use Rubick\RbPdf\RbPdf;

/**
 * Lista com marcadores (bullets) e sublistas aninhadas com recuo.
 *
 * Segue o padrão fluente de {@see RbPdfTableBuilder}: encadeie métodos e finalize com {@see self::end()}.
 *
 * @example
 * $pdf->bulletList()
 *     ->bulletStyle('•', 5.0, 8.0)
 *     ->lineHeight(6.0)
 *     ->items(['Item 1', ['Sub A', 'Sub B'], 'Item 2'])
 *     ->end();
 */
final class RbPdfListBuilder
{
    private string $bulletChar = "\xE2\x80\xA2";

    private float $indent = 5.0;

    private float $textIndent = 8.0;

    private float $lineHeight = 6.0;

    private float $subListIndent = 5.0;

    public function __construct(
        private readonly RbPdf $pdf,
    ) {}

    /**
     * Caractere do bullet (UTF-8) e recuos em mm.
     *
     * @return $this
     */
    public function bulletStyle(string $char = "\xE2\x80\xA2", float $indent = 5.0, float $textIndent = 8.0): self
    {
        $this->bulletChar = $char;
        $this->indent = $indent;
        $this->textIndent = $textIndent;

        return $this;
    }

    /**
     * Altura de cada linha da lista (mm).
     *
     * @return $this
     */
    public function lineHeight(float $height): self
    {
        $this->lineHeight = $height;

        return $this;
    }

    /**
     * Recuo adicional por nível de sublista (mm).
     *
     * @return $this
     */
    public function subListIndent(float $mm): self
    {
        $this->subListIndent = $mm;

        return $this;
    }

    /**
     * Itens: strings com bullet ou arrays aninhados (sublista), até 3 níveis tipados no PHPDoc.
     *
     * @param  array<int, string|array<int, string|array<int, string>>>  $items
     * @return $this
     */
    public function items(array $items): self
    {
        $this->renderItems($items, 0);

        return $this;
    }

    public function end(): RbPdf
    {
        return $this->pdf;
    }

    /**
     * @param  array<int, string|array<int, string|array<int, string>>>  $items
     */
    private function renderItems(array $items, int $level): void
    {
        $baseIndent = $this->indent + ($level * $this->subListIndent);

        foreach ($items as $item) {
            if (is_array($item)) {
                $this->renderItems($item, $level + 1);

                continue;
            }

            $x = $this->pdf->getX();

            $this->pdf->setX($x + $baseIndent);
            $this->pdf->cell(
                $this->textIndent,
                $this->lineHeight,
                $this->bulletChar,
            );
            $this->pdf->cell(
                0,
                $this->lineHeight,
                $item,
            );
            $this->pdf->newLine($this->lineHeight);
            $this->pdf->setX($x);
        }
    }
}
