<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Internal\Trait;

use Rubick\RbPdf\Contract\RbPdfColorInterface;
use Rubick\RbPdf\Internal\RbPdfFpdfBridge;
use Rubick\RbPdf\ValueObject\RbPdfCmykColor;
use Rubick\RbPdf\ValueObject\RbPdfGradientStop;

/**
 * Gradientes linear e radial via Shading no PDF (script FPDF 72, multi-stop).
 *
 * @internal Uso exclusivo em {@see RbPdfFpdfBridge}.
 */
trait RbPdfGradientSupport
{
    /** @var array<int, array<string, mixed>> */
    private array $gradients = [];

    /**
     * @param  RbPdfGradientStop[]  $stops
     * @param  float[]  $coords
     */
    public function LinearGradient(float $x, float $y, float $w, float $h, array $stops, array $coords = [0, 0, 1, 0]): void
    {
        $this->GradientClip($x, $y, $w, $h);
        $this->PaintGradient(2, $stops, $coords);
    }

    /**
     * @param  RbPdfGradientStop[]  $stops
     * @param  float[]  $coords
     */
    public function RadialGradient(float $x, float $y, float $w, float $h, array $stops, array $coords = [0.5, 0.5, 0.5, 0.5, 1]): void
    {
        $this->GradientClip($x, $y, $w, $h);
        $this->PaintGradient(3, $stops, $coords);
    }

    private function GradientClip(float $x, float $y, float $w, float $h): void
    {
        $s = 'q';
        $s .= sprintf(' %.2F %.2F %.2F %.2F re W n', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k);
        $s .= sprintf(' %.3F 0 0 %.3F %.3F %.3F cm', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k);
        $this->_out($s);
    }

    /**
     * @param  RbPdfGradientStop[]  $stops
     * @param  float[]  $coords
     */
    private function PaintGradient(int $type, array $stops, array $coords): void
    {
        usort($stops, static fn (RbPdfGradientStop $a, RbPdfGradientStop $b): int => $a->position <=> $b->position);

        $n = count($this->gradients) + 1;
        $this->gradients[$n] = [
            'type' => $type,
            'stops' => $stops,
            'coords' => $coords,
            'colorspace' => $this->resolveColorSpace($stops),
        ];

        $this->_out('/Sh'.$n.' sh');
        $this->_out('Q');
    }

    /**
     * @param  RbPdfGradientStop[]  $stops
     */
    private function resolveColorSpace(array $stops): string
    {
        foreach ($stops as $stop) {
            if ($stop->color instanceof RbPdfCmykColor) {
                return 'DeviceCMYK';
            }
        }

        return 'DeviceRGB';
    }

    private function formatColorComponents(RbPdfColorInterface $color, string $colorSpace): string
    {
        $components = $color->toArray();
        if ($colorSpace === 'DeviceRGB') {
            if (count($components) === 4) {
                $c = $components[0] / 100;
                $m = $components[1] / 100;
                $y = $components[2] / 100;
                $k = $components[3] / 100;
                $r = 1 - min(1, $c + $k);
                $g = 1 - min(1, $m + $k);
                $b = 1 - min(1, $y + $k);

                return sprintf('%.3F %.3F %.3F', $r, $g, $b);
            }

            return sprintf('%.3F %.3F %.3F', $components[0] / 255, $components[1] / 255, $components[2] / 255);
        }

        if (count($components) === 3) {
            $r = $components[0] / 255;
            $g = $components[1] / 255;
            $b = $components[2] / 255;
            $k = 1 - max($r, $g, $b);
            if ($k >= 1.0) {
                return '0.000 0.000 0.000 1.000';
            }
            $c = (1 - $r - $k) / (1 - $k);
            $m = (1 - $g - $k) / (1 - $k);
            $y = (1 - $b - $k) / (1 - $k);

            return sprintf('%.3F %.3F %.3F %.3F', $c, $m, $y, $k);
        }

        return sprintf('%.3F %.3F %.3F %.3F', $components[0] / 100, $components[1] / 100, $components[2] / 100, $components[3] / 100);
    }

