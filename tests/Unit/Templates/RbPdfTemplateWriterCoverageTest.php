<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Config\RbPdfConfiguration;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\Templates\RbPdfTemplateWriter;
use Rubick\RbPdf\Tests\Fixtures\Templates\ConcreteRbPdfTemplateForTests;
use Rubick\RbPdf\Tests\Fixtures\Templates\RbPdfTemplateWriterCoverageStub;

#[CoversClass(RbPdfTemplateWriter::class)]
final class RbPdfTemplateWriterCoverageTest extends TestCase
{
    public function test_drawing_helpers_execute_against_pdf(): void
    {
        $root = dirname(__DIR__, 3);
        $ttf = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($ttf)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_tpl_cov_'.uniqid('', true);
        self::assertTrue(mkdir($cacheDir, 0777, true));

        try {
            $template = new RbPdfTemplateWriterCoverageStub([]);

            $pdf = (new RbPdf)
                ->landscape()
                ->addPage(RbPdfPageOrientation::Landscape, RbPdfPageSize::A4)
                ->addTrueTypeFont('Inter', RbPdfFontStyle::Regular, $ttf, $cacheDir)
                ->addTrueTypeFont('Inter', RbPdfFontStyle::Bold, $ttf, $cacheDir)
                ->font('Inter', RbPdfFontStyle::Regular, 8);

            $template->runDrawingSamples($pdf);

            $binary = $pdf->toString();
            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(1500, strlen($binary));
        } finally {
            $this->deleteTree($cacheDir);
        }
    }

    public function test_resolve_local_asset_path_without_public_base_returns_null(): void
    {
        $tpl = new RbPdfTemplateWriterCoverageStub(
            RbPdfConfiguration::fromArray(['paths' => ['public' => '']]),
        );

        self::assertNull($tpl->resolveLocalForTest('img/x.png'));
    }

    public function test_resolve_local_asset_path_with_base_finds_file(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_pub_'.uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));
        $path = $dir.DIRECTORY_SEPARATOR.'logo.png';
        file_put_contents($path, 'x');

        try {
            $tpl = new RbPdfTemplateWriterCoverageStub(
                RbPdfConfiguration::fromArray(['paths' => ['public' => $dir]]),
            );

            self::assertSame($path, $tpl->resolveLocalForTest('logo.png'));
            self::assertNull($tpl->resolveLocalForTest('missing.png'));
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }

    public function test_from_config_file_throws_when_missing(): void
    {
        $this->expectException(RbPdfException::class);
        ConcreteRbPdfTemplateForTests::fromConfigFile('/nonexistent/rbpdf.php');
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
