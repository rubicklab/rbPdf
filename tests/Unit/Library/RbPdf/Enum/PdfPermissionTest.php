<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfPermission;
use ValueError;

#[CoversClass(RbPdfPermission::class)]
final class PdfPermissionTest extends TestCase
{
    /**
     * @return array<string, array{RbPdfPermission, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Copy' => [RbPdfPermission::Copy, 'copy'],
            'Print' => [RbPdfPermission::Print, 'print'],
            'Modify' => [RbPdfPermission::Modify, 'modify'],
            'AnnotForms' => [RbPdfPermission::AnnotForms, 'annot-forms'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(RbPdfPermission $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, RbPdfPermission::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(RbPdfPermission $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, RbPdfPermission::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        RbPdfPermission::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(RbPdfPermission::tryFrom('invalid'));
    }
}
