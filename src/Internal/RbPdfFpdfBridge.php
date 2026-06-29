<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Internal;

use Closure;
use Fawno\FPDF\FawnoFPDF;
use Rubick\RbPdf\Internal\Trait\RbPdfAlphaSupport;
use Rubick\RbPdf\Internal\Trait\RbPdfGradientSupport;

/**
 * Ponte com {@see FawnoFPDF}: header/footer via closures, alpha, gradientes e utilitários de stream.
 *
 * FPDF não é thread-safe; cada instância de documento deve usar sua própria bridge.
 *
 * @internal Motor fixo FPDF — não faz parte da API pública estável.
 */
final class RbPdfFpdfBridge extends FawnoFPDF
{
    use RbPdfAlphaSupport;
    use RbPdfGradientSupport;

    private ?Closure $headerCallback = null;

    private ?Closure $footerCallback = null;

    public function setHeaderCallback(?Closure $callback): void
    {
        $this->headerCallback = $callback;
    }

    public function setFooterCallback(?Closure $callback): void
    {
        $this->footerCallback = $callback;
    }

    public function Header(): void
    {
        if ($this->headerCallback !== null) {
            ($this->headerCallback)();
        }
    }

    public function Footer(): void
    {
        if ($this->footerCallback !== null) {
            ($this->footerCallback)();
        }
    }

    public function rawOut(string $s): void
    {
        $this->_out($s);
    }

    public function getScaleFactor(): float
    {
        return $this->k;
    }

    public function getCurrentPageHeight(): float
    {
        return $this->h;
    }

    public function getLeftMargin(): float
    {
        return $this->lMargin;
    }

    public function getRightMargin(): float
    {
        return $this->rMargin;
    }

    public function getBottomMargin(): float
    {
        return $this->bMargin;
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();
        $this->_putresourcedict_alpha();
        $this->_putresourcedict_gradient();
    }

    protected function _putresources(): void
    {
        $this->_putresources_alpha();
        $this->_putresources_gradient();
        parent::_putresources();
    }

    protected function _enddoc(): void
    {
        $this->_enddoc_alpha();
        parent::_enddoc();
    }

    /**
     * RC4 usado pelo script PDFProtection do FPDF.
     *
     * O trait chama {@see openssl_encrypt()} com o cipher `RC4-40` sempre que a extensão
     * existe; no OpenSSL 3 (sem provider legacy) isso devolve `false` e o PDF encriptado
     * fica inválido. Neste caso usamos a implementação em PHP pura do próprio script.
     */
    protected function RC4(string $key, string $data): string
    {
        if (function_exists('openssl_encrypt')) {
            $encrypted = @openssl_encrypt($data, 'RC4-40', $key, OPENSSL_RAW_DATA);
            if ($encrypted !== false && $encrypted !== '') {
                return $encrypted;
            }
        }

        return $this->pdfProtectionRc4PurePhp($key, $data);
    }

    /**
     * Cópia do ramo RC4 em PHP do {@see \FPDF\Scripts\PDFProtection\PDFProtectionTrait}
     * (necessário quando OpenSSL não expõe RC4-40).
     */
    private function pdfProtectionRc4PurePhp(string $key, string $data): string
    {
        static $lastKey;
        static $lastState;

        if ($key !== $lastKey) {
            $k = str_repeat($key, (int) (256 / strlen($key) + 1));
            $state = range(0, 255);
            $j = 0;

            for ($i = 0; $i < 256; $i++) {
                $t = $state[$i];
                $j = ($j + $t + ord($k[$i])) % 256;
                $state[$i] = $state[$j];
                $state[$j] = $t;
            }

            $lastKey = $key;
            $lastState = $state;
        } else {
            $state = $lastState;
        }

        $len = strlen($data);
        $a = 0;
        $b = 0;
        $out = '';

        for ($i = 0; $i < $len; $i++) {
            $a = ($a + 1) % 256;
            $t = $state[$a];
            $b = ($b + $t) % 256;
            $state[$a] = $state[$b];
            $state[$b] = $t;
            $keyByte = $state[($state[$a] + $state[$b]) % 256];
            $out .= chr(ord($data[$i]) ^ $keyByte);
        }

        return $out;
    }
}
