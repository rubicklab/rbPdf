<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Fixtures\Templates;

use Rubick\RbPdf\Templates\RbPdfTemplateWriter;

/**
 * Subclasse mínima para exercitar {@see RbPdfTemplateWriter::fromConfigFile()} nos testes.
 *
 * @internal
 */
final class ConcreteRbPdfTemplateForTests extends RbPdfTemplateWriter {}
