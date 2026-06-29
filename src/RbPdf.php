<?php

declare(strict_types=1);

namespace Rubick\RbPdf;

use Closure;
use Rubick\RbPdf\Component\RbPdfListBuilder;
use Rubick\RbPdf\Contract\RbPdfColorInterface;
use Rubick\RbPdf\Enum\RbPdfDisplayLayout;
use Rubick\RbPdf\Enum\RbPdfDisplayZoom;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfImageAlign;
use Rubick\RbPdf\Enum\RbPdfImageFit;
use Rubick\RbPdf\Enum\RbPdfImageValign;
use Rubick\RbPdf\Enum\RbPdfLineCap;
use Rubick\RbPdf\Enum\RbPdfLineJoin;
use Rubick\RbPdf\Enum\RbPdfOutputMode;
use Rubick\RbPdf\Enum\RbPdfPageOrientation;
use Rubick\RbPdf\Enum\RbPdfPageSize;
use Rubick\RbPdf\Enum\RbPdfPermission;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\Enum\RbPdfTextDirection;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\Internal\RbPdfFpdfEngine;
use Rubick\RbPdf\Service\RbPdfTtfFontCacheConfig;
use Rubick\RbPdf\Service\RbPdfTtfFontResolver;
use Rubick\RbPdf\ValueObject\RbPdfPageDimension;

/**
 * API fluente de alto nível para construção de PDFs.
 *
 * Delega ao motor FPDF interno ({@see RbPdfFpdfEngine}). Cada instância possui
 * engine isolado — use {@code new RbPdf()} por requisição ou job.
 * Métodos de construção retornam $this para encadeamento; getters retornam valores.
 */
final class RbPdf
{
    private RbPdfPageOrientation $defaultOrientation = RbPdfPageOrientation::Portrait;

    private RbPdfPageSize $defaultSize = RbPdfPageSize::A4;

    private ?RbPdfPageDimension $customDimension = null;

    private readonly RbPdfFpdfEngine $engine;

    private ?RbPdfTtfFontResolver $ttfFontResolver = null;

    /**
     * @param  RbPdfFpdfEngine|null  $engine  Motor FPDF; null cria instância nova (isolada).
     */
    public function __construct()
    {
        $this->engine = new RbPdfFpdfEngine;
    }

    private function ttfFontResolver(): RbPdfTtfFontResolver
    {
        return $this->ttfFontResolver ??= new RbPdfTtfFontResolver;
    }

    // --- Configuração de página ---

    /**
     * Define a orientação padrão como Landscape.
     *
     * @return $this
     */
    public function landscape(): self
    {
        $this->defaultOrientation = RbPdfPageOrientation::Landscape;

        return $this;
    }

    /**
     * Define a orientação padrão como Portrait.
     *
     * @return $this
     */
    public function portrait(): self
    {
        $this->defaultOrientation = RbPdfPageOrientation::Portrait;

        return $this;
    }

    /**
     * Define o tamanho de página padrão.
     *
     * @param  RbPdfPageSize  $size  Tamanho da página
     * @return $this
     */
    public function pageSize(RbPdfPageSize $size): self
    {
        $this->defaultSize = $size;
        $this->customDimension = null;

        return $this;
    }

    /**
     * Define um tamanho de página customizado em milímetros.
     *
     * Substitui o tamanho definido por pageSize(). Aplicado na próxima chamada a addPage().
     *
     * @param  float  $width  Largura em milímetros (deve ser > 0)
     * @param  float  $height  Altura em milímetros (deve ser > 0)
     * @return $this
     */
    public function customRbPdfPageSize(float $width, float $height): self
    {
        $this->customDimension = RbPdfPageDimension::custom($width, $height);

        return $this;
    }

    /**
     * Define as margens do documento.
     *
     * Quando $bottom é informado, configura automaticamente a quebra de página
     * com a margem inferior especificada via setAutoPageBreak(true, $bottom).
     *
     * @param  float  $left  Margem esquerda em milímetros
     * @param  float  $top  Margem superior em milímetros
     * @param  float  $right  Margem direita em milímetros
     * @param  float|null  $bottom  Margem inferior em milímetros (null = sem alterar)
     * @return $this
     */
    public function margins(float $left, float $top, float $right, ?float $bottom = null): self
    {
        $this->engine->setMargins($left, $top, $right);

        if ($bottom !== null) {
            $this->engine->setAutoPageBreak(true, $bottom);
        }

        return $this;
    }

    /**
     * Define a margem esquerda do documento.
     *
     * @param  float  $margin  Margem em milímetros
     * @return $this
     */
    public function leftMargin(float $margin): self
    {
        $this->engine->setLeftMargin($margin);

        return $this;
    }

    /**
     * Define a margem superior do documento.
     *
     * @param  float  $margin  Margem em milímetros
     * @return $this
     */
    public function topMargin(float $margin): self
    {
        $this->engine->setTopMargin($margin);

        return $this;
    }

    /**
     * Define a margem direita do documento.
     *
     * @param  float  $margin  Margem em milímetros
     * @return $this
     */
    public function rightMargin(float $margin): self
    {
        $this->engine->setRightMargin($margin);

        return $this;
    }

    /**
     * Configura a quebra automática de página.
     *
     * @param  bool  $enabled  Se a quebra automática está ativada
     * @param  float  $margin  Margem inferior que dispara a quebra (em mm)
     * @return $this
     */
    public function autoPageBreak(bool $enabled = true, float $margin = 14): self
    {
        $this->engine->setAutoPageBreak($enabled, $margin);

        return $this;
    }

    /**
     * Define um alias para o número total de páginas.
     *
     * @param  string  $alias  Alias a ser substituído pelo total de páginas
     * @return $this
     */
    public function aliasNbPages(string $alias = '{nb}'): self
    {
        $this->engine->aliasNbPages($alias);

        return $this;
    }

    // --- Metadados do documento ---

    /**
     * Define o título do documento PDF.
     *
     * @param  string  $title  Título do documento (UTF-8)
     * @return $this
     */
    public function title(string $title): self
    {
        $this->engine->setTitle($title);

        return $this;
    }

