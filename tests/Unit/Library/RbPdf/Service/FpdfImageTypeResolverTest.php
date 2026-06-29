<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Service\RbPdfFpdfImageTypeResolver;

#[CoversClass(RbPdfFpdfImageTypeResolver::class)]
final class FpdfImageTypeResolverTest extends TestCase
{
    public function test_detects_png_by_magic_bytes(): void
    {
        $path = $this->writeTempImage(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true),
            'png',
        );

        try {
            $r = RbPdfFpdfImageTypeResolver::resolve($path);
            self::assertSame(RbPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            self::assertSame('png', $r['type']);
        } finally {
            unlink($path);
        }
    }

    public function test_detects_jpeg_by_magic_bytes(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('ext-gd necessário para gerar JPEG de teste.');
        }

        $im = imagecreatetruecolor(2, 2);
        self::assertNotFalse($im);
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_img_'.uniqid('', true).'.jpg';
        self::assertTrue(imagejpeg($im, $path));
        imagedestroy($im);

        try {
            $r = RbPdfFpdfImageTypeResolver::resolve($path);
            self::assertSame(RbPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            self::assertSame('jpg', $r['type']);
        } finally {
            unlink($path);
        }
    }

    public function test_detects_gif_by_magic_bytes(): void
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);
        self::assertNotFalse($gif);
        $path = $this->writeTempImage($gif, 'gif');

        try {
            $r = RbPdfFpdfImageTypeResolver::resolve($path);
            self::assertSame(RbPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            self::assertSame('gif', $r['type']);
        } finally {
            unlink($path);
        }
    }

    public function test_missing_file_returns_unknown_type(): void
    {
        $r = RbPdfFpdfImageTypeResolver::resolve('/no/such/image_'.uniqid().'.png');
        self::assertSame(RbPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
        self::assertSame('', $r['type']);
    }

    public function test_webp_falls_back_to_gd_when_raster_supported(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            self::markTestSkipped('ext-gd com imagewebp necessário.');
        }

        $im = imagecreatetruecolor(2, 2);
        self::assertNotFalse($im);
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_webp_'.uniqid('', true).'.webp';
        self::assertTrue(imagewebp($im, $path));
        imagedestroy($im);

        try {
            $r = RbPdfFpdfImageTypeResolver::resolve($path);
            if ($r['mode'] === RbPdfFpdfImageTypeResolver::MODE_GD) {
                self::assertArrayHasKey('gd', $r);
                imagedestroy($r['gd']);
            } else {
                self::assertSame(RbPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            }
        } finally {
            unlink($path);
        }
    }

    /**
     * @param  non-empty-string  $suffix
     */
    private function writeTempImage(string $binary, string $suffix): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf_img_'.uniqid('', true).'.'.$suffix;
        file_put_contents($path, $binary);

        return $path;
    }
}
