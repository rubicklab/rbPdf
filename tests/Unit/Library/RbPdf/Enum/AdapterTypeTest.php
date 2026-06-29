<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Enum;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfAdapterType;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;
use Rubick\RbPdf\RbPdf;
use ReflectionClass;

/**
 * Porte de {@see \Tests\Unit\Library\RbPdf\Enum\AdapterTypeTest} da referência `api`.
 * O enum {@see AdapterType} foi removido da API pública (motor fixo FPDF). Em vez de casos de enum,
 * validamos que {@code new RbPdf()} sempre produz documentos com engine FPDF isolado.
 */
#[CoversNothing]
final class AdapterTypeTest extends TestCase
{
    public function test_new_rb_pdf_always_backed_by_fpdf_engine(): void
    {
        $pdf = new RbPdf;

        self::assertInstanceOf(RbPdf::class, $pdf);

        $ref = new ReflectionClass($pdf);
        $prop = $ref->getProperty('engine');
        $prop->setAccessible(true);
        $engine = $prop->getValue($pdf);

        self::assertInstanceOf(RbPdfFpdfEngine::class, $engine);
    }

    public function test_no_public_adapter_type_enum_in_package(): void
    {
        self::assertFalse(
            enum_exists(RbPdfAdapterType::class),
            'AdapterType não deve existir como tipo público prefixado RbPdf.',
        );
    }
}
