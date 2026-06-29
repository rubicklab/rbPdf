<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Component;

use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Construção declarativa de tabelas em PDF (cabeçalho preenchido, corpo, separadores opcionais).
 *
 * Opera apenas sobre a API pública de {@see RbPdf}. Com auto page break, adiciona página
 * e redesenha o cabeçalho quando a próxima linha não couber.
 *
 * @example
 * $table = new RbPdfTableBuilder($pdf);
 * $table->columns(
 *     new RbPdfTableColumn(label: 'Nome', width: 60, align: RbPdfTextAlignment::Left),
 *     new RbPdfTableColumn(label: 'Valor', width: 30, align: RbPdfTextAlignment::Right),
 * )
 * ->headerStyle(bgColor: RbPdfColor::rgb(51, 122, 183), textColor: RbPdfColor::white())
 * ->renderHeader()
 * ->addRows($data)
 * ->end();
 */
final class RbPdfTableBuilder
{
    /** @var array<RbPdfTableColumn> */
    private array $columns = [];

    private RbPdfColor $headerBgColor;

    private RbPdfColor $headerTextColor;

    private string $headerFontFamily;

    private RbPdfFontStyle $headerFontStyle;

    private float $headerFontSize;

    private float $headerHeight;

    private string $bodyFontFamily;

    private RbPdfFontStyle $bodyFontStyle;

    private float $bodyFontSize;

    private float $rowHeight;

    private bool $drawSeparators;

    private RbPdfColor $separatorColor;

    private float $separatorWidth;

    private bool $autoPageBreak;

    private float $pageBreakMargin;

    /** Borda FPDF passada a {@see RbPdf::cell()} (0 = sem, 1 = contorno). */
    private int $cellBorder = 0;

    public function __construct(
        private readonly RbPdf $pdf,
    ) {
        $this->headerBgColor = RbPdfColor::rgb(51, 122, 183);
        $this->headerTextColor = RbPdfColor::white();
        $this->headerFontFamily = 'Helvetica';
        $this->headerFontStyle = RbPdfFontStyle::Bold;
        $this->headerFontSize = 9;
        $this->headerHeight = 7;

        $this->bodyFontFamily = 'Helvetica';
        $this->bodyFontStyle = RbPdfFontStyle::Regular;
        $this->bodyFontSize = 9;
        $this->rowHeight = 6;

        $this->drawSeparators = false;
        $this->separatorColor = RbPdfColor::rgb(200, 200, 200);
        $this->separatorWidth = 0.2;

        $this->autoPageBreak = true;
        $this->pageBreakMargin = 20;
    }

    /**
     * Define as colunas da tabela.
     *
     * @return $this
     */
    public function columns(RbPdfTableColumn ...$columns): self
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Estilo visual do cabeçalho; parâmetros null preservam o valor atual.
     *
     * @return $this
     */
    public function headerStyle(
        ?RbPdfColor $bgColor = null,
        ?RbPdfColor $textColor = null,
        ?string $fontFamily = null,
        ?RbPdfFontStyle $fontStyle = null,
        ?float $fontSize = null,
        ?float $height = null,
    ): self {
        if ($bgColor !== null) {
            $this->headerBgColor = $bgColor;
        }
        if ($textColor !== null) {
            $this->headerTextColor = $textColor;
        }
        if ($fontFamily !== null) {
            $this->headerFontFamily = $fontFamily;
        }
        if ($fontStyle !== null) {
            $this->headerFontStyle = $fontStyle;
        }
        if ($fontSize !== null) {
            $this->headerFontSize = $fontSize;
        }
        if ($height !== null) {
            $this->headerHeight = $height;
        }

        return $this;
    }

    /**
     * Estilo visual do corpo; parâmetros null preservam o valor atual.
     *
     * @return $this
     */
    public function bodyStyle(
        ?string $fontFamily = null,
        ?RbPdfFontStyle $fontStyle = null,
        ?float $fontSize = null,
        ?float $rowHeight = null,
    ): self {
        if ($fontFamily !== null) {
            $this->bodyFontFamily = $fontFamily;
        }
        if ($fontStyle !== null) {
            $this->bodyFontStyle = $fontStyle;
        }
        if ($fontSize !== null) {
            $this->bodyFontSize = $fontSize;
        }
        if ($rowHeight !== null) {
            $this->rowHeight = $rowHeight;
        }

        return $this;
    }

