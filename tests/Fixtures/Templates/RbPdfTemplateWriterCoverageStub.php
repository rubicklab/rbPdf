<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Fixtures\Templates;

use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\Templates\RbPdfTemplateWriter;

/**
 * Chama helpers protegidos do template para elevar cobertura de testes.
 */
final class RbPdfTemplateWriterCoverageStub extends RbPdfTemplateWriter
{
    public function resolveLocalForTest(string $relative): ?string
    {
        return $this->resolveLocalAssetPath($relative);
    }

    public function runDrawingSamples(RbPdf $pdf): void
    {
        $pdf->setXY(20, 40);

        $this->drawCard($pdf, 20, 40, 'T', 'V');
        $this->drawHorizontalCards($pdf, 20, 60, [
            ['title' => 'A', 'value' => '1'],
            ['title' => 'B', 'value' => '2'],
        ]);

        $pdf->setY(80);
        $this->drawColumnHeaders($pdf, ['Col'], [40], [RbPdfTextAlignment::Center]);

        $pdf->setY(90);
        $this->drawSummaryBand(
            $pdf,
            $this->groupRowBackground,
            $this->columnTitleColor,
            'Resumo',
            25,
            ['x'],
            [15],
            60,
        );

        $pdf->setXY(20, 100);
        $this->drawBadge($pdf, 20, 6, 'OK');

        $pdf->setY(110);
        $this->drawFilterLine($pdf, 'F: ', 'valor');

        $pdf->setY(120);
        $this->drawRowSeparator($pdf, 80);

        $this->wouldOverflow($pdf, 1);
        $this->wouldOverflow($pdf, 500);
    }
}
