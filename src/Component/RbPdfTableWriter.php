<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Component;

use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Tabela compacta: cabeçalho em texto (sem faixa preenchida) e linhas de dados.
 *
 * Reutiliza {@see RbPdfTableColumn}. Opcionalmente repete o cabeçalho ao quebrar página,
 * alinhado a {@see self::atX()}.
 *
 * @example
 * (new RbPdfTableWriter($pdf))
 *     ->atX(5)
 *     ->columns(
 *         new RbPdfTableColumn('ITEM', 40),
 *         new RbPdfTableColumn('VALOR', 30, RbPdfTextAlignment::Right),
 *     )
 *     ->font('Helvetica', 6.5)
 *     ->autoPageBreak(true, 14)
 *     ->writeHeader();
 * foreach ($linhas as $linha) {
 *     $writer->writeRow($linha);
 * }
 * $writer->end();
 */
final class RbPdfTableWriter
{
    /** @var array<RbPdfTableColumn> */
    private array $columns = [];

    private float $startX = 0.0;

    private string $fontFamily = 'Helvetica';

    private float $fontSize = 6.5;

    private RbPdfColor $headerTextColor;

    private RbPdfColor $bodyTextColor;

    private float $headerRowHeight = 5.5;

    private float $bodyRowHeight = 4.5;

    private bool $autoPageBreakEnabled = false;

    private float $pageBreakMargin = 14.0;

    private bool $headerWritten = false;

    private int $cellBorder = 0;

    public function __construct(
        private readonly RbPdf $pdf,
    ) {
        $this->headerTextColor = RbPdfColor::rgb(50, 50, 50);
        $this->bodyTextColor = RbPdfColor::black();
    }

    /**
     * Posição X inicial de cada linha (em geral a margem esquerda do bloco).
     *
     * @return $this
     */
    public function atX(float $x): self
    {
        $this->startX = $x;

        return $this;
    }

    /**
     * @return $this
     */
    public function columns(RbPdfTableColumn ...$columns): self
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Família e tamanho da fonte (cabeçalho em negrito, corpo regular).
     *
     * @return $this
     */
    public function font(string $family, float $size = 6.5): self
    {
        $this->fontFamily = $family;
        $this->fontSize = $size;

        return $this;
    }

    /**
     * Cores de texto do cabeçalho e do corpo.
     *
     * @return $this
     */
    public function textColors(RbPdfColor $header, RbPdfColor $body): self
    {
        $this->headerTextColor = $header;
        $this->bodyTextColor = $body;

        return $this;
    }

    /**
     * Alturas de linha em mm.
     *
     * @return $this
     */
    public function rowHeights(float $headerMm, float $bodyMm): self
    {
        $this->headerRowHeight = $headerMm;
        $this->bodyRowHeight = $bodyMm;

        return $this;
    }

    /**
     * Ao estourar a página, adiciona página e redesenha o cabeçalho.
     *
     * @return $this
     */
    public function autoPageBreak(bool $enabled = true, float $marginMm = 14.0): self
    {
        $this->autoPageBreakEnabled = $enabled;
        $this->pageBreakMargin = $marginMm;

        return $this;
    }

    /**
     * Borda das células ({@see RbPdf::cell()}).
     *
     * @return $this
     */
    public function cellBorder(int $border): self
    {
        $this->cellBorder = $border;

        return $this;
    }

    /**
     * Linha de cabeçalho com os rótulos das colunas.
     *
     * @return $this
     *
     * @throws RbPdfException Se as colunas não foram definidas
     */
    public function writeHeader(): self
    {
        $this->assertHasColumns();

        $this->pdf
            ->setX($this->startX)
            ->font($this->fontFamily, RbPdfFontStyle::Bold, $this->fontSize)
            ->textColor($this->headerTextColor);

        foreach ($this->columns as $column) {
            $this->pdf->cell(
                $column->width,
                $this->headerRowHeight,
                $column->label,
                $this->cellBorder,
                0,
                $column->align,
            );
        }

        $this->pdf->newLine($this->headerRowHeight)->textColor(RbPdfColor::black());
        $this->headerWritten = true;

        return $this;
    }

