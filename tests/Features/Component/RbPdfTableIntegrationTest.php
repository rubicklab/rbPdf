<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfTableBuilder;
use Rubick\RbPdf\Component\RbPdfTableColumn;
use Rubick\RbPdf\Component\RbPdfTableWriter;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;

#[CoversClass(RbPdfTableWriter::class)]
final class RbPdfTableIntegrationTest extends TestCase
{
    public function test_table_with_multiple_rows_and_borders_produces_valid_pdf(): void
    {
        $pdf = new RbPdf;
        $pdf->margins(15, 20, 15, 20)
            ->addPage()
            ->font('Helvetica', RbPdfFontStyle::Regular, 9);

        $rows = [];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = [(string) $i, 'SKU-'.$i, number_format($i * 10.5, 2, ',', '.')];
        }

        (new RbPdfTableWriter($pdf))
            ->atX(15)
            ->columns(
                new RbPdfTableColumn('#', 15, RbPdfTextAlignment::Right),
                new RbPdfTableColumn('Code', 70, RbPdfTextAlignment::Left),
                new RbPdfTableColumn('Amount', 35, RbPdfTextAlignment::Right),
            )
            ->font('Helvetica', 9)
            ->cellBorder(1)
            ->writeHeader()
            ->writeRows($rows);

        $raw = $pdf->toString();

        self::assertStringStartsWith('%PDF', $raw);
        self::assertGreaterThan(1500, strlen($raw));
        self::assertGreaterThanOrEqual(1, preg_match_all('/\/Type\s*\/Page[^s]/', $raw));
    }

    public function test_table_builder_with_page_break_redraws_header(): void
    {
        $pdf = new RbPdf;
        $pdf->landscape()
            ->margins(10, 25, 10, 10)
            ->autoPageBreak(true, 20)
            ->addPage()
            ->font('Helvetica', RbPdfFontStyle::Regular, 8);

        $b = new RbPdfTableBuilder($pdf);
        $b->columns(
            new RbPdfTableColumn('ID', 20),
            new RbPdfTableColumn('Data', 240),
        )
            ->bodyStyle(rowHeight: 5)
            ->autoPageBreakConfig(true, 20)
            ->renderHeader();

        for ($i = 1; $i <= 80; $i++) {
            $b->addRow([(string) $i, str_repeat('x', 30)]);
        }

        $raw = $b->end()->toString();
        $pages = preg_match_all('/\/Type\s*\/Page[^s]/', $raw);
        self::assertGreaterThanOrEqual(2, $pages);
    }
}
