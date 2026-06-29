<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use ValueError;

#[CoversClass(RbPdfFontStyle::class)]
final class FontStyleTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfFontStyle, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Regular' => [RbPdfFontStyle::Regular, ''],
            'Bold' => [RbPdfFontStyle::Bold, 'B'],
            'Italic' => [RbPdfFontStyle::Italic, 'I'],
            'BoldItalic' => [RbPdfFontStyle::BoldItalic, 'BI'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfFontStyle $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfFontStyle::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfFontStyle $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfFontStyle::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfFontStyle::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfFontStyle::tryFrom('X'));
    }

    public function test_regular_has_empty_string_value(): void
    {
        $this->assertSame('', RbPdfFontStyle::Regular->value);
    }
}
