<?php

declare(strict_types=1);

namespace Rubick\RbPdf\ValueObject;

use Rubick\RbPdf\Contract\RbPdfColorInterface;
use Rubick\RbPdf\Exception\RbPdfException;

/**
 * Representa uma cor RGB imutável para uso em operações PDF.
 *
 * Cada componente (red, green, blue) deve estar no range 0-255.
 */
final class RbPdfColor implements RbPdfColorInterface
{
    /**
     * @throws RbPdfException Se qualquer componente estiver fora do range 0-255
     */
    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
    ) {
        self::validateComponent('red', $red);
        self::validateComponent('green', $green);
        self::validateComponent('blue', $blue);
    }

    /**
     * @throws RbPdfException Se qualquer componente estiver fora do range 0-255
     */
    public static function rgb(int $r, int $g, int $b): self
    {
        return new self($r, $g, $b);
    }

    /**
     * Aceita formatos '#337ab7' e '337ab7' (com ou sem #, case-insensitive).
     *
     * @throws RbPdfException Se o formato hexadecimal for inválido
     */
    public static function hex(string $hex): self
    {
        $normalized = ltrim($hex, '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $normalized) !== 1) {
            throw new RbPdfException(
                "RbPdfColor: invalid hexadecimal value '{$hex}'. Expected format: '#RRGGBB' or 'RRGGBB' (6 hexadecimal digits)."
            );
        }

        $r = (int) hexdec(substr($normalized, 0, 2));
        $g = (int) hexdec(substr($normalized, 2, 2));
        $b = (int) hexdec(substr($normalized, 4, 2));

        return new self($r, $g, $b);
    }

    public static function white(): self
    {
        return new self(255, 255, 255);
    }

    public static function black(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * @return array{int, int, int}
     */
    public function toArray(): array
    {
        return [$this->red, $this->green, $this->blue];
    }

    /**
     * @throws RbPdfException Se o valor estiver fora do range 0-255
     */
    private static function validateComponent(string $name, int $value): void
    {
        if ($value < 0 || $value > 255) {
            throw new RbPdfException(
                "RbPdfColor: {$name} value ({$value}) is out of range 0-255."
            );
        }
    }
}
