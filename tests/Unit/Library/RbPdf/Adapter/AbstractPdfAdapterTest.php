<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfOutputMode;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;
use Rubick\RbPdf\RbPdf;

/**
 * Porte de {@see \Tests\Unit\Library\RbPdf\Adapter\AbstractPdfAdapterTest} da referência `api`.
 * A classe abstrata `AbstractPdfAdapter` e o contrato `PdfAdapterInterface` não existem neste pacote;
 * o motor é sempre {@see RbPdfFpdfEngine} atrás da fachada {@see RbPdf}.
 * Estes testes preservam as asserções **observáveis** equivalentes: callbacks header/footer,
 * conversões métricas e geração PDF válida (em vez de “unsupported by default” no adapter stub).
 */
#[CoversClass(RbPdf::class)]
final class AbstractPdfAdapterTest extends TestCase
{
    public function test_mm_to_points_returns_correct_value(): void
    {
        $pdf = new RbPdf;

        self::assertEqualsWithDelta(72.0, $pdf->mmToPoints(25.4), 0.0001);
    }

    public function test_points_to_mm_returns_correct_value(): void
    {
        $pdf = new RbPdf;

        self::assertEqualsWithDelta(25.4, $pdf->pointsToMm(72.0), 0.0001);
    }

    public function test_cm_to_points_returns_correct_value(): void
    {
        $pdf = new RbPdf;

        self::assertEqualsWithDelta(72.0, $pdf->cmToPoints(2.54), 0.0001);
    }

    public function test_inches_to_points_returns_correct_value(): void
    {
        $pdf = new RbPdf;

        self::assertEqualsWithDelta(72.0, $pdf->inchesToPoints(1.0), 0.0001);
    }

    public function test_header_and_footer_callbacks_are_invoked_on_output(): void
    {
        $headerRan = false;
        $footerRan = false;

        $pdf = (new RbPdf)
            ->onHeader(static function (RbPdf $p) use (&$headerRan): void {
                $headerRan = true;
                $p->font('Helvetica', RbPdfFontStyle::Regular, 8)
                    ->cell(20, 4, 'H', 0, 1, RbPdfTextAlignment::Left, false);
            })
            ->onFooter(static function (RbPdf $p) use (&$footerRan): void {
                $footerRan = true;
                $p->font('Helvetica', RbPdfFontStyle::Regular, 8)
                    ->cell(20, 4, 'F', 0, 1, RbPdfTextAlignment::Left, false);
            })
            ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4);

        $binary = $pdf->output('', RbPdfOutputMode::String);

        self::assertTrue($headerRan);
        self::assertTrue($footerRan);
        self::assertStringStartsWith('%PDF', $binary);
        self::assertGreaterThan(400, strlen($binary));
    }
}
