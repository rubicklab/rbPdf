<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfListBuilder;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\RbPdf;

#[CoversClass(RbPdfListBuilder::class)]
final class ListBuilderTest extends TestCase
{
    private RbPdf $pdf;

    private RbPdfListBuilder $list;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdf = new RbPdf;
        $this->pdf->addPage()->font('Helvetica', RbPdfFontStyle::Regular, 10);
        $this->list = new RbPdfListBuilder($this->pdf);
    }

    public function test_bullet_style_fluent(): void
    {
        $r = $this->list->bulletStyle('-', 4, 6);
        self::assertSame($this->list, $r);
    }

    public function test_line_height_fluent(): void
    {
        self::assertSame($this->list, $this->list->lineHeight(7));
    }

    public function test_sub_list_indent_fluent(): void
    {
        self::assertSame($this->list, $this->list->subListIndent(6));
    }

    public function test_items_renders_without_exception(): void
    {
        $this->list
            ->bulletStyle('•', 5, 8)
            ->lineHeight(6)
            ->items(['A', 'B']);

        self::assertSame($this->pdf, $this->list->end());
    }

    public function test_nested_items(): void
    {
        $this->list->items([
            'Root',
            ['Sub one', 'Sub two'],
            'Last',
        ]);

        self::assertSame($this->pdf, $this->list->end());
    }

    public function test_bullet_list_on_facade_returns_builder(): void
    {
        $pdf = new RbPdf;
        $pdf->addPage();

        $b = $pdf->bulletList();
        self::assertInstanceOf(RbPdfListBuilder::class, $b);
        self::assertSame($pdf, $b->end());
    }
}
