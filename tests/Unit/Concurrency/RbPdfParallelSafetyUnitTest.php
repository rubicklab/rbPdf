<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Concurrency;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Service\RbPdfTtfFontCacheConfig;

#[CoversClass(RbPdfTtfFontCacheConfig::class)]
final class RbPdfParallelSafetyUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('RBPDF_CACHE_DIR');
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');
        parent::tearDown();
    }

    public function test_unique_test_token_takes_precedence_over_test_token_for_cache_path(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_unit_tok_'.uniqid('', true);
        putenv('RBPDF_CACHE_DIR='.$base);
        putenv('TEST_TOKEN=ignored_when_unique_set');
        putenv('UNIQUE_TEST_TOKEN=runner_xyz');

        $path = RbPdfTtfFontCacheConfig::resolve(null);

        self::assertStringContainsString('runner_xyz', $path);
        self::assertStringNotContainsString('ignored_when_unique_set', $path);
    }

    public function test_test_token_isolated_paths_differ_for_same_base(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_unit_tok2_'.uniqid('', true);
        putenv('RBPDF_CACHE_DIR='.$base);

        putenv('TEST_TOKEN=alpha_proc');
        $a = RbPdfTtfFontCacheConfig::resolve(null);

        putenv('TEST_TOKEN=beta_proc');
        $b = RbPdfTtfFontCacheConfig::resolve(null);

        self::assertNotSame($a, $b);
    }
}
