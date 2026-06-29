<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\RbPdf;

use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;

final class RbPdfGenerationTest extends TestCase
{
    public function test_factory_to_text_to_binary_string(): void
    {
        $pdf = new RbPdf;

        $binary = $pdf
            ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4)
            ->font('Helvetica', RbPdfFontStyle::Regular, 12)
            ->cell(0, 10, 'RbPdf integration', 0, 1, RbPdfTextAlignment::Left, false)
            ->toString();

        self::assertStringStartsWith('%PDF', $binary);
        self::assertStringContainsString('%%EOF', $binary);
        self::assertGreaterThan(200, strlen($binary));
    }

    public function test_save_writes_non_empty_file(): void
    {
        $root = dirname(__DIR__, 3);
        $tmpDir = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $path = $tmpDir.DIRECTORY_SEPARATOR.'rbpdf_task4_'.bin2hex(random_bytes(8)).'.pdf';

        try {
            (new RbPdf)
                ->addPage()
                ->font('Helvetica', RbPdfFontStyle::Regular, 10)
                ->cell(50, 8, 'file', 0, 1, RbPdfTextAlignment::Left, false)
                ->save($path);

            self::assertFileExists($path);
            self::assertGreaterThan(0, filesize($path));
            $contents = file_get_contents($path);
            self::assertIsString($contents);
            self::assertStringStartsWith('%PDF', $contents);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
