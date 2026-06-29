<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\RbPdf;

use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;

/**
 * Gera um PDF protegido (senha de abertura 12345) em storage/app/rbpdf_password_protected.pdf.
 * O ficheiro não é apagado ao fim do teste; cada execução sobrescreve o mesmo caminho.
 *
 * Compatibilidade de leitores: a proteção do FPDF usa Standard Security Handler rev. 2 (RC4-40).
 * Microsoft Edge e Chrome (PDF.js) costumam falhar ou recusar esse formato; use Adobe Acrobat
 * Reader, Foxit ou SumatraPDF com a senha 12345. Em PHP com OpenSSL 3 sem algoritmos legacy,
 * a bridge RbPdf aplica RC4 em PHP puro para o ficheiro continuar válido.
 */
final class PasswordProtectedRandomPdfTest extends TestCase
{
    private const OUTPUT_BASENAME = 'rbpdf_password_protected.pdf';

    public function test_generates_password_protected_pdf_saved_under_storage_app(): void
    {
        $root = dirname(__DIR__, 3);
        $appDir = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app';
        if (! is_dir($appDir)) {
            mkdir($appDir, 0775, true);
        }

        $path = $appDir.DIRECTORY_SEPARATOR.self::OUTPUT_BASENAME;

        $payload = 'random-token: '.bin2hex(random_bytes(24));

        (new RbPdf)
            ->protect([], '12345', null)
            ->addPage(RbPdfPageOrientation::Portrait, RbPdfPageSize::A4)
            ->font('Helvetica', RbPdfFontStyle::Regular, 12)
            ->cell(0, 10, 'Password-protected sample (open with 12345)', 0, 1, RbPdfTextAlignment::Left, false)
            ->cell(0, 8, $payload, 0, 1, RbPdfTextAlignment::Left, false)
            ->save($path);

        self::assertFileExists($path);
        self::assertGreaterThan(0, filesize($path));

        $contents = file_get_contents($path);
        self::assertIsString($contents);
        self::assertStringStartsWith('%PDF', $contents);
        self::assertStringContainsString('/Encrypt', $contents, 'PDF should declare an encryption dictionary');
        self::assertStringContainsString('%%EOF', $contents);
    }
}
