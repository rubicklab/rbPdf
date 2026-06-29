<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfImageAlign;
use ValueError;

#[CoversClass(RbPdfImageAlign::class)]
final class ImageAlignTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfImageAlign, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Left' => [RbPdfImageAlign::Left, 'left'],
            'Center' => [RbPdfImageAlign::Center, 'center'],
            'Right' => [RbPdfImageAlign::Right, 'right'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfImageAlign $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, RbPdfImageAlign::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfImageAlign $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfImageAlign::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfImageAlign::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfImageAlign::tryFrom('invalid'));
    }
}