    /**
     * Altura mínima necessária para uma linha de corpo, considerando quebra de texto em cada célula.
     *
     * Usa {@see RbPdf::getStringHeight()} com a fonte do corpo configurada neste writer
     * (deve haver página no documento e fonte aplicável, p.ex. após addPage()).
     *
     * @param  array<int, string>  $cells  Valores na ordem das colunas
     * @return float Altura em mm (≥ {@see $bodyRowHeight})
     */
    public function minimumBodyRowHeightForCells(array $cells): float
    {
        $this->assertHasColumns();

        $this->pdf->font($this->fontFamily, RbPdfFontStyle::Regular, $this->fontSize);

        $max = $this->bodyRowHeight;
        $n = count($this->columns);
        for ($i = 0; $i < $n; $i++) {
            $column = $this->columns[$i];
            $text = $cells[$i] ?? '';
            $h = $this->pdf->getStringHeight($text, $column->width, $this->bodyRowHeight);
            if ($h > $max) {
                $max = $h;
            }
        }

        return $max;
    }

    /**
     * @param  array<int, string>  $cells
     * @return $this
     */
    public function writeRow(array $cells): self
    {
        $this->assertHasColumns();

        if ($this->autoPageBreakEnabled) {
            $this->breakPageIfNeeded();
        }

        $this->pdf
            ->setX($this->startX)
            ->font($this->fontFamily, RbPdfFontStyle::Regular, $this->fontSize)
            ->textColor($this->bodyTextColor);

        $n = count($this->columns);
        for ($i = 0; $i < $n; $i++) {
            $column = $this->columns[$i];
            $text = $cells[$i] ?? '';

            $this->pdf->cell(
                $column->width,
                $this->bodyRowHeight,
                $text,
                $this->cellBorder,
                0,
                $column->align,
            );
        }

        $this->pdf->newLine($this->bodyRowHeight)->textColor(RbPdfColor::black());

        return $this;
    }

    /**
     * @param  iterable<int, array<int, string>>  $rows
     * @return $this
     */
    public function writeRows(iterable $rows): self
    {
        foreach ($rows as $row) {
            $this->writeRow($row);
        }

        return $this;
    }

    public function end(): RbPdf
    {
        return $this->pdf;
    }

    /**
     * Altura de linha do corpo configurada (mm).
     */
    public function getBodyRowHeight(): float
    {
        return $this->bodyRowHeight;
    }

    private function assertHasColumns(): void
    {
        if ($this->columns === []) {
            throw new RbPdfException(
                'RbPdfTableWriter: define columns with columns() before writing the table.'
            );
        }
    }

    private function breakPageIfNeeded(): void
    {
        $currentY = $this->pdf->getY();
        $pageHeight = $this->pdf->getPageHeight();
        $limitY = $pageHeight - $this->pageBreakMargin;

        if ($currentY + $this->bodyRowHeight <= $limitY) {
            return;
        }

        $this->pdf->addPage();

        if ($this->headerWritten) {
            $this->writeHeaderWithoutFlag();
        }
    }

    private function writeHeaderWithoutFlag(): void
    {
        $this->pdf
            ->setX($this->startX)
            ->font($this->fontFamily, RbPdfFontStyle::Bold, $this->fontSize)
            ->textColor($this->headerTextColor);

        foreach ($this->columns as $column) {
            $this->pdf->cell(
                $column->width,
                $this->headerRowHeight,
                $column->label,
                $this->cellBorder,
                0,
                $column->align,
            );
        }

        $this->pdf->newLine($this->headerRowHeight)->textColor(RbPdfColor::black());
    }
}
