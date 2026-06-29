<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Templates;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Config\RbPdfConfiguration;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\Templates\RbPdfTemplateWriter;
use Rubick\RbPdf\Tests\Fixtures\Templates\ConcreteRbPdfTemplateForTests;

#[CoversClass(RbPdfTemplateWriter::class)]
final class RbPdfTemplateWriterSmokeTest extends TestCase
{
    public function test_smoke_from_package_config_file_with_ttf_stub(): void
    {
        $root = dirname(__DIR__, 3);
        $ttf = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($ttf)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $configPath = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'rbpdf.php';
        self::assertFileExists($configPath);

        /** @var array<string, mixed> $configData */
        $configData = require $configPath;
        $configuration = RbPdfConfiguration::fromArray($configData);

        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_tpl_smoke_'.uniqid('', true);
        self::assertTrue(mkdir($cacheDir, 0777, true));

        try {
            $template = new class($configuration, $ttf, $cacheDir) extends RbPdfTemplateWriter
            {
                public function __construct(
                    RbPdfConfiguration $configuration,
                    private readonly string $ttfPath,
                    private readonly string $fontCacheDir,
                ) {
                    parent::__construct($configuration);
                }

                protected function registerFonts(RbPdf $pdf): void
                {
                    $pdf->addTrueTypeFont('Inter', RbPdfFontStyle::Regular, $this->ttfPath, $this->fontCacheDir)
                        ->addTrueTypeFont('Inter', RbPdfFontStyle::Bold, $this->ttfPath, $this->fontCacheDir);
                }

                public function build(): string
                {
                    $pdf = $this->initialize('RBPDF_SMOKE_HDR', '');
                    $pdf->title('RBPDF_SMOKE_DOC_TITLE');

                    return $pdf->cell(0, 8, 'RBPDF_SMOKE_BODY', 0, 1)->toString();
                }
            };

            $binary = $template->build();

            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(2000, strlen($binary));
            self::assertTrue(
                str_contains($binary, 'RBPDF_SMOKE_DOC_TITLE'),
                'Metadado do documento deve aparecer no stream PDF (texto do corpo pode estar comprimido).'
            );
        } finally {
            $this->deleteTree($cacheDir);
        }
    }

    public function test_carbon_formats_date_according_to_locale_config(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00', 'UTC'));
        try {
            $writer = new class(RbPdfConfiguration::fromArray(['datetime' => ['locale' => 'en', 'timezone' => 'UTC']])) extends RbPdfTemplateWriter
            {
                public function previewSubtitle(): string
                {
                    $tz = (string) $this->configuration->get('datetime.timezone', 'UTC');
                    $locale = (string) $this->configuration->get('datetime.locale', 'en');

                    return $this->upper(
                        Carbon::now($tz)->locale($locale)->translatedFormat('d M Y'),
                    );
                }
            };

            self::assertSame('10 JAN 2024', $writer->previewSubtitle());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_static_from_config_file_loads_package_config(): void
    {
        $root = dirname(__DIR__, 3);
        $path = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'rbpdf.php';

        $instance = ConcreteRbPdfTemplateForTests::fromConfigFile($path);

        self::assertInstanceOf(RbPdfTemplateWriter::class, $instance);
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
