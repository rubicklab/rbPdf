<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfTableBuilder;
use Rubick\RbPdf\Component\RbPdfTableColumn;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\ValueObject\RbPdfColor;

#[CoversClass(RbPdfTableBuilder::class)]
final class TableBuilderTest extends TestCase
{
    private RbPdf $pdf;

    private RbPdfTableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdf = new RbPdf;
        $this->pdf->addPage();
        $this->table = new RbPdfTableBuilder($this->pdf);
    }

    public function test_render_header_without_columns_throws(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('columns()');

        $this->table->renderHeader();
    }

    public function test_fluent_chains_return_same_instance(): void
    {
        $c = new RbPdfTableColumn('A', 50);

        $t = $this->table
            ->columns($c)
            ->headerStyle(bgColor: RbPdfColor::rgb(1, 2, 3))
            ->bodyStyle(fontFamily: 'Helvetica', fontStyle: RbPdfFontStyle::Regular)
            ->separators(true, RbPdfColor::black(), 0.1)
            ->autoPageBreakConfig(true, 15)
            ->cellBorder(1);

        self::assertSame($this->table, $t);
    }

    public function test_end_returns_original_pdf(): void
    {
        $this->table->columns(new RbPdfTableColumn('Col', 40))->renderHeader();

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_add_row_pads_missing_cells(): void
    {
        $this->table
            ->columns(
                new RbPdfTableColumn('A', 30),
                new RbPdfTableColumn('B', 30),
            )
            ->renderHeader()
            ->addRow(['only-first']);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_multiple_columns_alignment(): void
    {
        $this->table
            ->columns(
                new RbPdfTableColumn(label: 'Nome', width: 60, align: RbPdfTextAlignment::Left),
                new RbPdfTableColumn(label: 'Valor', width: 30, align: RbPdfTextAlignment::Right),
                new RbPdfTableColumn(label: 'Status', width: 25, align: RbPdfTextAlignment::Center),
            )
            ->renderHeader()
            ->addRow(['Produto', '99,00', 'OK']);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_add_rows_iterates(): void
    {
        $this->table
            ->columns(new RbPdfTableColumn('C', 50))
            ->renderHeader()
            ->addRows([['1'], ['2'], ['3']]);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_separators_and_row_fill(): void
    {
        $this->table
            ->columns(new RbPdfTableColumn('C', 50))
            ->separators(true, RbPdfColor::rgb(180, 180, 180), 0.15)
            ->renderHeader()
            ->addRow(['a'], RbPdfColor::rgb(240, 240, 240))
            ->addRow(['b']);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_draw_separator_standalone(): void
    {
        $this->table
            ->columns(new RbPdfTableColumn('C', 40))
            ->renderHeader()
            ->drawSeparator();

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_auto_page_break_disabled_allows_tall_content(): void
    {
        $this->table
            ->columns(new RbPdfTableColumn('C', 50))
            ->autoPageBreakConfig(false)
            ->renderHeader();

        for ($i = 0; $i < 5; $i++) {
            $this->table->addRow([(string) $i]);
        }

        self::assertSame($this->pdf, $this->table->end());
    }
}
