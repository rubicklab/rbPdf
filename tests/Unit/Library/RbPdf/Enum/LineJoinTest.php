<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfLineJoin;
use ValueError;

#[CoversClass(RbPdfLineJoin::class)]
final class LineJoinTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfLineJoin, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Miter' => [RbPdfLineJoin::Miter, 'miter'],
            'Round' => [RbPdfLineJoin::Round, 'round'],
            'Bevel' => [RbPdfLineJoin::Bevel, 'bevel'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfLineJoin $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, RbPdfLineJoin::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfLineJoin $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfLineJoin::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfLineJoin::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfLineJoin::tryFrom('invalid'));
    }
}