    /**
     * Define o autor do documento PDF.
     *
     * @param  string  $author  Nome do autor (UTF-8)
     * @return $this
     */
    public function author(string $author): self
    {
        $this->engine->setAuthor($author);

        return $this;
    }

    /**
     * Define o assunto do documento PDF.
     *
     * @param  string  $subject  Assunto do documento (UTF-8)
     * @return $this
     */
    public function subject(string $subject): self
    {
        $this->engine->setSubject($subject);

        return $this;
    }

    /**
     * Define as palavras-chave do documento PDF.
     *
     * @param  string  $keywords  Palavras-chave separadas por espaço (UTF-8)
     * @return $this
     */
    public function keywords(string $keywords): self
    {
        $this->engine->setKeywords($keywords);

        return $this;
    }

    /**
     * Define o criador do documento PDF.
     *
     * @param  string  $creator  Nome do criador/aplicação (UTF-8)
     * @return $this
     */
    public function creator(string $creator): self
    {
        $this->engine->setCreator($creator);

        return $this;
    }

    // --- Modo de exibição ---

    /**
     * Define o modo de exibição do viewer PDF ao abrir o documento.
     *
     * @param  RbPdfDisplayZoom  $zoom  Nível de zoom ao abrir
     * @param  RbPdfDisplayLayout  $layout  Layout de exibição das páginas
     * @return $this
     */
    public function displayMode(RbPdfDisplayZoom $zoom, RbPdfDisplayLayout $layout = RbPdfDisplayLayout::Default): self
    {
        $this->engine->setDisplayMode($zoom, $layout);

        return $this;
    }

    // --- Páginas ---

    /**
     * Adiciona uma nova página ao documento.
     *
     * Usa orientação e tamanho padrão se não especificados.
     * Se customRbPdfPageSize() foi chamado e nenhum $size explícito é fornecido,
     * usa as dimensões customizadas.
     *
     * @param  RbPdfPageOrientation|null  $orientation  Orientação da página (null = padrão)
     * @param  RbPdfPageSize|null  $size  Tamanho da página (null = padrão ou customizado)
     * @return $this
     */
    public function addPage(
        ?RbPdfPageOrientation $orientation = null,
        ?RbPdfPageSize $size = null,
    ): self {
        $resolvedOrientation = $orientation ?? $this->defaultOrientation;

        if ($size !== null) {
            $this->engine->addPage($resolvedOrientation, $size);
        } elseif ($this->customDimension !== null) {
            $this->engine->addPageWithDimension($resolvedOrientation, $this->customDimension);
        } else {
            $this->engine->addPage($resolvedOrientation, $this->defaultSize);
        }

        return $this;
    }

    // --- Fontes ---

    /**
     * Define a fonte ativa para o texto subsequente.
     *
     * @param  string  $family  Nome da família de fontes (ex: 'Arial', 'Helvetica')
     * @param  RbPdfFontStyle  $style  Estilo da fonte
     * @param  float  $size  Tamanho em pontos (0 = mantém o tamanho atual)
     * @return $this
     */
    public function font(
        string $family,
        RbPdfFontStyle $style = RbPdfFontStyle::Regular,
        float $size = 0,
    ): self {
        $this->engine->setFont($family, $style, $size);

        return $this;
    }

    /**
     * Registra uma fonte customizada no documento.
     *
     * @param  string  $family  Nome da família
     * @param  RbPdfFontStyle  $style  Estilo da fonte
     * @param  string  $file  Nome do arquivo de definição da fonte
     * @param  string  $dir  Diretório contendo o arquivo (vazio = diretório padrão)
     * @return $this
     */
    public function addFont(
        string $family,
        RbPdfFontStyle $style = RbPdfFontStyle::Regular,
        string $file = '',
        string $dir = '',
    ): self {
        $this->engine->addFont($family, $style, $file, $dir);

        return $this;
    }

    /**
     * Registra uma fonte a partir de um arquivo .ttf ou .otf.
     *
     * Converte via makefont (fawno/fpdf) para font.php + font.z em cache em disco; veja
     * {@see RbPdfTtfFontCacheConfig} e {@see RbPdfTtfFontResolver} para variáveis de ambiente
     * e riscos de concorrência em diretório compartilhado.
     *
     * @param  string|null  $cacheDirectory  Diretório de cache explícito; null usa env/defaults
     * @return $this
     */
    public function addTrueTypeFont(
        string $family,
        RbPdfFontStyle $style,
        string $ttfPath,
        ?string $cacheDirectory = null,
    ): self {
        $cacheRoot = RbPdfTtfFontCacheConfig::resolve($cacheDirectory);
        $resolved = $this->ttfFontResolver()->ensureFpdfFontFiles($ttfPath, $cacheRoot);
        $this->engine->addFont($family, $style, $resolved->definitionFileName, $resolved->cacheDirectory);

        return $this;
    }

    /**
     * Retorna a largura de uma string na fonte atual.
     *
     * @param  string  $text  Texto UTF-8
     * @return float Largura em milímetros
     */
    public function getStringWidth(string $text): float
    {
        return $this->engine->getStringWidth($text);
    }

    /**
     * Calcula a altura que um texto ocupará com quebra de linha automática.
     *
     * Simula o algoritmo de quebra do MultiCell sem renderizar.
     * Considera quebras de linha automáticas e manuais (\n).
     *
     * @param  string  $text  Texto UTF-8
     * @param  float  $width  Largura disponível em milímetros
     * @param  float  $lineHeight  Altura de cada linha em milímetros
     * @return float Altura total em milímetros
     */
    public function getStringHeight(string $text, float $width, float $lineHeight): float
    {
        return $this->engine->getStringHeight($text, $width, $lineHeight);
    }

    // --- Texto ---