    /**
     * Linhas separadoras entre linhas de dados.
     *
     * @return $this
     */
    public function separators(
        bool $enabled = true,
        ?RbPdfColor $color = null,
        ?float $width = null,
    ): self {
        $this->drawSeparators = $enabled;

        if ($color !== null) {
            $this->separatorColor = $color;
        }
        if ($width !== null) {
            $this->separatorWidth = $width;
        }

        return $this;
    }

    /**
     * Quebra de página automática ao adicionar linhas; redesenha o cabeçalho na nova página.
     *
     * @return $this
     */
    public function autoPageBreakConfig(bool $enabled = true, float $margin = 20): self
    {
        $this->autoPageBreak = $enabled;
        $this->pageBreakMargin = $margin;

        return $this;
    }

    /**
     * Define a borda das células (mesma convenção de {@see RbPdf::cell()}).
     *
     * @return $this
     */
    public function cellBorder(int $border): self
    {
        $this->cellBorder = $border;

        return $this;
    }

    /**
     * Renderiza o cabeçalho na posição atual do cursor.
     *
     * @return $this
     *
     * @throws RbPdfException Se nenhuma coluna foi definida
     */
    public function renderHeader(): self
    {
        $this->assertHasColumns();

        $this->pdf
            ->font($this->headerFontFamily, $this->headerFontStyle, $this->headerFontSize)
            ->fillColor($this->headerBgColor)
            ->textColor($this->headerTextColor);

        foreach ($this->columns as $column) {
            $this->pdf->cell(
                $column->width,
                $this->headerHeight,
                $column->label,
                $this->cellBorder,
                0,
                $column->align,
                true,
            );
        }

        $this->pdf->newLine($this->headerHeight);

        return $this;
    }

    /**
     * Uma linha de dados (valores na ordem das colunas).
     *
     * @param  array<int, string>  $data
     * @return $this
     */
    public function addRow(array $data, ?RbPdfColor $bgColor = null): self
    {
        $this->assertHasColumns();

        $this->checkPageBreak();

        $this->pdf->font($this->bodyFontFamily, $this->bodyFontStyle, $this->bodyFontSize);

        $fill = $bgColor !== null;
        if ($fill) {
            $this->pdf->fillColor($bgColor);
        }

        $this->pdf->textColor(RbPdfColor::black());

        $columnCount = count($this->columns);
        for ($i = 0; $i < $columnCount; $i++) {
            $column = $this->columns[$i];
            $text = $data[$i] ?? '';

            $this->pdf->cell(
                $column->width,
                $this->rowHeight,
                $text,
                $this->cellBorder,
                0,
                $column->align,
                $fill,
            );
        }

        $this->pdf->newLine($this->rowHeight);

        if ($this->drawSeparators) {
            $this->drawSeparator();
        }

        return $this;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return $this
     */
    public function addRows(array $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * Desenha uma linha separadora sob o cursor, com a largura total das colunas.
     *
     * @return $this
     */
    public function drawSeparator(): self
    {
        $totalWidth = $this->calculateTotalWidth();
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();

        $this->pdf
            ->drawColor($this->separatorColor)
            ->lineWidth($this->separatorWidth)
            ->line($x, $y, $x + $totalWidth, $y);

        return $this;
    }

    public function end(): RbPdf
    {
        return $this->pdf;
    }

    private function assertHasColumns(): void
    {
        if ($this->columns === []) {
            throw new RbPdfException(
                'RbPdfTableBuilder: define columns with columns() before rendering or adding rows.'
            );
        }
    }

    private function checkPageBreak(): void
    {
        if (! $this->autoPageBreak) {
            return;
        }

        $currentY = $this->pdf->getY();
        $pageHeight = $this->pdf->getPageHeight();
        $availableSpace = $pageHeight - $this->pageBreakMargin;

        if ($currentY + $this->rowHeight > $availableSpace) {
            $this->pdf->addPage();
            $this->renderHeader();
        }
    }

    private function calculateTotalWidth(): float
    {
        $total = 0.0;
        foreach ($this->columns as $column) {
            $total += $column->width;
        }

        return $total;
    }
}
