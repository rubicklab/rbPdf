# Encode Sans Condensed (FPDF)

Fontes usadas no relatório Contas a Receber, alinhadas ao relatório DomPDF (Google Fonts: Encode Sans Condensed).

## Arquivos

- `EncodeSansCondensed-Regular.php` + `.z` — regular
- `EncodeSansCondensed-Bold.php` + `.z` — negrito

Encoding: ISO-8859-1 (suporte a acentos em português).

## Regenerar a partir dos TTF

Se precisar regenerar os `.php` e `.z` a partir dos arquivos TTF do Google Fonts:

1. Baixe os TTF em <https://github.com/google/fonts/tree/main/ofl/encodesanscondensed> (Regular e Bold).
2. Use o makefont do FPDF (ex.: em `vendor/fawno/fpdf/fpdf/makefont/` ou copie para `/tmp`):

   ```bash
   cd /tmp && cp -r /path/to/fawno/fpdf/fpdf/makefont . && cd makefont
   php makefont.php /caminho/EncodeSansCondensed-Regular.ttf iso-8859-1
   php makefont.php /caminho/EncodeSansCondensed-Bold.ttf iso-8859-1
   ```

3. Copie os 4 arquivos gerados (`.php` e `.z` de cada) para esta pasta `fonts/`.
