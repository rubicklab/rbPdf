<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Exception;

use RuntimeException;

/**
 * Exceção base para todos os erros do RbPdf.
 *
 * Mensagens seguem o padrão: "o que falhou" + "como corrigir".
 */
class RbPdfException extends RuntimeException {}
