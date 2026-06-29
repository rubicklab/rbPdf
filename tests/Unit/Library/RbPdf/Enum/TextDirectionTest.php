<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfTextDirection;
use ValueError;

#[CoversClass(RbPdfTextDirection::class)]
final class TextDirectionTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfTextDirection, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'LeftToRight' => [RbPdfTextDirection::LeftToRight, 'R'],
            'BottomToTop' => [RbPdfTextDirection::BottomToTop, 'U'],
            'TopToBottom' => [RbPdfTextDirection::TopToBottom, 'D'],
            'RightToLeft' => [RbPdfTextDirection::RightToLeft, 'L'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfTextDirection $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfTextDirection::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfTextDirection $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfTextDirection::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfTextDirection::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfTextDirection::tryFrom('X'));
    }
}
