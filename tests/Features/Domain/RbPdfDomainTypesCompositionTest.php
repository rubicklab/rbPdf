<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Domain;

use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Garante que VOs e enums públicos compõem estruturas estáveis (ex.: estilo de célula)
 * consumíveis por componentes de layout nas tarefas seguintes.
 */
final class RbPdfDomainTypesCompositionTest extends TestCase
{
    /**
     * @return array{textAlign: string, fillRgb: array{int, int, int}}
     */
    private static function buildCellStyleSnapshot(RbPdfColor $fill, RbPdfTextAlignment $align): array
    {
        return [
            'textAlign' => $align->value,
            'fillRgb' => $fill->toArray(),
        ];
    }

    public function test_color_and_text_alignment_compose_serializable_cell_style(): void
    {
        $fill = RbPdfColor::hex('#337ab7');
        $align = RbPdfTextAlignment::Center;

        $style = self::buildCellStyleSnapshot($fill, $align);

        $this->assertSame('C', $style['textAlign']);
        $this->assertSame([51, 122, 183], $style['fillRgb']);

        $json = json_encode($style, JSON_THROW_ON_ERROR);
        $roundTrip = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($style, $roundTrip);
    }
}
