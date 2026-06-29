<?php

declare(strict_types=1);

namespace Rubick\RbPdf\ValueObject;

use Rubick\RbPdf\Contract\RbPdfColorInterface;
use Rubick\RbPdf\Exception\RbPdfException;

/**
 * Representa um color stop imutável para gradientes PDF.
 */
final readonly class RbPdfGradientStop
{
    /**
     * @throws RbPdfException Se a posição estiver fora do range 0.0-1.0
     */
    public function __construct(
        public float $position,
        public RbPdfColorInterface $color,
    ) {
        if ($position < 0.0 || $position > 1.0) {
            throw new RbPdfException(
                "Invalid gradient stop position: {$position}. "
                .'The value must be between 0.0 and 1.0.'
            );
        }
    }

    /**
     * @throws RbPdfException Se a posição estiver fora do range 0.0-1.0
     */
    public static function at(float $position, RbPdfColorInterface $color): self
    {
        return new self($position, $color);
    }
}
