<?php

declare(strict_types=1);

namespace Rubick\RbPdf\ValueObject;

use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Exception\RbPdfException;

/**
 * Encapsula um tamanho de página — enum predefinido ou dimensões customizadas (mm).
 *
 * Permite que addPage() aceite tanto RbPdfPageSize::A4 quanto dimensões
 * arbitrárias (largura × altura) de forma type-safe num único tipo.
 */
final readonly class RbPdfPageDimension
{
    private function __construct(
        public ?RbPdfPageSize $enum,
        public ?float $width,
        public ?float $height,
    ) {}

    /**
     * Cria dimensão a partir de um tamanho de página predefinido.
     */
    public static function fromEnum(RbPdfPageSize $size): self
    {
        return new self(enum: $size, width: null, height: null);
    }

    /**
     * Cria dimensão customizada em milímetros.
     *
     * @throws RbPdfException Se largura ou altura forem <= 0
     */
    public static function custom(float $width, float $height): self
    {
        if ($width <= 0 || $height <= 0) {
            throw new RbPdfException(
                "Invalid page dimensions ({$width}×{$height}mm). "
                .'Both width and height must be greater than zero.'
            );
        }

        return new self(enum: null, width: $width, height: $height);
    }

    /**
     * Retorna o parâmetro no formato esperado pelo FPDF.
     *
     * @return string|array{float, float} String para enum (ex: 'A4'), array [width, height] para customizado
     */
    public function toFpdfParam(): string|array
    {
        if ($this->enum !== null) {
            return $this->enum->value;
        }

        return [$this->width, $this->height];
    }
}
