<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Config;

use InvalidArgumentException;

/**
 * Value object imutável com a configuração do RbPdf (mesma forma de array que {@code config/rbpdf.php}).
 *
 * O método {@see self::get()} aceita retorno e default tipados como {@code mixed} de propósito,
 * para acesso genérico por dot-notation sem perder flexibilidade de valores escalares e aninhados.
 */
final class RbPdfConfiguration
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        self::validateStructure($data);

        return new self($data);
    }

    /**
     * Acesso por dot-notation (ex.: {@code fonts.directory}). Retorna {@code $default} se algum segmento não existir.
     */
    public function get(string $dotPath, mixed $default = null): mixed
    {
        $segments = explode('.', $dotPath);
        $value = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function validateStructure(array $data): void
    {
        $checks = [
            'fonts' => 'array',
            'logos' => 'array',
            'paths' => 'array',
            'datetime' => 'array',
            'colors' => 'array',
            'margins' => 'array',
            'layout' => 'array',
        ];

        foreach ($checks as $key => $expected) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $type = get_debug_type($data[$key]);
            if ($type !== $expected) {
                throw new InvalidArgumentException(
                    "RbPdfConfiguration: key '{$key}' must be of type {$expected}, {$type} given."
                );
            }
        }

        if (isset($data['fonts']['directory']) && ! is_string($data['fonts']['directory'])) {
            throw new InvalidArgumentException(
                "RbPdfConfiguration: 'fonts.directory' must be string, ".get_debug_type($data['fonts']['directory']).' given.'
            );
        }

        foreach (['header', 'footer'] as $logoKey) {
            if (isset($data['logos'][$logoKey]) && ! is_string($data['logos'][$logoKey])) {
                throw new InvalidArgumentException(
                    "RbPdfConfiguration: 'logos.{$logoKey}' must be string."
                );
            }
        }

        if (isset($data['paths']['public']) && ! is_string($data['paths']['public'])) {
            throw new InvalidArgumentException("RbPdfConfiguration: 'paths.public' must be string.");
        }

        foreach (['locale', 'timezone'] as $dtKey) {
            if (isset($data['datetime'][$dtKey]) && ! is_string($data['datetime'][$dtKey])) {
                throw new InvalidArgumentException("RbPdfConfiguration: 'datetime.{$dtKey}' must be string.");
            }
        }
    }
}
