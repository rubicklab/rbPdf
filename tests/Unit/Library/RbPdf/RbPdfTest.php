<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Component\RbPdfListBuilder;
use Rubick\RbPdf\Enum\RbPdfDisplayLayout;
use Rubick\RbPdf\Enum\RbPdfDisplayZoom;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfImageAlign;
use Rubick\RbPdf\Enum\RbPdfImageFit;
use Rubick\RbPdf\Enum\RbPdfImageValign;
use Rubick\RbPdf\Enum\RbPdfLineCap;
use Rubick\RbPdf\Enum\RbPdfLineJoin;
use Rubick\RbPdf\Enum\RbPdfOutputMode;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Enum\RbPdfTextDirection;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;
use Rubick\RbPdf\RbPdf;
use ReflectionClass;
use Rubick\RbPdf\ValueObject\RbPdfColor;
use Rubick\RbPdf\ValueObject\RbPdfGradientStop;

/**
 * Porte consolidado de {@see \Tests\Unit\Library\RbPdf\RbPdfTest} da referência `api`.
 * Testes baseados em mock de `PdfAdapterInterface`/`getAdapter()` foram substituídos por
 * asserções sobre a fachada real {@see RbPdf} e sobre bytes PDF válidos (`%PDF`, tamanho mínimo).
 */
#[CoversClass(RbPdf::class)]
final class RbPdfTest extends TestCase
{
    public function test_fluent_methods_return_same_instance(): void
    {
        $pdf = new RbPdf;

        $chain = $pdf
            ->margins(10, 10, 10)
            ->font('Helvetica', RbPdfFontStyle::Regular, 12)
            ->addPage();

        self::assertSame($pdf, $chain);
    }

    public function test_mm_to_points_conversion_matches_typographic_definition(): void
    {
        $pdf = new RbPdf;

        self::assertEqualsWithDelta(72.0, $pdf->mmToPoints(25.4), 0.001);
        self::assertEqualsWithDelta(25.4, $pdf->pointsToMm(72.0), 0.001);
    }

    public function test_polygon_rejects_less_than_three_points(): void
    {
        $pdf = new RbPdf;

        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('polygon() requires at least 3 points');

        $pdf->polygon([[0, 0], [1, 1]], 'D');
    }

    public function test_add_true_type_font_throws_when_ttf_path_missing(): void
    {
        $pdf = new RbPdf;

        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessageMatches('/not found or not readable/');

        $pdf->addTrueTypeFont(
            'X',
            RbPdfFontStyle::Regular,
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_missing_font_'.uniqid('', true).'.ttf',
        );
    }

    public function test_bullet_list_returns_list_builder_and_end_returns_same_pdf(): void
    {
        $pdf = new RbPdf;

        $list = $pdf->bulletList();

        self::assertInstanceOf(RbPdfListBuilder::class, $list);
        self::assertSame($pdf, $list->end());
    }

    public function test_minimal_closed_document_via_to_string(): void
    {
        $binary = (new RbPdf)
            ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4)
            ->font('Helvetica', RbPdfFontStyle::Regular, 11)
            ->cell(40, 8, 'ok', 0, 1, RbPdfTextAlignment::Left, false)
            ->toString();

