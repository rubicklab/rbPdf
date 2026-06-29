<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfListBuilder;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\RbPdf;

#[CoversClass(RbPdfListBuilder::class)]
final class RbPdfListIntegrationTest extends TestCase
{
    public function test_list_with_nested_items_produces_valid_pdf(): void
    {
        $pdf = new RbPdf;
        $pdf->margins(20, 25, 20, 25)
            ->addPage()
            ->font('Helvetica', RbPdfFontStyle::Regular, 11)
            ->setXY(20, 40);

        $pdf->bulletList()
            ->bulletStyle('•', 5, 8)
            ->lineHeight(6)
            ->subListIndent(5)
            ->items([
                'First top-level item',
                [
                    'Nested A with more text',
                    'Nested B',
                ],
                'Second top-level item',
            ])
            ->end();

        $raw = $pdf->toString();

        self::assertStringStartsWith('%PDF', $raw);
        self::assertGreaterThan(500, strlen($raw));
    }
}
