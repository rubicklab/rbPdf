<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Enum;

/**
 * Permissões de acesso ao documento PDF protegido.
 *
 * Cada case corresponde a uma permissão que pode ser concedida ao utilizador
 * quando o PDF é protegido com senha. A ausência de uma permissão significa
 * que a ação correspondente é restrita.
 *
 * A encriptação utilizada pelo fawno/fpdf é RC4-40 (PDF 1.3) — segurança
 * fraca pelos padrões modernos. Não apresentar como "segurança forte".
 */
enum RbPdfPermission: string
{
    /** Permite copiar texto e imagens do documento. */
    case Copy = 'copy';

    /** Permite imprimir o documento. */
    case Print = 'print';

    /** Permite modificar o conteúdo do documento. */
    case Modify = 'modify';

    /** Permite adicionar anotações e preencher formulários. */
    case AnnotForms = 'annot-forms';
}
