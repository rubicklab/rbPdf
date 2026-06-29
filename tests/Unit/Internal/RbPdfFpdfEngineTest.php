<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Internal;

use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfImageAlign;
use Rubick\RbPdf\Enum\RbPdfImageFit;
use Rubick\RbPdf\Enum\RbPdfImageValign;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;

final class RbPdfFpdfEngineTest extends TestCase
{
    public function test_image_fit_rejects_non_positive_area_width(): void
    {
        $engine = new RbPdfFpdfEngine;

        $this->expectException(RbPdfException::class);
        $this->expectExceptionMessage('Area width (imageFit)');

        $engine->imageFit(
            '/nonexistent.png',
            0,
            0,
            -10,
            50,
            RbPdfImageFit::Fit,
            RbPdfImageAlign::Left,
            RbPdfImageValign::Top,
        );
    }
}
