<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Internal;

use Kaareln\SVGPathData\Attributes\PathData\ClosePath;
use Kaareln\SVGPathData\Attributes\PathData\Line;
use Kaareln\SVGPathData\Attributes\PathData\Move;
use Kaareln\SVGPathData\Attributes\SVGPathData;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;

/**
 * Mesmo path usado nos testes unitários da referência (RbPdfTest / svgPath).
 */
final class PathParsingTest extends TestCase
{
    public function test_reference_path_parses_to_expected_command_sequence(): void
    {
        $pathData = 'M 0 0 L 100 100 Z';
        $parsed = SVGPathData::fromString($pathData);

        $commands = self::collectForwardCommands($parsed);

        self::assertGreaterThanOrEqual(3, count($commands));
        self::assertInstanceOf(Move::class, $commands[0]);
        self::assertInstanceOf(Line::class, $commands[1]);
        self::assertInstanceOf(ClosePath::class, $commands[2]);
    }

    /**
     * Espelha a ordem usada em {@see RbPdfFpdfEngine} (reverso do iterador).
     *
     * @return array<object>
     */
    private static function collectForwardCommands(SVGPathData $parsed): array
    {
        $commands = [];
        $parsed->rewind();
        while ($parsed->valid()) {
            $commands[] = $parsed->current();
            $parsed->next();
        }

        return array_reverse($commands);
    }
}