    private function _putshaders(): void
    {
        foreach ($this->gradients as $id => $grad) {
            $stops = $grad['stops'];
            $colorSpace = $grad['colorspace'];
            $functionRef = $this->writeGradientFunctions($stops, $colorSpace);

            $this->_newobj();
            $this->_put('<<');
            $this->_put('/ShadingType '.$grad['type']);
            $this->_put('/ColorSpace /'.$colorSpace);

            if ($grad['type'] === 2) {
                $this->_put(sprintf(
                    '/Coords [%.3F %.3F %.3F %.3F]',
                    $grad['coords'][0],
                    $grad['coords'][1],
                    $grad['coords'][2],
                    $grad['coords'][3],
                ));
            } elseif ($grad['type'] === 3) {
                $this->_put(sprintf(
                    '/Coords [%.3F %.3F 0 %.3F %.3F %.3F]',
                    $grad['coords'][0],
                    $grad['coords'][1],
                    $grad['coords'][2],
                    $grad['coords'][3],
                    $grad['coords'][4],
                ));
            }

            $this->_put('/Function '.$functionRef.' 0 R');
            $this->_put('/Extend [true true]');
            $this->_put('>>');
            $this->_put('endobj');
            $this->gradients[$id]['id'] = $this->n;
        }
    }

    /**
     * @param  RbPdfGradientStop[]  $stops
     */
    private function writeGradientFunctions(array $stops, string $colorSpace): int
    {
        if (count($stops) === 2) {
            $this->_newobj();
            $this->_put('<<');
            $this->_put('/FunctionType 2');
            $this->_put('/Domain [0.0 1.0]');
            $this->_put('/C0 ['.$this->formatColorComponents($stops[0]->color, $colorSpace).']');
            $this->_put('/C1 ['.$this->formatColorComponents($stops[1]->color, $colorSpace).']');
            $this->_put('/N 1');
            $this->_put('>>');
            $this->_put('endobj');

            return $this->n;
        }

        $subFunctionRefs = [];
        for ($i = 0; $i < count($stops) - 1; $i++) {
            $this->_newobj();
            $this->_put('<<');
            $this->_put('/FunctionType 2');
            $this->_put('/Domain [0.0 1.0]');
            $this->_put('/C0 ['.$this->formatColorComponents($stops[$i]->color, $colorSpace).']');
            $this->_put('/C1 ['.$this->formatColorComponents($stops[$i + 1]->color, $colorSpace).']');
            $this->_put('/N 1');
            $this->_put('>>');
            $this->_put('endobj');
            $subFunctionRefs[] = $this->n;
        }

        $bounds = [];
        $encode = [];
        for ($i = 1; $i < count($stops) - 1; $i++) {
            $bounds[] = sprintf('%.3F', $stops[$i]->position);
        }
        for ($i = 0; $i < count($stops) - 1; $i++) {
            $encode[] = '0 1';
        }

        $this->_newobj();
        $this->_put('<<');
        $this->_put('/FunctionType 3');
        $this->_put('/Domain [0.0 1.0]');
        $this->_put('/Functions ['.implode(' ', array_map(static fn (int $ref): string => $ref.' 0 R', $subFunctionRefs)).']');
        $this->_put('/Bounds ['.implode(' ', $bounds).']');
        $this->_put('/Encode ['.implode(' ', $encode).']');
        $this->_put('>>');
        $this->_put('endobj');

        return $this->n;
    }

    protected function _putresourcedict_gradient(): void
    {
        if (! empty($this->gradients)) {
            $this->_put('/Shading <<');
            foreach ($this->gradients as $id => $grad) {
                $this->_put('/Sh'.$id.' '.$grad['id'].' 0 R');
            }
            $this->_put('>>');
        }
    }

    protected function _putresources_gradient(): void
    {
        $this->_putshaders();
    }
}
