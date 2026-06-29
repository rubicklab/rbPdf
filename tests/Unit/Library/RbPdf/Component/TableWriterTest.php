<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfTableColumn;
use Rubick\RbPdf\Component\RbPdfTableWriter;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\ValueObject\RbPdfColor;

#[CoversClass(RbPdfTableWriter::class)]
final class TableWriterTest extends TestCase
{
    public function test_throws_when_writing_header_without_columns(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage();

        $writer = new RbPdfTableWriter($pdf);

        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('columns()');

        $writer->writeHeader();
    }

    public function test_minimum_body_row_height_for_cells_requires_columns(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage();

        $writer = new RbPdfTableWriter($pdf);

        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('columns()');

        $writer->minimumBodyRowHeightForCells(['x']);
    }

    public function test_fluent_returns_self(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage();

        $w = new RbPdfTableWriter($pdf);
        self::assertSame($w, $w->atX(3));
        self::assertSame($w, $w->columns(new RbPdfTableColumn('A', 40)));
        self::assertSame($w, $w->font('Helvetica', 8));
        self::assertSame($w, $w->textColors(RbPdfColor::black(), RbPdfColor::black()));
    }

    public function test_fluent_continued(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage();

        $w = new RbPdfTableWriter($pdf);
        $w->columns(new RbPdfTableColumn('A', 40));
        self::assertSame($w, $w->rowHeights(6, 5));
        self::assertSame($w, $w->autoPageBreak(true, 12));
        self::assertSame($w, $w->writeHeader());
        self::assertSame($w, $w->writeRow(['x']));
        self::assertSame($w, $w->writeRows([['a'], ['b']]));
    }

    public function test_end_returns_same_pdf(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage();

        $out = (new RbPdfTableWriter($pdf))
            ->atX(5)
            ->columns(new RbPdfTableColumn('COL', 50))
            ->writeHeader()
            ->writeRow(['valor'])
            ->end();

        self::assertSame($pdf, $out);
    }

    public function test_minimum_body_row_height_for_wrapped_text_is_at_least_line_height(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage()->font('Helvetica', RbPdfFontStyle::Regular, 10);

        $writer = (new RbPdfTableWriter($pdf))
            ->columns(new RbPdfTableColumn('T', 20))
            ->font('Helvetica', 10)
            ->rowHeights(5, 5);

        $short = $writer->minimumBodyRowHeightForCells(['Hi']);
        self::assertGreaterThanOrEqual($writer->getBodyRowHeight(), $short);

        $long = 'word '.str_repeat('x ', 40);
        $tall = $writer->minimumBodyRowHeightForCells([$long]);
        self::assertGreaterThanOrEqual($writer->getBodyRowHeight(), $tall);
        self::assertGreaterThan($writer->getBodyRowHeight(), $tall);
    }
}
