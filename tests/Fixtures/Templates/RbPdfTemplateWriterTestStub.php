<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Fixtures\Templates;

use Rubick\RbPdf\Templates\RbPdfTemplateWriter;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Expõe estado protegido do template apenas para testes unitários.
 */
final class RbPdfTemplateWriterTestStub extends RbPdfTemplateWriter
{
    public function brandColorForTest(): RbPdfColor
    {
        return $this->brandColor;
    }
}
