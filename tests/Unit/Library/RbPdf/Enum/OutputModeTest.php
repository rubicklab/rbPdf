<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfOutputMode;
use ValueError;

#[CoversClass(RbPdfOutputMode::class)]
final class OutputModeTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfOutputMode, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Inline' => [RbPdfOutputMode::Inline, 'I'],
            'Download' => [RbPdfOutputMode::Download, 'D'],
            'File' => [RbPdfOutputMode::File, 'F'],
            'String' => [RbPdfOutputMode::String, 'S'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfOutputMode $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfOutputMode::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfOutputMode $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfOutputMode::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfOutputMode::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfOutputMode::tryFrom('X'));
    }
}
