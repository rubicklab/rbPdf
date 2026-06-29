<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfImageValign;
use ValueError;

#[CoversClass(RbPdfImageValign::class)]
final class ImageValignTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfImageValign, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Top' => [RbPdfImageValign::Top, 'top'],
            'Center' => [RbPdfImageValign::Center, 'center'],
            'Bottom' => [RbPdfImageValign::Bottom, 'bottom'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfImageValign $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, RbPdfImageValign::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfImageValign $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfImageValign::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfImageValign::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfImageValign::tryFrom('invalid'));
    }
}
