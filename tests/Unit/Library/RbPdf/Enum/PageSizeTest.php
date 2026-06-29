<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use ValueError;

#[CoversClass(RbPdfPageSize::class)]
final class PageSizeTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfPageSize, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'A4' => [RbPdfPageSize::A4, 'A4'],
            'Letter' => [RbPdfPageSize::Letter, 'Letter'],
            'Legal' => [RbPdfPageSize::Legal, 'Legal'],
            'A3' => [RbPdfPageSize::A3, 'A3'],
            'A5' => [RbPdfPageSize::A5, 'A5'],
            'A6' => [RbPdfPageSize::A6, 'A6'],
            'B4' => [RbPdfPageSize::B4, 'B4'],
            'B5' => [RbPdfPageSize::B5, 'B5'],
            'Folio' => [RbPdfPageSize::Folio, 'Folio'],
            'Executive' => [RbPdfPageSize::Executive, 'Executive'],
            'Tabloid' => [RbPdfPageSize::Tabloid, 'Tabloid'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfPageSize $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(11, RbPdfPageSize::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfPageSize $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfPageSize::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfPageSize::from('Unknown');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfPageSize::tryFrom('Unknown'));
    }
}
