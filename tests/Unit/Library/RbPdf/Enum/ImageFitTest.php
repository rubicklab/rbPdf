<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfImageFit;
use ValueError;

#[CoversClass(RbPdfImageFit::class)]
final class ImageFitTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfImageFit, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'None' => [RbPdfImageFit::None, 'none'],
            'Fit' => [RbPdfImageFit::Fit, 'fit'],
            'Cover' => [RbPdfImageFit::Cover, 'cover'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfImageFit $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, RbPdfImageFit::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfImageFit $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfImageFit::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfImageFit::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfImageFit::tryFrom('invalid'));
    }
}
