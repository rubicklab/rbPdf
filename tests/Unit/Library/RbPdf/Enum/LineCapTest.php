<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfLineCap;
use ValueError;

#[CoversClass(RbPdfLineCap::class)]
final class LineCapTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfLineCap, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Butt' => [RbPdfLineCap::Butt, 'butt'],
            'Round' => [RbPdfLineCap::Round, 'round'],
            'Square' => [RbPdfLineCap::Square, 'square'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfLineCap $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, RbPdfLineCap::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfLineCap $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfLineCap::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfLineCap::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfLineCap::tryFrom('invalid'));
    }
}
