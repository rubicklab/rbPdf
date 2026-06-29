<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Engine;

use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfOutputMode;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;
use Rubick\RbPdf\ValueObject\RbPdfColor;

final class RbPdfEngineGeneratesValidPdfTest extends TestCase
{
    public function test_minimal_pdf_has_valid_header_and_eof(): void
    {
        $engine = new RbPdfFpdfEngine;
        $engine->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4);
        $engine->setFont('Helvetica', RbPdfFontStyle::Regular, 12);
        $engine->cell(40, 10, 'RbPdf engine', 0, 1, RbPdfTextAlignment::Left, false);

        $binary = $engine->output('', RbPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $binary);
        self::assertStringContainsString('%%EOF', $binary);
        self::assertGreaterThan(400, strlen($binary));
    }

    public function test_rectangle_with_fill_alpha_completes_with_meaningful_size(): void
    {
        $engine = new RbPdfFpdfEngine;
        $engine->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4);
        $engine->setFillColor(RbPdfColor::rgb(200, 30, 30));
        $engine->setFillAlpha(0.5);
        $engine->rect(20, 30, 80, 40, 'F');
        $engine->setAlpha(1.0);

        $binary = $engine->output('', RbPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $binary);
        self::assertGreaterThan(800, strlen($binary));
    }
}
