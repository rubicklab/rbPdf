<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Tooling;

/**
 * Verifica que tipos declarados em PHP sob {@code src/} (fora de {@code Internal/})
 * usam o prefixo {@code RbPdf}, alinhado a RF-04 / baseline de release.
 */
final class RbPdfNamingConventionScanner
{
    private const PREFIX = 'RbPdf';

    /**
     * @return array<string> mensagens descrevendo violações (vazio = OK)
     */
    public static function scanSourceTree(string $srcRoot): array
    {
        $srcRoot = rtrim($srcRoot, '/\\');
        if (! is_dir($srcRoot)) {
            return ['Diretório src inexistente: '.$srcRoot];
        }

        $violations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $srcRoot,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
            )
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $fullPath = $file->getPathname();
            $relative = str_replace('\\', '/', substr($fullPath, strlen($srcRoot) + 1));
            if (str_starts_with($relative, 'Internal/')) {
                continue;
            }

            $content = @file_get_contents($fullPath);
            if ($content === false) {
                $violations[] = 'Não foi possível ler: '.$relative;

                continue;
            }

            foreach (self::extractTypeNames($content) as $kind => $names) {
                foreach ($names as $name) {
                    if (! str_starts_with($name, self::PREFIX)) {
                        $violations[] = sprintf(
                            '%s: %s `%s` deve iniciar com `%s`',
                            $relative,
                            $kind,
                            $name,
                            self::PREFIX
                        );
                    }
                }
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @return array<string, array<string>> chaves: class, enum, interface, trait
     */
    public static function extractTypeNames(string $php): array
    {
        $tokens = token_get_all($php);
        $count = count($tokens);

        $classes = [];
        $enums = [];
        $interfaces = [];
        $traits = [];

        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if (! is_array($t)) {
                continue;
            }

            if ($t[0] === T_CLASS) {
                if (self::isAnonymousClassContext($tokens, $i)) {
                    continue;
                }
                $name = self::nextStringToken($tokens, $count, $i + 1);
                if ($name !== null) {
                    $classes[] = $name;
                }
            } elseif ($t[0] === T_ENUM) {
                $name = self::nextStringToken($tokens, $count, $i + 1);
                if ($name !== null) {
                    $enums[] = $name;
                }
            } elseif ($t[0] === T_INTERFACE) {
                $name = self::nextStringToken($tokens, $count, $i + 1);
                if ($name !== null) {
                    $interfaces[] = $name;
                }
            } elseif ($t[0] === T_TRAIT) {
                $name = self::nextStringToken($tokens, $count, $i + 1);
                if ($name !== null) {
                    $traits[] = $name;
                }
            }
        }

        return [
            'class' => $classes,
            'enum' => $enums,
            'interface' => $interfaces,
            'trait' => $traits,
        ];
    }

    /**
     * @param  array<string|array{0: int, 1: string, 2: int}>  $tokens
     */
    private static function isAnonymousClassContext(array $tokens, int $classIndex): bool
    {
        $j = $classIndex - 1;
        while ($j >= 0) {
            $prev = $tokens[$j];
            if (is_array($prev)) {
                if ($prev[0] === T_WHITESPACE || $prev[0] === T_COMMENT || $prev[0] === T_DOC_COMMENT) {
                    $j--;

                    continue;
                }

                return $prev[0] === T_NEW;
            }

            $j--;
        }

        return false;
    }

    /**
     * @param  array<string|array{0: int, 1: string, 2: int}>  $tokens
     */
    private static function nextStringToken(array $tokens, int $count, int $start): ?string
    {
        for ($k = $start; $k < $count; $k++) {
            $t = $tokens[$k];
            if (! is_array($t)) {
                return null;
            }
            if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                continue;
            }
            if ($t[0] === T_STRING) {
                return $t[1];
            }

            return null;
        }

        return null;
    }
}
