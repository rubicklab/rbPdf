<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Tooling;

use PHPUnit\Framework\TestCase;

final class RbPdfNamingConventionTest extends TestCase
{
    public function test_public_surface_under_src_follows_rb_pdf_prefix(): void
    {
        $root = dirname(__DIR__, 3);
        $src = $root.DIRECTORY_SEPARATOR.'src';

        $violations = RbPdfNamingConventionScanner::scanSourceTree($src);

        self::assertSame(
            [],
            $violations,
            "Violações RF-04 (prefixo RbPdf):\n".implode("\n", $violations)
        );
    }

    public function test_scanner_flags_class_without_prefix(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_naming_test_'.uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));

        try {
            file_put_contents(
                $dir.DIRECTORY_SEPARATOR.'BadActor.php',
                <<<'PHP'
<?php

declare(strict_types=1);

namespace X;

final class NotRbPdfNamed
{
}

PHP
            );

            $violations = RbPdfNamingConventionScanner::scanSourceTree($dir);

            self::assertNotEmpty($violations);
            self::assertStringContainsString('NotRbPdfNamed', $violations[0]);
        } finally {
            $this->deleteTree($dir);
        }
    }

    public function test_scanner_ignores_anonymous_class(): void
    {
        $php = <<<'PHP'
<?php

declare(strict_types=1);

namespace X;

$o = new class {
    public function x(): void {}
};

final class RbPdfOk
{
}

PHP;

        $names = RbPdfNamingConventionScanner::extractTypeNames($php);
        self::assertSame(['RbPdfOk'], $names['class']);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
