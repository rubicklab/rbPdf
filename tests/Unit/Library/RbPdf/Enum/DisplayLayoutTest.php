<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfDisplayLayout;
use ValueError;

#[CoversClass(RbPdfDisplayLayout::class)]
final class DisplayLayoutTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfDisplayLayout, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Single' => [RbPdfDisplayLayout::Single, 'single'],
            'Continuous' => [RbPdfDisplayLayout::Continuous, 'continuous'],
            'Two' => [RbPdfDisplayLayout::Two, 'two'],
            'Default' => [RbPdfDisplayLayout::Default, 'default'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfDisplayLayout $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfDisplayLayout::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfDisplayLayout $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfDisplayLayout::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfDisplayLayout::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfDisplayLayout::tryFrom('invalid'));
    }
}
