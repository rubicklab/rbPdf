<?php

declare(strict_types=1);

namespace Rubick\RbPdf\ValueObject;

use Rubick\RbPdf\Contract\RbPdfColorInterface;
use Rubick\RbPdf\Exception\RbPdfException;

/**
 * Representa uma cor CMYK imutável para uso em operações PDF.
 *
 * Cada componente (cyan, magenta, yellow, black) deve estar no range 0-100.
 */
final class RbPdfCmykColor implements RbPdfColorInterface
{
    /**
     * @throws RbPdfException Se qualquer componente estiver fora do range 0-100
     */
    public function __construct(
        public readonly int $cyan,
        public readonly int $magenta,
        public readonly int $yellow,
        public readonly int $black,
    ) {
        self::validateComponent('cyan', $cyan);
        self::validateComponent('magenta', $magenta);
        self::validateComponent('yellow', $yellow);
        self::validateComponent('black', $black);
    }

    /**
     * @throws RbPdfException Se qualquer componente estiver fora do range 0-100
     */
    public static function cmyk(int $c, int $m, int $y, int $k): self
    {
        return new self($c, $m, $y, $k);
    }

    /**
     * @return array{int, int, int, int}
     */
    public function toArray(): array
    {
        return [$this->cyan, $this->magenta, $this->yellow, $this->black];
    }

    /**
     * @throws RbPdfException Se o valor estiver fora do range 0-100
     */
    private static function validateComponent(string $name, int $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new RbPdfException(
                "Invalid CMYK component '{$name}': {$value}. "
                .'The value must be between 0 and 100.'
            );
        }
    }
}
