<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\ValueObject;

use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Contract\RbPdfColorInterface;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\ValueObject\RbPdfCmykColor;

#[CoversClass(RbPdfCmykColor::class)]
final class PdfCmykColorTest extends TestCase
{
    public function test_cmyk_creates_instance_with_correct_components(): void
    {
        $color = RbPdfCmykColor::cmyk(0, 100, 50, 25);

        $this->assertSame(0, $color->cyan);
        $this->assertSame(100, $color->magenta);
        $this->assertSame(50, $color->yellow);
        $this->assertSame(25, $color->black);
    }

    public function test_constructor_creates_instance_with_correct_components(): void
    {
        $color = new RbPdfCmykColor(10, 20, 30, 40);

        $this->assertSame(10, $color->cyan);
        $this->assertSame(20, $color->magenta);
        $this->assertSame(30, $color->yellow);
        $this->assertSame(40, $color->black);
    }

    public function test_implements_rb_pdf_color_interface(): void
    {
        $color = RbPdfCmykColor::cmyk(0, 0, 0, 0);

        $this->assertInstanceOf(RbPdfColorInterface::class, $color);
    }

    public function test_to_array_returns_four_components(): void
    {
        $color = RbPdfCmykColor::cmyk(10, 20, 30, 40);

        $this->assertSame([10, 20, 30, 40], $color->toArray());
    }

    public function test_to_array_with_all_zeros(): void
    {
        $this->assertSame([0, 0, 0, 0], RbPdfCmykColor::cmyk(0, 0, 0, 0)->toArray());
    }

    public function test_to_array_with_all_hundred(): void
    {
        $this->assertSame([100, 100, 100, 100], RbPdfCmykColor::cmyk(100, 100, 100, 100)->toArray());
    }

    public function test_boundary_values_zero_do_not_throw(): void
    {
        $color = RbPdfCmykColor::cmyk(0, 0, 0, 0);

        $this->assertSame(0, $color->cyan);
        $this->assertSame(0, $color->magenta);
        $this->assertSame(0, $color->yellow);
        $this->assertSame(0, $color->black);
    }

    public function test_boundary_values_hundred_do_not_throw(): void
    {
        $color = RbPdfCmykColor::cmyk(100, 100, 100, 100);

        $this->assertSame(100, $color->cyan);
        $this->assertSame(100, $color->magenta);
        $this->assertSame(100, $color->yellow);
        $this->assertSame(100, $color->black);
    }

    public function test_cyan_above100_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'cyan': 101");

        RbPdfCmykColor::cmyk(101, 0, 0, 0);
    }

    public function test_magenta_above100_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'magenta': 200");

        RbPdfCmykColor::cmyk(0, 200, 0, 0);
    }

    public function test_yellow_above100_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'yellow': 150");

        RbPdfCmykColor::cmyk(0, 0, 150, 0);
    }

    public function test_black_above100_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'black': 999");

        RbPdfCmykColor::cmyk(0, 0, 0, 999);
    }

    public function test_cyan_below_zero_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'cyan': -1");

        RbPdfCmykColor::cmyk(-1, 0, 0, 0);
    }

    public function test_magenta_below_zero_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'magenta': -50");

        RbPdfCmykColor::cmyk(0, -50, 0, 0);
    }

    public function test_yellow_below_zero_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'yellow': -10");

        RbPdfCmykColor::cmyk(0, 0, -10, 0);
    }

    public function test_black_below_zero_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'black': -100");

        RbPdfCmykColor::cmyk(0, 0, 0, -100);
    }

    public function test_exception_message_contains_how_to_fix(): void
    {
        try {
            RbPdfCmykColor::cmyk(101, 0, 0, 0);
            $this->fail('Expected RbPdfException was not thrown');
        } catch (RbPdfException $e) {
            $this->assertStringContainsString('between 0 and 100', $e->getMessage());
        }
    }

    public function test_readonly_properties_cannot_be_modified(): void
    {
        $color = RbPdfCmykColor::cmyk(50, 50, 50, 50);

        $this->expectException(Error::class);

        /** @phpstan-ignore-next-line Intentionally testing readonly violation */
        $color->cyan = 10;
    }

    /**
     * @return array<string, array{int, int, int, int}>
     */
    public static function validCmykProvider(): array
    {
        return [
            'pure cyan' => [100, 0, 0, 0],
            'pure magenta' => [0, 100, 0, 0],
            'pure yellow' => [0, 0, 100, 0],
            'pure black' => [0, 0, 0, 100],
            'rich black' => [60, 40, 40, 100],
            'mid values' => [50, 50, 50, 50],
        ];
    }

    #[DataProvider('validCmykProvider')]
    public function test_valid_cmyk_combinations(int $c, int $m, int $y, int $k): void
    {
        $color = RbPdfCmykColor::cmyk($c, $m, $y, $k);

        $this->assertSame([$c, $m, $y, $k], $color->toArray());
    }
}