    /**
     * Imprime uma célula de texto.
     *
     * @param  float  $width  Largura da célula (0 = até margem direita)
     * @param  float  $height  Altura da célula
     * @param  string  $text  Texto UTF-8
     * @param  int  $border  Borda (0 = sem, 1 = com)
     * @param  int  $ln  Posição após a célula (0 = à direita, 1 = próxima linha, 2 = abaixo)
     * @param  RbPdfTextAlignment  $align  Alinhamento do texto
     * @param  bool  $fill  Se deve preencher o fundo da célula
     * @param  string|int  $link  URL externa ou ID de link interno ('' = sem link)
     * @return $this
     */
    public function cell(
        float $width = 0,
        float $height = 0,
        string $text = '',
        int $border = 0,
        int $ln = 0,
        RbPdfTextAlignment $align = RbPdfTextAlignment::Left,
        bool $fill = false,
        string|int $link = '',
    ): self {
        $this->engine->cell($width, $height, $text, $border, $ln, $align, $fill, $link);

        return $this;
    }

    /**
     * Imprime texto com quebra de linha automática.
     *
     * @param  float  $width  Largura da célula (0 = largura disponível)
     * @param  float  $height  Altura de cada linha
     * @param  string  $text  Texto UTF-8
     * @param  int  $border  Borda (0 = sem, 1 = com)
     * @param  RbPdfTextAlignment  $align  Alinhamento do texto
     * @param  bool  $fill  Se deve preencher o fundo
     * @return $this
     */
    public function multiCell(
        float $width = 0,
        float $height = 0,
        string $text = '',
        int $border = 0,
        RbPdfTextAlignment $align = RbPdfTextAlignment::Left,
        bool $fill = false,
    ): self {
        $this->engine->multiCell($width, $height, $text, $border, $align, $fill);

        return $this;
    }

    /**
     * Realiza uma quebra de linha.
     *
     * @param  float  $height  Altura da quebra (0 = altura da última célula)
     * @return $this
     */
    public function newLine(float $height = 0): self
    {
        $this->engine->ln($height);

        return $this;
    }

    // --- Posicionamento ---

    /**
     * Define as posições X e Y do cursor.
     *
     * @param  float  $x  Posição X em milímetros
     * @param  float  $y  Posição Y em milímetros
     * @return $this
     */
    public function setXY(float $x, float $y): self
    {
        $this->engine->setXY($x, $y);

        return $this;
    }

    /**
     * Define a posição X do cursor, mantendo Y inalterado.
     *
     * @param  float  $x  Posição X em milímetros
     * @return $this
     */
    public function setX(float $x): self
    {
        $this->engine->setXY($x, $this->engine->getY());

        return $this;
    }

    /**
     * Define a posição Y do cursor, mantendo X inalterado.
     *
     * @param  float  $y  Posição Y em milímetros
     * @return $this
     */
    public function setY(float $y): self
    {
        $this->engine->setXY($this->engine->getX(), $y);

        return $this;
    }

    /**
     * Retorna a posição X atual do cursor.
     *
     * @return float Posição X em milímetros
     */
    public function getX(): float
    {
        return $this->engine->getX();
    }

    /**
     * Retorna a posição Y atual do cursor.
     *
     * @return float Posição Y em milímetros
     */
    public function getY(): float
    {
        return $this->engine->getY();
    }

    /**
     * Retorna a largura da página atual.
     *
     * @return float Largura em milímetros
     */
    public function getPageWidth(): float
    {
        return $this->engine->getPageWidth();
    }

    /**
     * Retorna a altura da página atual.
     *
     * @return float Altura em milímetros
     */
    public function getPageHeight(): float
    {
        return $this->engine->getPageHeight();
    }

    /**
     * Retorna o número da página atual.
     *
     * @return int Número da página
     */
    public function pageNo(): int
    {
        return $this->engine->pageNo();
    }

    // --- Formas ---

    /**
     * Desenha uma linha entre dois pontos.
     *
     * @param  float  $x1  Coordenada X do ponto inicial
     * @param  float  $y1  Coordenada Y do ponto inicial
     * @param  float  $x2  Coordenada X do ponto final
     * @param  float  $y2  Coordenada Y do ponto final
     * @return $this
     */
    public function line(float $x1, float $y1, float $x2, float $y2): self
    {
        $this->engine->line($x1, $y1, $x2, $y2);

        return $this;
    }

    /**
     * Desenha um retângulo.
     *
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura
     * @param  float  $h  Altura
     * @param  string  $style  Estilo: '' = borda, 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     */
    public function rect(float $x, float $y, float $w, float $h, string $style = ''): self
    {
        $this->engine->rect($x, $y, $w, $h, $style);

        return $this;
    }

    /**
     * Desenha um retângulo com cantos arredondados.
     *
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura
     * @param  float  $h  Altura
     * @param  float  $radius  Raio dos cantos arredondados
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF' = ambos
     * @return $this
     */
    public function roundedRect(
        float $x,
        float $y,
        float $w,
        float $h,
        float $radius,
        string $style = '',
    ): self {
        $this->engine->roundedRect($x, $y, $w, $h, $radius, $style);

        return $this;
    }

    /**
     * Desenha um círculo.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $r  Raio
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     */
    public function circle(float $x, float $y, float $r, string $style = 'D'): self
    {
        $this->engine->circle($x, $y, $r, $style);

        return $this;
    }

    /**
     * Desenha uma elipse.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $rx  Raio horizontal
     * @param  float  $ry  Raio vertical
     * @param  float  $angle  Ângulo de rotação em graus
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     */
    public function ellipse(float $x, float $y, float $rx, float $ry, float $angle = 0, string $style = 'D'): self
    {
        $this->engine->ellipse($x, $y, $rx, $ry, $angle, $style);

        return $this;
    }

    /**
     * Desenha um polígono arbitrário.
     *
     * @param  array<array{float, float}>  $points  Array de pontos [x, y] (mínimo 3 pontos)
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     *
     * @throws RbPdfException Se o array tiver menos de 3 pontos
     */
    public function polygon(array $points, string $style = 'D'): self
    {
        if (count($points) < 3) {
            throw new RbPdfException(
                'polygon() requires at least 3 points, '.count($points).' given. '
                .'Provide an array with at least 3 [x, y] pairs.'
            );
        }

        $this->engine->polygon($points, $style);

        return $this;
    }

