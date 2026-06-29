<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use ValueError;

#[CoversClass(RbPdfPageOrientation::class)]
final class PageOrientationTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfPageOrientation, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Portrait' => [RbPdfPageOrientation::Portrait, 'P'],
            'Landscape' => [RbPdfPageOrientation::Landscape, 'L'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfPageOrientation $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(2, RbPdfPageOrientation::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfPageOrientation $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfPageOrientation::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfPageOrientation::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfPageOrientation::tryFrom('X'));
    }
}
