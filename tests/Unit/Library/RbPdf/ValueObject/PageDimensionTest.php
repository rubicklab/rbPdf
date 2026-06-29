<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\ValueObject\RbPdfPageDimension;

#[CoversClass(RbPdfPageDimension::class)]
final class PageDimensionTest extends TestCase
{
    public function test_from_enum_creates_with_enum_and_null_dimensions(): void
    {
        $dim = RbPdfPageDimension::fromEnum(RbPdfPageSize::A4);

        $this->assertSame(RbPdfPageSize::A4, $dim->enum);
        $this->assertNull($dim->width);
        $this->assertNull($dim->height);
    }

    public function test_from_enum_to_fpdf_param_returns_string(): void
    {
        $dim = RbPdfPageDimension::fromEnum(RbPdfPageSize::A4);

        $this->assertSame('A4', $dim->toFpdfParam());
    }

    public function test_from_enum_with_letter_size(): void
    {
        $dim = RbPdfPageDimension::fromEnum(RbPdfPageSize::Letter);

        $this->assertSame(RbPdfPageSize::Letter, $dim->enum);
        $this->assertSame('Letter', $dim->toFpdfParam());
    }

    public function test_from_enum_with_legal_size(): void
    {
        $dim = RbPdfPageDimension::fromEnum(RbPdfPageSize::Legal);

        $this->assertSame('Legal', $dim->toFpdfParam());
    }

    public function test_custom_creates_with_dimensions_and_null_enum(): void
    {
        $dim = RbPdfPageDimension::custom(200.0, 300.0);

        $this->assertNull($dim->enum);
        $this->assertSame(200.0, $dim->width);
        $this->assertSame(300.0, $dim->height);
    }

    public function test_custom_to_fpdf_param_returns_array(): void
    {
        $dim = RbPdfPageDimension::custom(200.0, 300.0);

        $this->assertSame([200.0, 300.0], $dim->toFpdfParam());
    }

    public function test_custom_with_small_dimensions(): void
    {
        $dim = RbPdfPageDimension::custom(0.1, 0.1);

        $this->assertSame([0.1, 0.1], $dim->toFpdfParam());
    }

    public function test_custom_with_integer_values(): void
    {
        $dim = RbPdfPageDimension::custom(100, 200);

        $this->assertSame(100.0, $dim->width);
        $this->assertSame(200.0, $dim->height);
    }

    public function test_custom_with_zero_width_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessageMatches('/Invalid page dimensions/');

        RbPdfPageDimension::custom(0, 300);
    }

    public function test_custom_with_zero_height_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessageMatches('/Invalid page dimensions/');

        RbPdfPageDimension::custom(200, 0);
    }

    public function test_custom_with_negative_width_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessageMatches('/Invalid page dimensions/');

        RbPdfPageDimension::custom(-10, 300);
    }

    public function test_custom_with_negative_height_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessageMatches('/Invalid page dimensions/');

        RbPdfPageDimension::custom(200, -10);
    }

    public function test_custom_with_both_negative_throws_exception(): void
    {
        $this->expectException(RbPdfException::class);

        RbPdfPageDimension::custom(-5, -5);
    }

    public function test_exception_message_contains_dimensions(): void
    {
        try {
            RbPdfPageDimension::custom(0, 300);
            $this->fail('Expected RbPdfException was not thrown');
        } catch (RbPdfException $e) {
            $this->assertStringContainsString('0', $e->getMessage());
            $this->assertStringContainsString('300', $e->getMessage());
            $this->assertStringContainsString('greater than zero', $e->getMessage());
        }
    }

    public function test_is_readonly(): void
    {
        $dim = RbPdfPageDimension::fromEnum(RbPdfPageSize::A4);

        $reflection = new \ReflectionClass($dim);
        $this->assertTrue($reflection->isReadOnly());
    }
}
