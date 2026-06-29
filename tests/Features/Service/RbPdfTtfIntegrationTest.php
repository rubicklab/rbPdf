<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Service;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;

#[CoversNothing]
final class RbPdfTtfIntegrationTest extends TestCase
{
    public function test_pdf_with_true_type_font_renders(): void
    {
        $root = dirname(__DIR__, 3);
        $font = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture tests/Fixtures/fonts/DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_integ_ttf_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $binary = (new RbPdf)
                ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4)
                ->addTrueTypeFont('DejaVuInteg', RbPdfFontStyle::Regular, $font, $cache)
                ->font('DejaVuInteg', RbPdfFontStyle::Regular, 12)
                ->cell(0, 8, 'TTF UTF-8: áéí óú', 0, 1, RbPdfTextAlignment::Left, false)
                ->toString();

            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(4000, strlen($binary), 'PDF com TTF deve ter tamanho substancial.');
        } finally {
            $this->deleteTree($cache);
        }
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $f) {
            $path = $f->getPathname();
            $f->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
