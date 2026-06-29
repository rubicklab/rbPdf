<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\Service\RbPdfTtfFontResolver;
use Rubick\RbPdf\ValueObject\RbPdfResolvedTtfFont;

#[CoversClass(RbPdfTtfFontResolver::class)]
final class TtfFontResolverTest extends TestCase
{
    public function test_rejects_missing_font_file(): void
    {
        $resolver = new RbPdfTtfFontResolver;
        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_unit_cache_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $this->expectException(RbPdfException::class);
            $this->expectExceptionMessage('not found or not readable');

            $resolver->ensureFpdfFontFiles(
                sys_get_temp_dir().DIRECTORY_SEPARATOR.'missing_font_'.uniqid('', true).'.ttf',
                $cache,
            );
        } finally {
            rmdir($cache);
        }
    }

    public function test_rejects_unsupported_extension(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rbpdf_font_txt_');
        self::assertNotFalse($path);
        file_put_contents($path, 'not a font');

        $resolver = new RbPdfTtfFontResolver;
        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_unit_cache2_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $this->expectException(RbPdfException::class);
            $this->expectExceptionMessage('Unsupported extension');

            $resolver->ensureFpdfFontFiles($path, $cache);
        } finally {
            unlink($path);
            rmdir($cache);
        }
    }

    public function test_throws_when_injected_makefont_script_missing(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_mkfont_bad_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $badScript = sys_get_temp_dir().DIRECTORY_SEPARATOR.'not_makefont_'.uniqid('', true).'.php';
        self::assertFileDoesNotExist($badScript);

        $resolver = new RbPdfTtfFontResolver($badScript);

        try {
            $this->expectException(RbPdfException::class);
            $this->expectExceptionMessageMatches('/Invalid makefont path/');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function test_throws_when_makefont_produces_tiny_font_php(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $scriptPhp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_mf_tinyphp_'.uniqid('', true).'.php';
        file_put_contents(
            $scriptPhp,
            <<<'PHP'
<?php
declare(strict_types=1);
$cwd = getcwd();
if ($cwd === false) {
    fwrite(STDERR, 'no cwd');
    exit(1);
}
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.php', 'short');
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.z', 'z');
exit(0);
PHP
        );

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_tinyphp_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $resolver = new RbPdfTtfFontResolver($scriptPhp);

        try {
            $this->expectException(RbPdfException::class);
            $this->expectExceptionMessage('expected font.php');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            unlink($scriptPhp);
            $this->deleteTree($cache);
        }
    }

    public function test_throws_when_makefont_produces_empty_font_z(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $scriptPhp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_mf_emptyz_'.uniqid('', true).'.php';
        file_put_contents(
            $scriptPhp,
            <<<'PHP'
<?php
declare(strict_types=1);
$cwd = getcwd();
if ($cwd === false) {
    fwrite(STDERR, 'no cwd');
    exit(1);
}
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.php', str_repeat('x', 50));
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.z', '');
exit(0);
PHP
        );

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_emptyz_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $resolver = new RbPdfTtfFontResolver($scriptPhp);

        try {
            $this->expectException(RbPdfException::class);
            $this->expectExceptionMessage('expected font.z');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            unlink($scriptPhp);
            $this->deleteTree($cache);
        }
    }

    public function test_makefont_process_failure_throws_with_exit_code(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $script = tempnam(sys_get_temp_dir(), 'rbpdf_fake_mf_');
        self::assertNotFalse($script);
        $scriptPhp = $script.'.php';
        self::assertTrue(rename($script, $scriptPhp));
        file_put_contents($scriptPhp, "<?php\nfwrite(STDERR, 'simulated failure');\nexit(3);\n");

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_mkfont_fail_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $resolver = new RbPdfTtfFontResolver($scriptPhp);

        try {
            $this->expectException(RbPdfException::class);
            $this->expectExceptionMessageMatches('/makefont failed/');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            unlink($scriptPhp);
            $this->deleteTree($cache);
        }
    }

    public function test_rebuilds_when_complete_marker_json_is_invalid(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_bad_json_marker_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $hash = hash_file('sha256', $font);
        self::assertNotFalse($hash);
        $entry = $cache.DIRECTORY_SEPARATOR.$hash;
        self::assertTrue(mkdir($entry, 0777, true));
        file_put_contents($entry.DIRECTORY_SEPARATOR.'.complete', '{invalid json');

        try {
            $resolver = new RbPdfTtfFontResolver;
            $resolved = $resolver->ensureFpdfFontFiles($font, $cache);
            self::assertInstanceOf(RbPdfResolvedTtfFont::class, $resolved);
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function test_rebuilds_when_complete_marker_version_is_stale(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_stale_marker_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $hash = hash_file('sha256', $font);
        self::assertNotFalse($hash);
        $entry = $cache.DIRECTORY_SEPARATOR.$hash;
        self::assertTrue(mkdir($entry, 0777, true));
        file_put_contents(
            $entry.DIRECTORY_SEPARATOR.'.complete',
            json_encode(['v' => 0, 'sha256' => $hash], JSON_THROW_ON_ERROR),
        );
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.php', str_repeat('<?php //', 40));
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.z', 'z');

        try {
            $resolver = new RbPdfTtfFontResolver;
            $resolved = $resolver->ensureFpdfFontFiles($font, $cache);
            self::assertSame('font.php', $resolved->definitionFileName);
            self::assertFileExists($entry.DIRECTORY_SEPARATOR.'font.z');
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function test_rebuilds_when_complete_marker_sha256_mismatch(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_sha_mismatch_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $hash = hash_file('sha256', $font);
        self::assertNotFalse($hash);
        $entry = $cache.DIRECTORY_SEPARATOR.$hash;
        self::assertTrue(mkdir($entry, 0777, true));
        file_put_contents(
            $entry.DIRECTORY_SEPARATOR.'.complete',
            json_encode(['v' => 1, 'sha256' => str_repeat('0', 64)], JSON_THROW_ON_ERROR),
        );
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.php', str_repeat('<?php //', 40));
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.z', 'zdata');

        try {
            $resolver = new RbPdfTtfFontResolver;
            $resolved = $resolver->ensureFpdfFontFiles($font, $cache);
            self::assertInstanceOf(RbPdfResolvedTtfFont::class, $resolved);
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function test_instance_cache_returns_same_object_for_repeated_calls(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente (copie tests/Fixtures/fonts).');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_unit_ttf_cache_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $resolver = new RbPdfTtfFontResolver;
            $a = $resolver->ensureFpdfFontFiles($font, $cache);
            $b = $resolver->ensureFpdfFontFiles($font, $cache);

            self::assertInstanceOf(RbPdfResolvedTtfFont::class, $a);
            self::assertSame($a, $b);
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
