<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfTableColumn;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Exception\RbPdfException;

#[CoversClass(RbPdfTableColumn::class)]
final class TableColumnTest extends TestCase
{
    public function test_creation_with_named_args(): void
    {
        $column = new RbPdfTableColumn(label: 'Nome', width: 50, align: RbPdfTextAlignment::Left);

        self::assertSame('Nome', $column->label);
        self::assertSame(50.0, $column->width);
        self::assertSame(RbPdfTextAlignment::Left, $column->align);
    }

    public function test_default_alignment_is_left(): void
    {
        $column = new RbPdfTableColumn(label: 'Nome', width: 50);

        self::assertSame(RbPdfTextAlignment::Left, $column->align);
    }

    public function test_creation_with_center_alignment(): void
    {
        $column = new RbPdfTableColumn(label: 'Status', width: 30, align: RbPdfTextAlignment::Center);

        self::assertSame('Status', $column->label);
        self::assertSame(30.0, $column->width);
        self::assertSame(RbPdfTextAlignment::Center, $column->align);
    }

    public function test_creation_with_right_alignment(): void
    {
        $column = new RbPdfTableColumn(label: 'Valor', width: 40, align: RbPdfTextAlignment::Right);

        self::assertSame('Valor', $column->label);
        self::assertSame(40.0, $column->width);
        self::assertSame(RbPdfTextAlignment::Right, $column->align);
    }

    public function test_creation_with_accented_label(): void
    {
        $column = new RbPdfTableColumn(label: 'Descrição', width: 60);

        self::assertSame('Descrição', $column->label);
    }

    public function test_zero_width_throws_rb_pdf_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('column width');

        new RbPdfTableColumn(label: 'X', width: 0);
    }

    public function test_negative_width_throws_rb_pdf_exception(): void
    {
        $this->expectException(RbPdfException::class);

        new RbPdfTableColumn(label: 'X', width: -1.5);
    }

    public function test_creation_with_decimal_width(): void
    {
        $column = new RbPdfTableColumn(label: 'Preciso', width: 33.5);

        self::assertSame(33.5, $column->width);
    }
}
