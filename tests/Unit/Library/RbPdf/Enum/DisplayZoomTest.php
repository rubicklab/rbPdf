<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfDisplayZoom;
use ValueError;

#[CoversClass(RbPdfDisplayZoom::class)]
final class DisplayZoomTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfDisplayZoom, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'FullPage' => [RbPdfDisplayZoom::FullPage, 'fullpage'],
            'FullWidth' => [RbPdfDisplayZoom::FullWidth, 'fullwidth'],
            'Real' => [RbPdfDisplayZoom::Real, 'real'],
            'Default' => [RbPdfDisplayZoom::Default, 'default'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfDisplayZoom $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfDisplayZoom::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfDisplayZoom $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfDisplayZoom::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfDisplayZoom::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfDisplayZoom::tryFrom('invalid'));
    }
}
