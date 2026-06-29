<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Internal\Trait;

use Rubick\RbPdf\Internal\RbPdfFpdfBridge;

/**
 * Transparência (opacidade) via objetos ExtGState no PDF.
 *
 * Baseado no script FPDF 74. Combinações (fillAlpha, strokeAlpha, blendMode) são
 * reutilizadas em cache interno. Requer PDF 1.4+ (ajustado em _enddoc).
 *
 * @internal Uso exclusivo em {@see RbPdfFpdfBridge}.
 */
trait RbPdfAlphaSupport
{
    /** @var array<int, array{parms: array{ca: float, CA: float, BM: string}, n?: int}> */
    private array $extGStates = [];

    public function SetAlpha(float $alpha, string $blendMode = 'Normal'): void
    {
        $gs = $this->AddExtGState([
            'ca' => $alpha,
            'CA' => $alpha,
            'BM' => '/'.$blendMode,
        ]);
        $this->SetExtGState($gs);
    }

    public function SetFillAlpha(float $alpha, string $blendMode = 'Normal'): void
    {
        $gs = $this->AddExtGState([
            'ca' => $alpha,
            'BM' => '/'.$blendMode,
        ]);
        $this->SetExtGState($gs);
    }

    public function SetStrokeAlpha(float $alpha, string $blendMode = 'Normal'): void
    {
        $gs = $this->AddExtGState([
            'ca' => null,
            'CA' => $alpha,
            'BM' => '/'.$blendMode,
        ]);
        $this->SetExtGState($gs);
    }

    /**
     * @param  array{ca?: float|null, CA?: float|null, BM: string}  $parms
     */
    private function AddExtGState(array $parms): int
    {
        $n = count($this->extGStates) + 1;
        $this->extGStates[$n] = ['parms' => $parms];

        return $n;
    }

    private function SetExtGState(int $gs): void
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    private function _putextgstates(): void
    {
        foreach ($this->extGStates as $i => $state) {
            $this->_newobj();
            $this->extGStates[$i]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $parms = $state['parms'];
            if (isset($parms['ca']) && $parms['ca'] !== null) {
                $this->_put(sprintf('/ca %.3F', $parms['ca']));
            }
            if (isset($parms['CA']) && $parms['CA'] !== null) {
                $this->_put(sprintf('/CA %.3F', $parms['CA']));
            }
            $this->_put('/BM '.$parms['BM']);
            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    protected function _putresourcedict_alpha(): void
    {
        if (! empty($this->extGStates)) {
            $this->_put('/ExtGState <<');
            foreach ($this->extGStates as $k => $extgstate) {
                $this->_put('/GS'.$k.' '.$extgstate['n'].' 0 R');
            }
            $this->_put('>>');
        }
    }

    protected function _putresources_alpha(): void
    {
        $this->_putextgstates();
    }

    protected function _enddoc_alpha(): void
    {
        if (! empty($this->extGStates) && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }
    }
}
