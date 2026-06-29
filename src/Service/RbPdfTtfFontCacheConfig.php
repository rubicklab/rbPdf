<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Service;

use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\RbPdf;

/**
 * Resolve o diretório raiz de cache para conversão TTF/OTF → artefatos FPDF (font.php + font.z).
 *
 * **Concorrência e disco compartilhado**: múltiplos workers escrevendo no mesmo diretório base
 * competem pelos mesmos arquivos; o {@see RbPdfTtfFontResolver} usa lock por entrada (hash), mas
 * filesystems com latência ou NFS podem ainda expor condições adversas. Para testes paralelos
 * (Paratest) use {@see getenv('TEST_TOKEN')} ou {@see getenv('UNIQUE_TEST_TOKEN')} — este
 * configurator acrescenta um subdiretório estável ao path efetivo quando essas variáveis existem.
 * Para produção com vários processos no mesmo host, prefira {@see getenv('RBPDF_CACHE_DIR')}
 * apontando para volume local rápido ou isole cache por worker/PID conforme política de deploy.
 *
 * Ordem de precedência:
 * 1. Argumento explícito em {@see RbPdf::addTrueTypeFont()} (sem sufixo de token)
 * 2. Variável de ambiente {@see getenv('RBPDF_CACHE_DIR')} + subpasta `ttf-fonts` (+ token se houver)
 * 3. {@see getenv('RBPDF_TTF_CACHE')} (paridade com apps que já usam a chave legada) (+ token)
 * 4. {@see sys_get_temp_dir()} + `/rbpdf-cache/ttf-fonts` (+ token)
 */
final class RbPdfTtfFontCacheConfig
{
    private const TTF_SUBDIR = 'ttf-fonts';

    /**
     * @param  string|null  $explicit  Diretório absoluto ou relativo; null usa env/defaults
     *
     * @throws RbPdfException Se $explicit apontar para um arquivo (não diretório)
     */
    public static function resolve(?string $explicit): string
    {
        if ($explicit !== null && $explicit !== '') {
            $normalized = self::normalizePath($explicit);
            if (is_file($normalized)) {
                throw new RbPdfException(
                    'Font cache path must be a directory, not a file: '.$normalized
                );
            }

            return $normalized;
        }

        $base = self::resolveBaseFromEnvironment();
        $segment = self::isolationSegment();
        if ($segment !== '') {
            return self::normalizePath($base.DIRECTORY_SEPARATOR.$segment);
        }

        return self::normalizePath($base);
    }

    private static function resolveBaseFromEnvironment(): string
    {
        $cacheDir = self::readNonEmptyEnv('RBPDF_CACHE_DIR');
        if ($cacheDir !== null) {
            return self::normalizePath($cacheDir.DIRECTORY_SEPARATOR.self::TTF_SUBDIR);
        }

        $legacy = self::readNonEmptyEnv('RBPDF_TTF_CACHE');
        if ($legacy !== null) {
            return $legacy;
        }

        return self::normalizePath(
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'rbpdf-cache'.DIRECTORY_SEPARATOR.self::TTF_SUBDIR
        );
    }

    private static function isolationSegment(): string
    {
        $unique = self::readNonEmptyEnv('UNIQUE_TEST_TOKEN');
        if ($unique !== null) {
            return self::sanitizeToken($unique);
        }

        $test = self::readNonEmptyEnv('TEST_TOKEN');
        if ($test !== null) {
            return self::sanitizeToken($test);
        }

        return '';
    }

    private static function readNonEmptyEnv(string $name): ?string
    {
        $v = getenv($name);
        if (is_string($v) && $v !== '') {
            return $v;
        }

        $fromSuperglobal = $_ENV[$name] ?? null;
        if (is_string($fromSuperglobal) && $fromSuperglobal !== '') {
            return $fromSuperglobal;
        }

        return null;
    }

    private static function sanitizeToken(string $token): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $token);
        if ($sanitized === null || $sanitized === '') {
            return 'token';
        }

        return $sanitized;
    }

    private static function normalizePath(string $path): string
    {
        return rtrim($path, '/\\');
    }
}
