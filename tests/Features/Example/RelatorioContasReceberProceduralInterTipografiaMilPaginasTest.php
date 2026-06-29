<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Example;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Mesmo layout do relatório procedural com tipografia Inter (cabeçalho, filtros, cards,
 * tabela com badge e status). Preenche até 1000 páginas com linhas sintéticas.
 *
 * O PDF permanece em storage/app/ para inspeção manual (sem unlink no término).
 */
#[CoversClass(RbPdf::class)]
#[Group('slow')]
final class RelatorioContasReceberProceduralInterTipografiaMilPaginasTest extends TestCase
{
    private const PAGINAS_ALVO = 1000;

    public function test_gera_relatorio_procedural_inter_tipografia_mil_paginas(): void
    {
        $resource = [
            'start' => '01/03/26',
            'end' => '31/03/26',
            'company_plant' => 'Diversos',
            'pending' => 'R$ 12.500,00',
            'late' => 'R$ 8.250,00',
            'received' => 'R$ 45.140,00',
            'value' => 'R$ 65.890,00',
            'total_value' => 'R$ 65.540,00',
            'discounts_fees' => 'R$ 150,00',
            'fees_fines' => 'R$ 120,00',
            'discounts' => 'R$ 320,00',
            'grouping_type_title' => 'Cliente',
            'grouping_type' => '0',
            'user_name' => 'Leonam Lima',
            'data_cabecalho' => '21 MAR 2026',
        ];

        $raizProjeto = dirname(__DIR__, 3);
        $caminhoFontes = $raizProjeto.'/fonts';
        $caminhoLogoHeader = $raizProjeto.'/public/img/logo-rubick-white.png';
        $caminhoLogoRodape = $raizProjeto.'/public/img/logo-rubick-footer.png';
        self::assertFileExists($caminhoLogoHeader);
        self::assertFileExists($caminhoLogoRodape);

        $diretorioOutputPdf = $raizProjeto.'/storage/app';
        if (! is_dir($diretorioOutputPdf)) {
            mkdir($diretorioOutputPdf, 0775, true);
        }

        $caminhoSaidaPdf = $diretorioOutputPdf.'/relatorio_contas_receber_procedural_inter_tipografia_1000p.pdf';

        $fonteCorpo = 'Inter';
        $fonteTitulo = 'Inter';
        $tamFonteFiltros = 8.5;
        $tamFonteCorpo = 6.5;
        $margemHorizontal = 5.0;
        $margemTopoConteudo = 28.0;
        $altBarraHeader = 22.0;
        $margemInferiorQuebraPagina = 14.0;

        $largurasColunas = [31, 38, 39, 25, 27, 14, 22, 16, 16, 16, 25, 18];
        $larguraTabela = (float) array_sum($largurasColunas);
        $qColunasBlocoTitulo = 6;
        $larguraBlocoTitulo = (float) array_sum(array_slice($largurasColunas, 0, $qColunasBlocoTitulo));
        $largurasSubtotaisEStatus = array_slice($largurasColunas, $qColunasBlocoTitulo);

        $rotulosColunasTabela = [
            'DOCUMENTO', 'VENDEDOR', 'MÉTODO',
            'VENCIMENTO', 'RECEBIMENTO', 'PARCELA', 'VALOR BRUTO',
            'TAXAS', 'DESCONTO', 'JUROS/MULTA', 'VALOR LIQUIDO', 'STATUS',
        ];

        $corMarca = RbPdfColor::rgb(51, 122, 183);
        $corBadgeParcela = RbPdfColor::rgb(73, 126, 168);
        $corTituloColunas = RbPdfColor::rgb(50, 50, 50);
        $corFundoLinhaGrupo = RbPdfColor::rgb(213, 213, 213);
        $corBordaCard = RbPdfColor::rgb(178, 178, 178);
        $corTracoRodape = RbPdfColor::rgb(133, 132, 132);
        $corSubtituloRodape = RbPdfColor::rgb(100, 100, 100);
        $corStatusPendente = RbPdfColor::rgb(242, 181, 50);
        $corStatusAtrasado = RbPdfColor::rgb(169, 68, 66);
        $corStatusRecebido = RbPdfColor::rgb(60, 118, 61);

        $raioCaixaPequena = 1.2;
        $raioCard = 2.0;

        $textoMaiusculo = static fn (string $s): string => mb_strtoupper($s, 'UTF-8');

        $alinhamentoTituloColuna = static function (int $indice): RbPdfTextAlignment {
            if ($indice <= 4) {
                return RbPdfTextAlignment::Left;
            }
            if ($indice === 5) {
                return RbPdfTextAlignment::Center;
            }
            if ($indice >= 6 && $indice <= 10) {
                return RbPdfTextAlignment::Right;
            }

            return RbPdfTextAlignment::Center;
        };

        $textoBadgeParcela = static function (string $parcela): string {
            $partes = explode('/', $parcela);

            return implode('/', array_map(
                static fn (string $p): string => str_pad($p, 2, '0', STR_PAD_LEFT),
                $partes,
            ));
        };

        $corPorStatus = static function (string $status) use ($corStatusPendente, $corStatusAtrasado, $corStatusRecebido): RbPdfColor {
            return match (mb_strtolower($status)) {
                'atrasado' => $corStatusAtrasado,
                'pendente' => $corStatusPendente,
                'recebido' => $corStatusRecebido,
                default => RbPdfColor::black(),
            };
        };

        $pdf = new RbPdf;

        $pdf->addFont($fonteCorpo, RbPdfFontStyle::Regular, 'Inter-Regular.php', $caminhoFontes)
            ->addFont($fonteCorpo, RbPdfFontStyle::Bold, 'Inter-Bold.php', $caminhoFontes);

        $dataCabecalho = $resource['data_cabecalho'];
        $nomeUsuario = $resource['user_name'];

        $pdf->onHeader(function (RbPdf $b) use (
            $fonteCorpo,
            $fonteTitulo,
            $margemHorizontal,
            $altBarraHeader,
            $dataCabecalho,
            $nomeUsuario,
            $caminhoLogoHeader,
            $corMarca,
        ): void {
            $larguraPagina = $b->getPageWidth();

            $b->setY(0)
                ->fillColor($corMarca)
                ->rect(0, 0, $larguraPagina, $altBarraHeader, 'F')
                ->textColor(RbPdfColor::white());

            $b->image($caminhoLogoHeader, $margemHorizontal + 1, 5, 0, 10);

            $b->font($fonteTitulo, RbPdfFontStyle::Bold, 14)
                ->setXY(22, 4)
                ->cell(0, 7, 'CONTAS A RECEBER', 0, 0, RbPdfTextAlignment::Left, false);

            $b->font($fonteCorpo, RbPdfFontStyle::Regular, 8)
                ->setXY(22, 12)
                ->cell(0, 5, mb_strtoupper($dataCabecalho, 'UTF-8'), 0, 0, RbPdfTextAlignment::Left, false);

            $b->font($fonteCorpo, RbPdfFontStyle::Regular, 8)
                ->setXY(0, 8)
                ->cell(
                    $larguraPagina - $margemHorizontal,
                    6,
                    mb_strtoupper($nomeUsuario, 'UTF-8'),
                    0,
                    0,
                    RbPdfTextAlignment::Right,
                    false,
                );

            $b->textColor(RbPdfColor::black())
                ->setY($altBarraHeader + 2);
        });

        $pdf->onFooter(function (RbPdf $b) use (
            $fonteCorpo,
            $margemHorizontal,
            $caminhoLogoRodape,
            $corTracoRodape,
            $corSubtituloRodape,
        ): void {
            $larguraPagina = $b->getPageWidth();

            $b->setY(-14);
            $yTraco = $b->getY();

            $b->drawColor($corTracoRodape)
                ->lineWidth(0.3)
                ->line($margemHorizontal, $yTraco, $larguraPagina - $margemHorizontal, $yTraco);

            $b->setX($margemHorizontal)
                ->setY($yTraco + 2)
                ->font($fonteCorpo, RbPdfFontStyle::Regular, 7)
                ->textColor($corSubtituloRodape)
                ->cell(0, 4, 'Pagina '.$b->pageNo().'/{nb}', 0, 0, RbPdfTextAlignment::Left, false);

            $b->image($caminhoLogoRodape, $larguraPagina - $margemHorizontal - 20, $yTraco + 1.5, 20);

            $b->textColor(RbPdfColor::black());
        });

        $pdf->landscape()
            ->margins($margemHorizontal, $margemTopoConteudo, $margemHorizontal)
            ->autoPageBreak(true, $margemInferiorQuebraPagina)
            ->aliasNbPages()
            ->addPage()
            ->font($fonteCorpo, RbPdfFontStyle::Regular, $tamFonteCorpo);

        $larguraPagina = $pdf->getPageWidth();
        $larguraCard = 38.0;
        $alturaCard = 13.0;
        $espacoEntreCards = 4.0;
        $larguraFaixaCards = 3 * $larguraCard + 2 * $espacoEntreCards;
        $xInicioCards = $larguraPagina - $margemHorizontal - $larguraFaixaCards;
        $yTopoBlocoSuperior = $pdf->getY();

        $larguraMaxTextoFiltro = $xInicioCards - $margemHorizontal - 5;

        $linhasFiltro = [
            ['rotulo' => 'PERÍODO: ', 'texto' => $textoMaiusculo($resource['start'].' A '.$resource['end'])],
            ['rotulo' => 'CENTRAL: ', 'texto' => $textoMaiusculo($resource['company_plant'])],
            ['rotulo' => 'AGRUPAMENTO: ', 'texto' => $textoMaiusculo($resource['grouping_type_title'])],
        ];

        foreach ($linhasFiltro as $linha) {
            $pdf->setX($margemHorizontal);
            $pdf->font($fonteCorpo, RbPdfFontStyle::Bold, $tamFonteFiltros);
            $larguraRotulo = $pdf->getStringWidth($linha['rotulo']);
            $pdf->cell($larguraRotulo, 5, $linha['rotulo'], 0, 0, RbPdfTextAlignment::Left, false);
            $pdf->font($fonteCorpo, RbPdfFontStyle::Regular, $tamFonteFiltros);
            $pdf->cell($larguraMaxTextoFiltro - $larguraRotulo, 5, $linha['texto'], 0, 2, RbPdfTextAlignment::Left, false);
            $pdf->setX($margemHorizontal);
        }

        $cardsResumo = [
            ['titulo' => 'PENDENTE', 'valor' => $resource['pending'], 'corValor' => $corStatusPendente],
            ['titulo' => 'ATRASADO', 'valor' => $resource['late'], 'corValor' => $corStatusAtrasado],
            ['titulo' => 'RECEBIDO', 'valor' => $resource['received'], 'corValor' => $corStatusRecebido],
        ];

        foreach ($cardsResumo as $i => $card) {
            $xCard = $xInicioCards + $i * ($larguraCard + $espacoEntreCards);

            $pdf->drawColor($corBordaCard)
                ->lineWidth(0.4)
                ->fillColor(RbPdfColor::white())
                ->roundedRect($xCard, $yTopoBlocoSuperior, $larguraCard, $alturaCard, $raioCard, 'DF');

            $pdf->font($fonteCorpo, RbPdfFontStyle::Bold, 7)
                ->textColor(RbPdfColor::black())
                ->setXY($xCard, $yTopoBlocoSuperior + 1.5)
                ->cell($larguraCard, 4, $card['titulo'], 0, 0, RbPdfTextAlignment::Center, false);

            $pdf->textColor($card['corValor'])
                ->font($fonteCorpo, RbPdfFontStyle::Bold, 10)
                ->setXY($xCard, $yTopoBlocoSuperior + 6)
                ->cell($larguraCard, 6, $textoMaiusculo($card['valor']), 0, 0, RbPdfTextAlignment::Center, false);

            $pdf->textColor(RbPdfColor::black());
        }

        $pdf->setXY($margemHorizontal, $yTopoBlocoSuperior + $alturaCard + 2);
        $pdf->newLine(2);

        $desenharCabecalhoTabela = function () use (
            $pdf,
            $fonteCorpo,
            $tamFonteCorpo,
            $largurasColunas,
            $rotulosColunasTabela,
            $margemHorizontal,
            $corTituloColunas,
            $alinhamentoTituloColuna,
        ): void {
            $pdf->setX($margemHorizontal)
                ->font($fonteCorpo, RbPdfFontStyle::Bold, $tamFonteCorpo)
                ->textColor($corTituloColunas);

            foreach ($rotulosColunasTabela as $i => $rotulo) {
                $pdf->cell(
                    (float) $largurasColunas[$i],
                    5.5,
                    $rotulo,
                    0,
                    0,
                    $alinhamentoTituloColuna($i),
                    false,
                );
            }

            $pdf->newLine(5.5)
                ->setX($margemHorizontal)
                ->textColor(RbPdfColor::black());
        };

        $alturaNaoCabeNaPagina = function (float $alturaNecessaria) use ($pdf, $margemInferiorQuebraPagina): bool {
            return ($pdf->getY() + $alturaNecessaria) > ($pdf->getPageHeight() - $margemInferiorQuebraPagina);
        };

        $novaPaginaComCabecalho = function () use ($pdf, $desenharCabecalhoTabela): void {
            $pdf->addPage();
            $desenharCabecalhoTabela();
        };

        $desenharCabecalhoTabela();

        $alturaLinhaResumo = 5.5;
        $alturaLinhaParcela = 4.5;

        $preencherFaixaArredondada = function (
            RbPdfColor $corFundo,
            float $x,
            float $y,
            float $largura,
            float $altura,
        ) use ($pdf, $raioCaixaPequena): void {
            $pdf->fillColor($corFundo)
                ->roundedRect($x, $y, $largura, $altura, $raioCaixaPequena, 'F');
        };

        $desenharLinhaResumoComColunas = function (
            RbPdfColor $corFundo,
            RbPdfColor $corTexto,
            string $textoEsquerda,
            array $cincoValoresMonetarios,
        ) use (
            $pdf,
            $margemHorizontal,
            $larguraTabela,
            $larguraBlocoTitulo,
            $largurasSubtotaisEStatus,
            $fonteCorpo,
            $tamFonteCorpo,
            $alturaLinhaResumo,
            $textoMaiusculo,
            $preencherFaixaArredondada,
        ): void {
            $pdf->setX($margemHorizontal);
            $x = $pdf->getX();
            $y = $pdf->getY();

            $preencherFaixaArredondada($corFundo, $x, $y, $larguraTabela, $alturaLinhaResumo);

            $pdf->setXY($x, $y)
                ->textColor($corTexto)
                ->font($fonteCorpo, RbPdfFontStyle::Bold, $tamFonteCorpo)
                ->cell($larguraBlocoTitulo, $alturaLinhaResumo, $textoEsquerda, 0, 0, RbPdfTextAlignment::Left, false);

            $pdf->font($fonteCorpo, RbPdfFontStyle::Regular, $tamFonteCorpo);
            foreach ($cincoValoresMonetarios as $k => $valor) {
                $pdf->cell(
                    (float) $largurasSubtotaisEStatus[$k],
                    $alturaLinhaResumo,
                    $textoMaiusculo($valor),
                    0,
                    0,
                    RbPdfTextAlignment::Right,
                    false,
                );
            }

            $pdf->cell(
                (float) $largurasSubtotaisEStatus[5],
                $alturaLinhaResumo,
                '',
                0,
                1,
                RbPdfTextAlignment::Center,
                false,
            );

            $pdf->textColor(RbPdfColor::black());
        };

        $desenharBadgeParcela = function (float $larguraCelula, float $alturaLinha, string $parcela) use (
            $pdf,
            $fonteCorpo,
            $tamFonteCorpo,
            $corBadgeParcela,
            $textoBadgeParcela,
            $raioCaixaPequena,
        ): void {
            $xCelula = $pdf->getX();
            $yCelula = $pdf->getY();
            $larguraBadge = min($larguraCelula - 1, 11.0);
            $alturaBadge = 3.5;
            $xBadge = $xCelula + ($larguraCelula - $larguraBadge) / 2;
            $yBadge = $yCelula + ($alturaLinha - $alturaBadge) / 2;

            $pdf->cell($larguraCelula, $alturaLinha, '', 0, 0, RbPdfTextAlignment::Left, false);

            $pdf->fillColor($corBadgeParcela)
                ->roundedRect($xBadge, $yBadge, $larguraBadge, $alturaBadge, $raioCaixaPequena, 'F');

            $pdf->textColor(RbPdfColor::white())
                ->font($fonteCorpo, RbPdfFontStyle::Bold, 5)
                ->setXY($xBadge, $yBadge)
                ->cell(
                    $larguraBadge,
                    $alturaBadge,
                    $textoBadgeParcela($parcela),
                    0,
                    0,
                    RbPdfTextAlignment::Center,
                    false,
                );

            $pdf->textColor(RbPdfColor::black())
                ->font($fonteCorpo, RbPdfFontStyle::Regular, $tamFonteCorpo)
                ->setXY($xCelula + $larguraCelula, $yCelula);
        };

        if ($alturaNaoCabeNaPagina($alturaLinhaResumo)) {
            $novaPaginaComCabecalho();
        }

        $desenharLinhaResumoComColunas(
            $corMarca,
            RbPdfColor::white(),
            'TOTAL GERAL',
            [
                $resource['value'],
                $resource['discounts_fees'],
                $resource['discounts'],
                $resource['fees_fines'],
                $resource['total_value'],
            ],
        );

        if ($alturaNaoCabeNaPagina($alturaLinhaResumo + $alturaLinhaParcela)) {
            $novaPaginaComCabecalho();
        }

        $desenharLinhaResumoComColunas(
            $corFundoLinhaGrupo,
            RbPdfColor::black(),
            'GRUPO BENCHMARK — VOLUME (TIPOGRAFIA INTER + LINHAS PROCEDURAL)',
            [
                $resource['value'],
                $resource['discounts_fees'],
                $resource['discounts'],
                $resource['fees_fines'],
                $resource['total_value'],
            ],
        );

        $celDoc = 'F. BENCH';
        $celVend = 'VOLUME TEST';
        $celMet = 'PIX';
        $celVenc = '10/01/2026';
        $celRec = '-';
        $celParc = '01/01';
        $celVb = 'R$ 1,00';
        $celTx = 'R$ 0,00';
        $celDesc = 'R$ 0,00';
        $celJm = 'R$ 0,00';
        $celVl = 'R$ 1,00';
        $statusRotacao = ['Recebido', 'Pendente', 'Atrasado'];

        $iteracoes = 0;
        $limiteIteracoes = 800_000;

        while ($pdf->pageNo() < self::PAGINAS_ALVO && $iteracoes < $limiteIteracoes) {
            if ($alturaNaoCabeNaPagina($alturaLinhaParcela)) {
                $novaPaginaComCabecalho();
            }

            $status = $statusRotacao[$iteracoes % 3];

            $pdf->setX($margemHorizontal)
                ->font($fonteCorpo, RbPdfFontStyle::Regular, $tamFonteCorpo)
                ->textColor(RbPdfColor::black());

            $pdf->cell((float) $largurasColunas[0], $alturaLinhaParcela, $celDoc, 0, 0, RbPdfTextAlignment::Left, false);
            $pdf->cell((float) $largurasColunas[1], $alturaLinhaParcela, $celVend, 0, 0, RbPdfTextAlignment::Left, false);
            $pdf->cell((float) $largurasColunas[2], $alturaLinhaParcela, $celMet, 0, 0, RbPdfTextAlignment::Left, false);
            $pdf->cell((float) $largurasColunas[3], $alturaLinhaParcela, $celVenc, 0, 0, RbPdfTextAlignment::Left, false);
            $pdf->cell((float) $largurasColunas[4], $alturaLinhaParcela, $celRec, 0, 0, RbPdfTextAlignment::Left, false);

            $desenharBadgeParcela((float) $largurasColunas[5], $alturaLinhaParcela, $celParc);

            $pdf->cell((float) $largurasColunas[6], $alturaLinhaParcela, $celVb, 0, 0, RbPdfTextAlignment::Right, false);
            $pdf->cell((float) $largurasColunas[7], $alturaLinhaParcela, $celTx, 0, 0, RbPdfTextAlignment::Right, false);
            $pdf->cell((float) $largurasColunas[8], $alturaLinhaParcela, $celDesc, 0, 0, RbPdfTextAlignment::Right, false);
            $pdf->cell((float) $largurasColunas[9], $alturaLinhaParcela, $celJm, 0, 0, RbPdfTextAlignment::Right, false);
            $pdf->cell((float) $largurasColunas[10], $alturaLinhaParcela, $celVl, 0, 0, RbPdfTextAlignment::Right, false);

            $pdf->textColor($corPorStatus($status))
                ->cell(
                    (float) $largurasColunas[11],
                    $alturaLinhaParcela,
                    $textoMaiusculo($status),
                    0,
                    0,
                    RbPdfTextAlignment::Center,
                    false,
                );

            $pdf->textColor(RbPdfColor::black())
                ->newLine($alturaLinhaParcela);

            $iteracoes++;
        }

        self::assertSame(self::PAGINAS_ALVO, $pdf->pageNo(), 'Deve atingir exatamente a página alvo');

        $pdf->save($caminhoSaidaPdf);

        self::assertFileExists($caminhoSaidaPdf);

        $conteudoPdf = file_get_contents($caminhoSaidaPdf);
        self::assertNotFalse($conteudoPdf);
        self::assertStringStartsWith('%PDF', $conteudoPdf);

        $numPaginas = preg_match_all('/\/Type\s*\/Page[^s]/', $conteudoPdf);
        self::assertSame(
            self::PAGINAS_ALVO,
            $numPaginas,
            'PDF final deve ter '.(string) self::PAGINAS_ALVO." páginas, gerou {$numPaginas}",
        );
    }
}
