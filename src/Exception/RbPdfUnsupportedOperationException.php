<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Exception;

/**
 * Lançada quando uma operação não é suportada pelo motor de renderização atual.
 *
 * Exemplo: método equivalente a roundedRect() indisponível na implementação interna.
 */
class RbPdfUnsupportedOperationException extends RbPdfException {}
