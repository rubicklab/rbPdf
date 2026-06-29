<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Service;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\RbPdf;

#[CoversNothing]
final class RbPdfImageIntegrationTest extends TestCase
{
    public function test_pdf_with_embedded_raster_image(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        self::assertNotFalse($png);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_integ_img_'.uniqid('', true).'.png';
        file_put_contents($path, $png);

        try {
            $binary = (new RbPdf)
                ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4)
                ->font('Helvetica', RbPdfFontStyle::Regular, 10)
                ->image($path, 20, 30, 15, 15)
                ->toString();

            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(1500, strlen($binary), 'PDF com imagem embutida deve exceder limiar mínimo.');
        } finally {
            unlink($path);
        }
    }
}
