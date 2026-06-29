<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use ValueError;

#[CoversClass(RbPdfTextAlignment::class)]
final class TextAlignmentTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfTextAlignment, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Left' => [RbPdfTextAlignment::Left, 'L'],
            'Center' => [RbPdfTextAlignment::Center, 'C'],
            'Right' => [RbPdfTextAlignment::Right, 'R'],
            'Justify' => [RbPdfTextAlignment::Justify, 'J'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfTextAlignment $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfTextAlignment::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfTextAlignment $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfTextAlignment::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfTextAlignment::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfTextAlignment::tryFrom('X'));
    }
}