        self::assertStringStartsWith('%PDF', $binary);
    }

    public function test_on_header_runs_during_output_and_produces_pdf(): void
    {
        $pdf = (new RbPdf)
            ->onHeader(static function (RbPdf $p): void {
                $p->font('Helvetica', RbPdfFontStyle::Regular, 8)
                    ->cell(40, 5, 'HDR', 0, 1, RbPdfTextAlignment::Left, false);
            })
            ->addPage();

        $out = $pdf->output('', RbPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $out);
        self::assertGreaterThan(200, strlen($out));
    }

    public function test_on_footer_runs_during_output_and_produces_pdf(): void
    {
        $pdf = (new RbPdf)
            ->onFooter(static function (RbPdf $p): void {
                $p->font('Helvetica', RbPdfFontStyle::Regular, 8)
                    ->cell(40, 5, 'FTR', 0, 1, RbPdfTextAlignment::Left, false);
            })
            ->addPage();

        $out = $pdf->output('', RbPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $out);
        self::assertGreaterThan(200, strlen($out));
    }

    /**
     * Exercita a maior parte da superfície da fachada num único fluxo (paridade com a suíte da referência).
     */
    public function test_wide_facade_surface_produces_valid_pdf(): void
    {
        $pngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
        self::assertNotFalse($pngData);

        $pngPath = tempnam(sys_get_temp_dir(), 'rbpdf_png_');
        self::assertNotFalse($pngPath);
        file_put_contents($pngPath, $pngData);

        $attachPath = tempnam(sys_get_temp_dir(), 'rbpdf_att_');
        self::assertNotFalse($attachPath);
        file_put_contents($attachPath, 'attachment-payload');

        try {
            $pdf = (new RbPdf)
                ->protect([], 'u', 'o')
                ->title('T')
                ->author('A')
                ->subject('S')
                ->keywords('k')
                ->creator('C')
                ->displayMode(RbPdfDisplayZoom::Default, RbPdfDisplayLayout::Default)
                ->aliasNbPages()
                ->landscape()
                ->pageSize(RbPdfPageSize::A4)
                ->portrait()
                ->customRbPdfPageSize(100, 120)
                ->margins(12, 14, 12, 20)
                ->leftMargin(11)
                ->topMargin(13)
                ->rightMargin(11)
                ->autoPageBreak(true, 18)
                ->addPage()
                ->font('Helvetica', RbPdfFontStyle::Regular, 10)
                ->setXY(20, 20);
            $pdf->getX();
            $pdf->getY();
            $pdf->getPageWidth();
            $pdf->getPageHeight();
            $pdf->pageNo();

            $linkId = $pdf->addLink();

            $pdf
                ->drawColor(RbPdfColor::rgb(10, 20, 30))
                ->fillColor(RbPdfColor::rgb(240, 240, 240))
                ->textColor(RbPdfColor::rgb(0, 0, 0))
                ->lineWidth(0.2)
                ->lineCap(RbPdfLineCap::Round)
                ->lineJoin(RbPdfLineJoin::Round)
                ->dash(1, 1)
                ->line(10, 10, 40, 15)
                ->undash()
                ->rect(10, 30, 30, 15, 'D')
                ->roundedRect(10, 50, 30, 12, 2, 'D')
                ->circle(25, 75, 4, 'D')
                ->ellipse(25, 85, 6, 4, 0, 'D')
                ->polygon([[10, 95], [30, 95], [20, 105]], 'D')
                ->regularPolygon(25, 115, 5, 5, 0, 'D')
                ->curve(10, 125, 15, 130, 25, 130, 30, 125, 'D')
                ->starPolygon(25, 140, 6, 5, 2, 0, 'D')
                ->pushState()
                ->translate(2, 2)
                ->rotate(5, 20, 150)
                ->scale(1.02, 1.02, 20, 150)
                ->scaleXY(1.01, 20, 150)
                ->skew(1, 0, 20, 150)
                ->mirrorH(30)
                ->mirrorV(160)
                ->popState()
                ->clipRect(10, 165, 50, 20)
                ->cell(40, 6, 'cell', 0, 1, RbPdfTextAlignment::Center, false, $linkId)
                ->multiCell(50, 5, "line1\nline2", 0, RbPdfTextAlignment::Left, false)
                ->write(5, ' write ')
                ->newLine(4)
                ->setX(15)
                ->setY(200);
            $pdf->getStringWidth('abc');
            $pdf->getStringHeight("a\nb", 40, 5);

            $pdf
                ->rotatedText(15, 210, 'rot', 15)
                ->textWithDirection(15, 220, 'dir', RbPdfTextDirection::LeftToRight)
                ->bookmark('B', 0, null)
                ->setLink($linkId, 0, -1)
                ->link(10, 230, 20, 6, 'https://example.test')
                ->opacity(0.9)
                ->fillOpacity(0.85)
                ->strokeOpacity(0.95)
                ->opacity(1.0)
                ->linearGradient(
                    10,
                    240,
                    40,
                    15,
                    [
                        RbPdfGradientStop::at(0.0, RbPdfColor::rgb(255, 0, 0)),
                        RbPdfGradientStop::at(1.0, RbPdfColor::rgb(0, 0, 255)),
                    ],
                )
                ->radialGradient(
                    55,
                    240,
                    35,
                    15,
                    [
                        RbPdfGradientStop::at(0.0, RbPdfColor::rgb(0, 255, 0)),
                        RbPdfGradientStop::at(1.0, RbPdfColor::rgb(0, 0, 0)),
                    ],
                )
                ->svgPath('M 10 10 L 20 10 L 20 20 Z', 'D')
                ->textColumns('col1 col2 col3 words', 2, 4, 80, 40)
                ->barcodeEAN13(10, 10, '123456789012', 10, 0.3)
                ->barcodeCode128(10, 25, 'ABC', 0.4, 8)
                ->qrCode(10, 40, 'q', 12)
                ->circularText(60, 40, 15, 'arc', 'top')
                ->image($pngPath, 120, 10, 10, 10)
                ->imageFit($pngPath, 120, 30, 20, 15, RbPdfImageFit::Fit, RbPdfImageAlign::Center, RbPdfImageValign::Center)
                ->imageScaled($pngPath, 120, 50, 0.5)
                ->rotatedImage($pngPath, 120, 70, 10, 10, 10)
                ->imageFromString($pngData, 120, 85, 8, 8);

            if (extension_loaded('gd')) {
                $im = imagecreatetruecolor(2, 2);
                self::assertNotFalse($im);
                $pdf->imageFromGd($im, 120, 100, 6, 6);
            }

            $pdf
                ->attach($attachPath, 'note.txt', 'desc')
                ->openAttachmentPane()
                ->signatureField(120, 115, 30, 10, 'sig1')
                ->clipCircle(130, 130, 5)
                ->clipEllipse(130, 145, 6, 4);
            $pdf->mmToPoints(10);
            $pdf->pointsToMm(28.35);
            $pdf->cmToPoints(1);
            $pdf->inchesToPoints(1);

            $out = $pdf->output('doc.pdf', RbPdfOutputMode::String);
            self::assertStringStartsWith('%PDF', $out);
            self::assertGreaterThan(800, strlen($out));
        } finally {
            if (is_file($pngPath)) {
                unlink($pngPath);
            }
            if (is_file($attachPath)) {
                unlink($attachPath);
            }
        }
    }

    public function test_protect_without_any_password_throws_rb_pdf_exception(): void
    {
        $pdf = new RbPdf;

        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('protect() requires at least one password');

        $pdf->protect([], null, null);
    }

    public function test_constructor_returns_rb_pdf_instance(): void
    {
        $pdf = new RbPdf;

        self::assertInstanceOf(RbPdf::class, $pdf);
    }

    public function test_consecutive_constructors_yield_distinct_engine_instances(): void
    {
        $a = new RbPdf;
        $b = new RbPdf;

        $engineA = $this->extractEngine($a);
        $engineB = $this->extractEngine($b);

        self::assertNotSame($engineA, $engineB);
    }

    public function test_operations_on_first_instance_do_not_affect_second_instance_page_count(): void
    {
        $first = new RbPdf;
        $second = new RbPdf;

        $first->addPage();

        self::assertSame(0, $second->pageNo());
    }

    private function extractEngine(RbPdf $pdf): RbPdfFpdfEngine
    {
        $ref = new ReflectionClass($pdf);
        $prop = $ref->getProperty('engine');
        $prop->setAccessible(true);
        $engine = $prop->getValue($pdf);

        self::assertInstanceOf(RbPdfFpdfEngine::class, $engine);

        return $engine;
    }
}