    /**
     * Desenha um polígono regular.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $r  Raio
     * @param  int  $sides  Número de lados (mínimo 3)
     * @param  float  $angle  Ângulo de rotação em graus
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     *
     * @throws RbPdfException Se $sides for menor que 3
     */
    public function regularPolygon(
        float $x,
        float $y,
        float $r,
        int $sides,
        float $angle = 0,
        string $style = 'D',
    ): self {
        if ($sides < 3) {
            throw new RbPdfException(
                "regularPolygon() requires at least 3 sides, {$sides} given. "
                .'Pass a value >= 3 for the $sides parameter.'
            );
        }

        $this->engine->regularPolygon($x, $y, $r, $sides, $angle, $style);

        return $this;
    }

    /**
     * Desenha uma curva Bézier cúbica.
     *
     * @param  float  $x0  X do ponto inicial
     * @param  float  $y0  Y do ponto inicial
     * @param  float  $x1  X do primeiro ponto de controle
     * @param  float  $y1  Y do primeiro ponto de controle
     * @param  float  $x2  X do segundo ponto de controle
     * @param  float  $y2  Y do segundo ponto de controle
     * @param  float  $x3  X do ponto final
     * @param  float  $y3  Y do ponto final
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     */
    public function curve(
        float $x0,
        float $y0,
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $x3,
        float $y3,
        string $style = 'D',
    ): self {
        $this->engine->curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $style);

