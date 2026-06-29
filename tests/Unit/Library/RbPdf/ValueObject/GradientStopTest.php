<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\ValueObject\RbPdfCmykColor;
use Rubick\RbPdf\ValueObject\RbPdfColor;
use Rubick\RbPdf\ValueObject\RbPdfGradientStop;
use ReflectionClass;

#[CoversClass(RbPdfGradientStop::class)]
final class GradientStopTest extends TestCase
{
    public function test_constructor_sets_position_and_color(): void
    {
        $color = RbPdfColor::rgb(255, 0, 0);
        $stop = new RbPdfGradientStop(0.25, $color);

        $this->assertSame(0.25, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_at_factory_creates_instance(): void
    {
        $color = RbPdfColor::rgb(255, 0, 0);
        $stop = RbPdfGradientStop::at(0.25, $color);

        $this->assertSame(0.25, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_position_zero_is_valid(): void
    {
        $color = RbPdfColor::rgb(255, 0, 0);
        $stop = new RbPdfGradientStop(0.0, $color);

        $this->assertSame(0.0, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_position_one_is_valid(): void
    {
        $color = RbPdfColor::rgb(255, 0, 0);
        $stop = new RbPdfGradientStop(1.0, $color);

        $this->assertSame(1.0, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_position_below_zero_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('Invalid gradient stop position');

        new RbPdfGradientStop(-0.1, RbPdfColor::rgb(255, 0, 0));
    }

    public function test_position_above_one_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);

        new RbPdfGradientStop(1.1, RbPdfColor::rgb(255, 0, 0));
    }

    public function test_position_middle_is_valid(): void
    {
        $color = RbPdfColor::rgb(255, 0, 0);
        $stop = new RbPdfGradientStop(0.5, $color);

        $this->assertSame(0.5, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_is_readonly(): void
    {
        $reflection = new ReflectionClass(RbPdfGradientStop::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_at_returns_gradient_stop_instance(): void
    {
        $stop = RbPdfGradientStop::at(0.5, RbPdfColor::rgb(255, 0, 0));

        $this->assertInstanceOf(RbPdfGradientStop::class, $stop);
    }

    public function test_accepts_cmyk_color(): void
    {
        $cmyk = RbPdfCmykColor::cmyk(10, 20, 30, 40);
        $stop = RbPdfGradientStop::at(0.75, $cmyk);

        $this->assertSame($cmyk, $stop->color);
        $this->assertSame([10, 20, 30, 40], $stop->color->toArray());
    }
}