        return $this;
    }

    /**
     * Desenha um polígono em forma de estrela.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $r  Raio externo
     * @param  int  $nv  Número de vértices
     * @param  int  $ng  Número do gap (define a forma da estrela)
     * @param  float  $angle  Ângulo de rotação em graus
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     */
    public function starPolygon(
        float $x,
        float $y,
        float $r,
        int $nv,
        int $ng,
        float $angle = 0,
        string $style = 'D',
    ): self {
        $this->engine->starPolygon($x, $y, $r, $nv, $ng, $angle, $style);

        return $this;
    }

    /**
     * Define a largura da linha para operações de desenho.
     *
     * @param  float  $width  Largura da linha em milímetros
     * @return $this
     */
    public function lineWidth(float $width): self
    {
        $this->engine->setLineWidth($width);

        return $this;
    }

    /**
     * Define o estilo de ponta de linha (line cap).
     *
     * @param  RbPdfLineCap  $cap  Estilo da ponta
     * @return $this
     */
    public function lineCap(RbPdfLineCap $cap): self
    {
        $this->engine->setLineCap($cap);

        return $this;
    }

    /**
     * Define o estilo de junção de linha (line join).
     *
     * @param  RbPdfLineJoin  $join  Estilo da junção
     * @return $this
     */
    public function lineJoin(RbPdfLineJoin $join): self
    {
        $this->engine->setLineJoin($join);

        return $this;
    }

    /**
     * Define o padrão tracejado da linha.
     *
     * @param  float  $length  Comprimento do traço
     * @param  float  $space  Espaço entre traços (0 = igual ao comprimento)
     * @param  float  $phase  Deslocamento inicial do padrão
     * @return $this
     */
    public function dash(float $length, float $space = 0, float $phase = 0): self
    {
        $this->engine->setDash($length, $space, $phase);

        return $this;
    }

    /**
     * Remove o padrão tracejado, voltando a linha sólida.
     *
     * @return $this
     */
    public function undash(): self
    {
        $this->engine->removeDash();

        return $this;
    }

    // --- Estado gráfico ---

    /**
     * Salva o estado gráfico atual (cores, transformações, estilos de linha).
     *
     * Cada pushState() deve ter um popState() correspondente.
     * Transformações devem ser usadas entre pushState()/popState() para
     * não afetar o restante do documento permanentemente.
     *
     * @return $this
     */
    public function pushState(): self
    {
        $this->engine->save();

        return $this;
    }

    /**
     * Restaura o estado gráfico salvo anteriormente por pushState().
     *
     * @return $this
     *
     * @throws RbPdfException Se não houver pushState() pendente
     */
    public function popState(): self
    {
        $this->engine->restore();

        return $this;
    }

    // --- Transformações 2D ---

    /**
     * Aplica uma translação ao sistema de coordenadas.
     *
     * @param  float  $x  Deslocamento horizontal em mm
     * @param  float  $y  Deslocamento vertical em mm
     * @return $this
     */
    public function translate(float $x, float $y): self
    {
        $this->engine->translate($x, $y);

        return $this;
    }

    /**
     * Aplica uma rotação ao sistema de coordenadas.
     *
     * @param  float  $angle  Ângulo de rotação em graus (sentido anti-horário)
     * @param  float|null  $x  Coordenada X do centro de rotação (null = posição atual)
     * @param  float|null  $y  Coordenada Y do centro de rotação (null = posição atual)
     * @return $this
     */
    public function rotate(float $angle, ?float $x = null, ?float $y = null): self
    {
        $this->engine->rotate($angle, $x, $y);

        return $this;
    }

    /**
     * Aplica escala diferenciada nos eixos X e Y.
     *
     * @param  float  $sx  Fator de escala horizontal (percentual, 100 = sem alteração)
     * @param  float  $sy  Fator de escala vertical (percentual, 100 = sem alteração)
     * @param  float|null  $x  Coordenada X do centro de escala (null = posição atual)
     * @param  float|null  $y  Coordenada Y do centro de escala (null = posição atual)
     * @return $this
     */
    public function scale(float $sx, float $sy, ?float $x = null, ?float $y = null): self
    {
        $this->engine->scale($sx, $sy, $x, $y);

        return $this;
    }

    /**
     * Aplica escala uniforme nos eixos X e Y.
     *
     * @param  float  $s  Fator de escala (percentual, 100 = sem alteração)
     * @param  float|null  $x  Coordenada X do centro de escala (null = posição atual)
     * @param  float|null  $y  Coordenada Y do centro de escala (null = posição atual)
     * @return $this
     */
    public function scaleXY(float $s, ?float $x = null, ?float $y = null): self
    {
        $this->engine->scaleXY($s, $x, $y);

        return $this;
    }

    /**
     * Aplica distorção (skew) ao sistema de coordenadas.
     *
     * @param  float  $ax  Ângulo de distorção horizontal em graus
     * @param  float  $ay  Ângulo de distorção vertical em graus
     * @param  float|null  $x  Coordenada X do centro de distorção (null = posição atual)
     * @param  float|null  $y  Coordenada Y do centro de distorção (null = posição atual)
     * @return $this
     */
    public function skew(float $ax, float $ay, ?float $x = null, ?float $y = null): self
    {
        $this->engine->skew($ax, $ay, $x, $y);

        return $this;
    }

    /**
     * Aplica espelhamento horizontal.
     *
     * @param  float|null  $x  Coordenada X do eixo de espelhamento (null = posição atual)
     * @return $this
     */
    public function mirrorH(?float $x = null): self
    {
        $this->engine->mirrorH($x);

        return $this;
    }

    /**
     * Aplica espelhamento vertical.
     *
     * @param  float|null  $y  Coordenada Y do eixo de espelhamento (null = posição atual)
     * @return $this
     */
    public function mirrorV(?float $y = null): self
    {
        $this->engine->mirrorV($y);

        return $this;
    }

    // --- Cores ---

    /**
     * Define a cor de desenho (linhas, bordas).
     *
     * @param  RbPdfColorInterface  $color  Cor (RGB, CMYK, etc.)
     * @return $this
     */
    public function drawColor(RbPdfColorInterface $color): self
    {
        $this->engine->setDrawColor($color);

        return $this;
    }

    /**
     * Define a cor de preenchimento (fundos).
     *
     * @param  RbPdfColorInterface  $color  Cor (RGB, CMYK, etc.)
     * @return $this
     */
    public function fillColor(RbPdfColorInterface $color): self
    {
        $this->engine->setFillColor($color);

        return $this;
    }

    /**
     * Define a cor do texto.
     *
     * @param  RbPdfColorInterface  $color  Cor (RGB, CMYK, etc.)
     * @return $this
     */
    public function textColor(RbPdfColorInterface $color): self
    {
        $this->engine->setTextColor($color);

        return $this;
    }

    // --- Imagem ---

    /**
     * Insere uma imagem no documento.
     *
     * @param  string  $file  Caminho do arquivo de imagem
     * @param  float  $x  Posição X (0 = posição atual)
     * @param  float  $y  Posição Y (0 = posição atual)
     * @param  float  $w  Largura (0 = automática)
     * @param  float  $h  Altura (0 = automática)
     * @param  string  $type  Tipo da imagem (vazio = detectar automaticamente)
     * @param  string|int  $link  URL externa ou ID de link interno ('' = sem link)
     * @return $this
     */
    public function image(
        string $file,
        float $x = 0,
        float $y = 0,
        float $w = 0,
        float $h = 0,
        string $type = '',
        string|int $link = '',
    ): self {
        $this->engine->image($file, $x, $y, $w, $h, $type, $link);

        return $this;
    }

    /**
     * Insere uma imagem com ajuste proporcional dentro de uma área retangular.
     *
     * - Fit: a imagem é redimensionada proporcionalmente para caber inteiramente dentro da área
     * - Cover: a imagem é redimensionada proporcionalmente para cobrir toda a área (pode ultrapassar)
     * - None: a imagem é renderizada com dimensões originais
     *
     * @param  string  $file  Caminho do arquivo de imagem
     * @param  float  $x  Coordenada X da área
     * @param  float  $y  Coordenada Y da área
     * @param  float  $areaW  Largura da área em milímetros
     * @param  float  $areaH  Altura da área em milímetros
     * @param  RbPdfImageFit  $fit  Modo de ajuste
     * @param  RbPdfImageAlign  $align  Alinhamento horizontal dentro da área
     * @param  RbPdfImageValign  $valign  Alinhamento vertical dentro da área
     * @return $this
     */
    public function imageFit(
        string $file,
        float $x,
        float $y,
        float $areaW,
        float $areaH,
        RbPdfImageFit $fit = RbPdfImageFit::Fit,
        RbPdfImageAlign $align = RbPdfImageAlign::Center,
        RbPdfImageValign $valign = RbPdfImageValign::Center,
    ): self {
        $this->engine->imageFit($file, $x, $y, $areaW, $areaH, $fit, $align, $valign);

        return $this;
    }

    /**
     * Insere uma imagem com escala proporcional uniforme.
     *
     * @param  string  $file  Caminho do arquivo de imagem
     * @param  float  $x  Coordenada X
     * @param  float  $y  Coordenada Y
     * @param  float  $scale  Fator de escala (1.0 = 100%, 0.5 = 50%)
     * @return $this
     */
    public function imageScaled(string $file, float $x, float $y, float $scale): self
    {
        $this->engine->imageScaled($file, $x, $y, $scale);

        return $this;
    }

    // --- Links ---

    /**
     * Cria um link interno e retorna seu identificador.
     *
     * O link pode ser associado a um destino com setLink() e referenciado
     * em cell(), image() ou write() passando o ID retornado.
     *
     * @return int Identificador do link criado
     */
    public function addLink(): int
    {
        return $this->engine->addLink();
    }

    /**
     * Define o destino de um link interno.
     *
     * @param  int  $linkId  Identificador retornado por addLink()
     * @param  float  $y  Posição Y do destino na página (0 = topo)
     * @param  int  $page  Número da página destino (-1 = página atual)
     * @return $this
     */
    public function setLink(int $linkId, float $y = 0, int $page = -1): self
    {
        $this->engine->setLink($linkId, $y, $page);

        return $this;
    }

    /**
     * Cria uma região clicável retangular no documento.
     *
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura da região
     * @param  float  $h  Altura da região
     * @param  string|int  $link  URL externa ou ID de link interno
     * @return $this
     */
    public function link(float $x, float $y, float $w, float $h, string|int $link): self
    {
        $this->engine->link($x, $y, $w, $h, $link);

        return $this;
    }

    /**
     * Imprime texto na posição atual com quebra automática e suporte a links.
     *
     * Diferente de cell() e multiCell(), write() continua na mesma linha
     * até precisar quebrar, permitindo intercalar texto normal com texto linkado.
     *
     * @param  float  $height  Altura da linha
     * @param  string  $text  Texto UTF-8
     * @param  string|int  $link  URL externa ou ID de link interno ('' = sem link)
     * @return $this
     */
    public function write(float $height, string $text, string|int $link = ''): self
    {
        $this->engine->write($height, $text, $link);

        return $this;
    }

    // --- Texto e imagem rotacionados ---

    /**
     * Imprime texto rotacionado ao redor de sua origem.
     *
     * @param  float  $x  Coordenada X da origem
     * @param  float  $y  Coordenada Y da origem
     * @param  string  $text  Texto UTF-8
     * @param  float  $angle  Ângulo de rotação em graus
     * @return $this
     */
    public function rotatedText(float $x, float $y, string $text, float $angle): self
    {
        $this->engine->rotatedText($x, $y, $text, $angle);

        return $this;
    }

    /**
     * Insere uma imagem rotacionada ao redor de seu canto superior esquerdo.
     *
     * @param  string  $file  Caminho do arquivo de imagem
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura da imagem
     * @param  float  $h  Altura da imagem
     * @param  float  $angle  Ângulo de rotação em graus
     * @return $this
     */
    public function rotatedImage(string $file, float $x, float $y, float $w, float $h, float $angle): self
    {
        $this->engine->rotatedImage($file, $x, $y, $w, $h, $angle);

        return $this;
    }

    /**
     * Imprime texto em uma das 4 direções ortogonais.
     *
     * @param  float  $x  Coordenada X da origem
     * @param  float  $y  Coordenada Y da origem
     * @param  string  $text  Texto UTF-8
     * @param  RbPdfTextDirection  $direction  Direção do texto
     * @return $this
     */
    public function textWithDirection(float $x, float $y, string $text, RbPdfTextDirection $direction): self
    {
        $this->engine->textWithDirection($x, $y, $text, $direction);

        return $this;
    }

    // --- Proteção ---

    /**
     * Protege o documento PDF com senhas e controle de permissões.
     *
     * A encriptação utilizada é RC4-40 (PDF 1.3) — segurança fraca pelos padrões modernos.
     * Deve ser chamado antes de addPage().
     *
     * @param  array<RbPdfPermission>  $permissions  Permissões concedidas ao usuário
     * @param  string|null  $userPassword  Senha do usuário (solicita ao abrir)
     * @param  string|null  $ownerPassword  Senha do proprietário (modo privilegiado)
     * @return $this
     *
     * @throws RbPdfException Se nenhuma senha for informada
     */
    public function protect(
        array $permissions = [],
        ?string $userPassword = null,
        ?string $ownerPassword = null,
    ): self {
        if ($userPassword === null && $ownerPassword === null) {
            throw new RbPdfException(
                'protect() requires at least one password (userPassword or ownerPassword). '
                .'Set userPassword to require a password to open, or ownerPassword to restrict permissions.'
            );
        }

        $permissionValues = array_map(
            static fn (RbPdfPermission $p): string => $p->value,
            $permissions,
        );

        $this->engine->setProtection($permissionValues, $userPassword, $ownerPassword);

        return $this;
    }

    // --- Bookmarks ---

    /**
     * Adiciona um bookmark (outline/índice de navegação) ao documento PDF.
     *
     * @param  string  $title  Título do bookmark (UTF-8)
     * @param  int  $level  Nível hierárquico (0 = topo, 1+ = filhos)
     * @param  float|null  $y  Posição Y do destino (null = posição atual)
     * @return $this
     */
    public function bookmark(string $title, int $level = 0, ?float $y = null): self
    {
        $this->engine->bookmark($title, $level, $y);

        return $this;
    }

    // --- Imagens em memória ---

    /**
     * Insere uma imagem a partir de dados binários em memória.
     *
     * Aceita dados PNG, JPEG ou GIF em formato binário (raw) ou base64.
     *
     * @param  string  $data  Dados binários da imagem
     * @param  float  $x  Posição X
     * @param  float  $y  Posição Y
     * @param  float  $w  Largura (0 = automática)
     * @param  float  $h  Altura (0 = automática)
     * @return $this
     */
    public function imageFromString(string $data, float $x, float $y, float $w = 0, float $h = 0): self
    {
        $this->engine->imageFromString($data, $x, $y, $w, $h);

        return $this;
    }

    /**
     * Insere uma imagem a partir de um recurso GD.
     *
     * Requer a extensão ext-gd. O recurso GD é convertido internamente para PNG.
     *
     * @param  mixed  $gdResource  Recurso GD (GdImage)
     * @param  float  $x  Posição X
     * @param  float  $y  Posição Y
     * @param  float  $w  Largura (0 = automática)
     * @param  float  $h  Altura (0 = automática)
     * @return $this
     */
    public function imageFromGd(\GdImage $gdResource, float $x, float $y, float $w = 0, float $h = 0): self
    {
        $this->engine->imageFromGd($gdResource, $x, $y, $w, $h);

        return $this;
    }

    // --- Conversões de unidade ---

    /**
     * Converte milímetros para pontos tipográficos.
     *
     * @param  float  $mm  Valor em milímetros
     * @return float Valor em pontos (1 in = 25.4 mm = 72 pt)
     */
    public function mmToPoints(float $mm): float
    {
        return $mm * 72.0 / 25.4;
    }

    /**
     * Converte pontos tipográficos para milímetros.
     *
     * @param  float  $pt  Valor em pontos
     * @return float Valor em milímetros
     */
    public function pointsToMm(float $pt): float
    {
        return $pt * 25.4 / 72.0;
    }

    /**
     * Converte centímetros para pontos tipográficos.
     *
     * @param  float  $cm  Valor em centímetros
     * @return float Valor em pontos (1 in = 2.54 cm = 72 pt)
     */
    public function cmToPoints(float $cm): float
    {
        return $cm * 72.0 / 2.54;
    }

    /**
     * Converte polegadas para pontos tipográficos.
     *
     * @param  float  $inches  Valor em polegadas
     * @return float Valor em pontos (1 in = 72 pt)
     */
    public function inchesToPoints(float $inches): float
    {
        return $inches * 72.0;
    }

    // --- Listas ---

    /**
     * Inicia a construção de uma lista com bullets (encadeie {@see RbPdfListBuilder::items()} e {@see RbPdfListBuilder::end()}).
     */
    public function bulletList(): RbPdfListBuilder
    {
        return new RbPdfListBuilder($this);
    }

    // --- Barcodes e QR Codes ---

    /**
     * Gera um código de barras EAN-13.
     *
     * @param  float  $x  Coordenada X
     * @param  float  $y  Coordenada Y
     * @param  string  $barcode  Código EAN-13 (12 ou 13 dígitos)
     * @param  float  $h  Altura do código de barras
     * @param  float  $w  Largura das barras individuais
     * @return $this
     */
    public function barcodeEAN13(float $x, float $y, string $barcode, float $h = 16, float $w = 0.35): self
    {
        $this->engine->barcodeEAN13($x, $y, $barcode, $h, $w);

        return $this;
    }

    /**
     * Gera um código de barras Code 128.
     *
     * @param  float  $x  Coordenada X
     * @param  float  $y  Coordenada Y
     * @param  string  $code  Dados a codificar
     * @param  float  $w  Largura das barras individuais
     * @param  float  $h  Altura do código de barras
     * @return $this
     */
    public function barcodeCode128(float $x, float $y, string $code, float $w, float $h): self
    {
        $this->engine->barcodeCode128($x, $y, $code, $w, $h);

        return $this;
    }

    /**
     * Gera um QR Code.
     *
     * @param  float  $x  Coordenada X
     * @param  float  $y  Coordenada Y
     * @param  string  $data  Dados a codificar no QR Code
     * @param  float  $size  Tamanho do QR Code
     * @return $this
     */
    public function qrCode(float $x, float $y, string $data, float $size): self
    {
        $this->engine->qrCode($x, $y, $data, $size);

        return $this;
    }

    // --- Anexos ---

    /**
     * Anexa um arquivo ao documento PDF.
     *
     * @param  string  $filePath  Caminho do arquivo a anexar
     * @param  string  $name  Nome de exibição do anexo (vazio = nome do arquivo)
     * @param  string  $description  Descrição do anexo
     * @return $this
     */
    public function attach(string $filePath, string $name = '', string $description = ''): self
    {
        $this->engine->attach($filePath, $name, $description);

        return $this;
    }

    /**
     * Configura o viewer para abrir o painel de anexos ao abrir o PDF.
     *
     * @return $this
     */
    public function openAttachmentPane(): self
    {
        $this->engine->openAttachmentPane();

        return $this;
    }

    // --- Assinatura digital ---

    /**
     * Insere um campo placeholder para assinatura digital.
     *
     * Apenas placeholder visual — não realiza assinatura digital real com certificado.
     *
     * @param  float  $x  Coordenada X
     * @param  float  $y  Coordenada Y
     * @param  float  $w  Largura do campo
     * @param  float  $h  Altura do campo
     * @param  string  $name  Nome identificador do campo
     * @return $this
     */
    public function signatureField(float $x, float $y, float $w, float $h, string $name = ''): self
    {
        $this->engine->signatureField($x, $y, $w, $h, $name);

        return $this;
    }

    // --- Texto circular ---

    /**
     * Renderiza texto ao longo de um arco circular.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $radius  Raio do arco
     * @param  string  $text  Texto a renderizar (UTF-8)
     * @param  string  $align  Posição do texto no arco ('top' ou 'bottom')
     * @return $this
     */
    public function circularText(float $x, float $y, float $radius, string $text, string $align = 'top'): self
    {
        $this->engine->circularText($x, $y, $radius, $text, $align);

        return $this;
    }

    // --- Opacidade ---

    /**
     * Define a opacidade geral (preenchimento e traço).
     *
     * @param  float  $value  Opacidade (0.0 = totalmente transparente, 1.0 = totalmente opaco)
     * @return $this
     *
     * @throws RbPdfException Se o valor estiver fora do range 0.0-1.0
     */
    public function opacity(float $value): self
    {
        $this->validateOpacity($value);
        $this->engine->setAlpha($value);

        return $this;
    }

    /**
     * Define a opacidade de preenchimento (fundos, formas preenchidas).
     *
     * @param  float  $value  Opacidade de preenchimento (0.0 = transparente, 1.0 = opaco)
     * @return $this
     *
     * @throws RbPdfException Se o valor estiver fora do range 0.0-1.0
     */
    public function fillOpacity(float $value): self
    {
        $this->validateOpacity($value);
        $this->engine->setFillAlpha($value);

        return $this;
    }

    /**
     * Define a opacidade de traço (linhas, bordas).
     *
     * @param  float  $value  Opacidade de traço (0.0 = transparente, 1.0 = opaco)
     * @return $this
     *
     * @throws RbPdfException Se o valor estiver fora do range 0.0-1.0
     */
    public function strokeOpacity(float $value): self
    {
        $this->validateOpacity($value);
        $this->engine->setStrokeAlpha($value);

        return $this;
    }

    // --- Clipping paths ---

    /**
     * Define um caminho de recorte retangular.
     *
     * Conteúdo desenhado após esta chamada será visível apenas dentro do retângulo.
     * Deve ser usado entre pushState()/popState() para limitar o escopo do clipping.
     *
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura do retângulo
     * @param  float  $h  Altura do retângulo
     * @return $this
     */
    public function clipRect(float $x, float $y, float $w, float $h): self
    {
        $this->engine->clipRect($x, $y, $w, $h);

        return $this;
    }

    /**
     * Define um caminho de recorte circular.
     *
     * Conteúdo desenhado após esta chamada será visível apenas dentro do círculo.
     * Deve ser usado entre pushState()/popState() para limitar o escopo do clipping.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $radius  Raio do círculo
     * @return $this
     */
    public function clipCircle(float $x, float $y, float $radius): self
    {
        $this->engine->clipCircle($x, $y, $radius);

        return $this;
    }

    /**
     * Define um caminho de recorte elíptico.
     *
     * Conteúdo desenhado após esta chamada será visível apenas dentro da elipse.
     * Deve ser usado entre pushState()/popState() para limitar o escopo do clipping.
     *
     * @param  float  $x  Coordenada X do centro
     * @param  float  $y  Coordenada Y do centro
     * @param  float  $rx  Raio horizontal
     * @param  float  $ry  Raio vertical
     * @return $this
     */
    public function clipEllipse(float $x, float $y, float $rx, float $ry): self
    {
        $this->engine->clipEllipse($x, $y, $rx, $ry);

        return $this;
    }

    // --- Gradientes ---

    /**
     * Preenche uma área retangular com um gradiente linear.
     *
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura da área
     * @param  float  $h  Altura da área
     * @param  RbPdfGradientStop[]  $stops  Array de RbPdfGradientStop (mínimo 2)
     * @param  float[]  $coords  Vetor do gradiente [x1, y1, x2, y2] (0.0-1.0)
     * @return $this
     */
    public function linearGradient(
        float $x,
        float $y,
        float $w,
        float $h,
        array $stops,
        array $coords = [0, 0, 1, 0],
    ): self {
        $this->engine->linearGradient($x, $y, $w, $h, $stops, $coords);

        return $this;
    }

    /**
     * Preenche uma área retangular com um gradiente radial.
     *
     * @param  float  $x  Coordenada X do canto superior esquerdo
     * @param  float  $y  Coordenada Y do canto superior esquerdo
     * @param  float  $w  Largura da área
     * @param  float  $h  Altura da área
     * @param  RbPdfGradientStop[]  $stops  Array de RbPdfGradientStop (mínimo 2)
     * @param  float[]  $coords  [fx, fy, cx, cy, r] (0.0-1.0)
     * @return $this
     */
    public function radialGradient(
        float $x,
        float $y,
        float $w,
        float $h,
        array $stops,
        array $coords = [0.5, 0.5, 0.5, 0.5, 1],
    ): self {
        $this->engine->radialGradient($x, $y, $w, $h, $stops, $coords);

        return $this;
    }

    // --- SVG path ---

    /**
     * Renderiza um caminho SVG no documento PDF.
     *
     * @param  string  $pathData  String de path data SVG (ex: 'M 0,0 L 100,0 L 100,100 Z')
     * @param  string  $style  Estilo: 'D' = borda, 'F' = preenchido, 'DF'/'FD' = ambos
     * @return $this
     *
     * @throws RbPdfException Se o path for inválido
     */
    public function svgPath(string $pathData, string $style = 'D'): self
    {
        $this->engine->svgPath($pathData, $style);

        return $this;
    }

    // --- Colunas de texto ---

    /**
     * Distribui texto em múltiplas colunas com fluxo automático.
     *
     * @param  string  $text  Texto UTF-8
     * @param  int  $columns  Número de colunas (mínimo 2)
     * @param  float  $gap  Espaço entre colunas em milímetros
     * @param  float  $w  Largura total disponível (0 = largura da página menos margens)
     * @param  float  $h  Altura máxima (0 = até o final da página)
     * @return $this
     *
     * @throws RbPdfException Se $columns for menor que 2
     */
    public function textColumns(
        string $text,
        int $columns = 2,
        float $gap = 5.0,
        float $w = 0,
        float $h = 0,
    ): self {
        $this->engine->textColumns($text, $columns, $gap, $w, $h);

        return $this;
    }

    /**
     * Validates that an opacity value is within the valid range [0.0, 1.0].
     *
     * @throws RbPdfException If the value is out of range
     */
    private function validateOpacity(float $value): void
    {
        if ($value < 0.0 || $value > 1.0) {
            throw new RbPdfException(
                "Invalid opacity: {$value}. The value must be between 0.0 and 1.0."
            );
        }
    }

    // --- Header/Footer ---

    /**
     * Define o callback invocado no header de cada página.
     *
     * A closure recebe o RbPdf como parâmetro, permitindo
     * uso da API fluent dentro do callback.
     *
     * @param  Closure  $callback  Closure executada no header — recebe RbPdf como argumento
     * @return $this
     */
    public function onHeader(Closure $callback): self
    {
        $builder = $this;
        $this->engine->setHeaderCallback(function () use ($callback, $builder): void {
            $callback($builder);
        });

        return $this;
    }

    /**
     * Define o callback invocado no footer de cada página.
     *
     * A closure recebe o RbPdf como parâmetro, permitindo
     * uso da API fluent dentro do callback.
     *
     * @param  Closure  $callback  Closure executada no footer — recebe RbPdf como argumento
     * @return $this
     */
    public function onFooter(Closure $callback): self
    {
        $builder = $this;
        $this->engine->setFooterCallback(function () use ($callback, $builder): void {
            $callback($builder);
        });

        return $this;
    }

    // --- Saída ---

    /**
     * Gera o documento PDF.
     *
     * @param  string  $name  Nome do arquivo
     * @param  RbPdfOutputMode  $mode  Modo de saída
     * @return string Conteúdo do PDF (para RbPdfOutputMode::String) ou string vazia
     */
    public function output(string $name = '', RbPdfOutputMode $mode = RbPdfOutputMode::String): string
    {
        return $this->engine->output($name, $mode);
    }

    /**
     * Salva o PDF em arquivo no disco.
     *
     * @param  string  $path  Caminho completo do arquivo de destino
     */
    public function save(string $path): void
    {
        $this->engine->output($path, RbPdfOutputMode::File);
    }

    /**
     * Retorna o conteúdo do PDF como string.
     *
     * @return string Conteúdo binário do PDF
     */
    public function toString(): string
    {
        return $this->engine->output('', RbPdfOutputMode::String);
    }

    /**
     * Gera o PDF para exibição inline no browser.
     *
     * @param  string  $name  Nome do arquivo exibido no browser
     * @return string Conteúdo do PDF
     */
    public function inline(string $name = 'document.pdf'): string
    {
        return $this->engine->output($name, RbPdfOutputMode::Inline);
    }

    /**
     * Gera o PDF para download pelo browser.
     *
     * @param  string  $name  Nome do arquivo para download
     * @return string Conteúdo do PDF
     */
    public function download(string $name = 'document.pdf'): string
    {
        return $this->engine->output($name, RbPdfOutputMode::Download);
    }
}
