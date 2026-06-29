<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Templates;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Tests\Fixtures\Reports\BillReceiveReportRbPdfWriter;

/**
 * Gera o mesmo PDF que o caminho RbPdf de {@code BillReceiveReportJob}, com dados sintéticos
 * suficientes para pelo menos três páginas (quebras automáticas + repetição de cabeçalho).
 *
 * O arquivo em {@code storage/app/bill_receive_report_rbpdf_min_3_pages.pdf} é mantido de propósito
 * (não há {@code unlink} nem teardown) para inspeção manual após rodar o teste.
 */
#[CoversClass(BillReceiveReportRbPdfWriter::class)]
final class BillReceiveReportRbPdfWriterThreePagesTest extends TestCase
{
    public function test_gera_relatorio_contas_receber_com_no_minimo_tres_paginas_usando_config_rbpdf(): void
    {
        $root = dirname(__DIR__, 3);
        $configPath = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'rbpdf.php';
        self::assertFileExists($configPath);

        /** @var array<string, mixed> $baseConfig */
        $baseConfig = require $configPath;

        $fontsDir = $root.DIRECTORY_SEPARATOR.'fonts';
        $logoHeader = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'logo-rubick-white.png';
        $logoFooter = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'logo-rubick-footer.png';

        self::assertDirectoryExists($fontsDir);
        self::assertFileExists($logoHeader);
        self::assertFileExists($logoFooter);

        $config = array_replace_recursive($baseConfig, [
            'fonts' => [
                'directory' => $fontsDir,
            ],
            'logos' => [
                'header' => $logoHeader,
                'footer' => $logoFooter,
            ],
            'datetime' => [
                'locale' => 'pt_BR',
                'timezone' => 'America/Sao_Paulo',
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-22 12:00:00', 'America/Sao_Paulo'));
        try {
            $resource = [
                'start' => '01/03/26',
                'end' => '31/03/26',
                'company_plant' => 'Matriz São Paulo',
                'pending' => 'R$ 45.000,00',
                'late' => 'R$ 12.340,50',
                'received' => 'R$ 198.760,25',
                'value' => 'R$ 256.100,75',
                'total_value' => 'R$ 251.200,00',
                'discounts_fees' => 'R$ 2.100,00',
                'fees_fines' => 'R$ 890,50',
                'discounts' => 'R$ 1.910,25',
                'grouping_type_title' => 'Cliente',
                'grouping_type' => 1,
                'user_name' => 'Usuário Integração RbPdf',
                'now' => '2026-03-22',
            ];

            $grouping = $this->buildSyntheticEntityGroups(rowsPerGroup: 22, groupCount: 7);

            $writer = new BillReceiveReportRbPdfWriter($config);
            $binary = $writer->render($resource, $grouping);
        } finally {
            Carbon::setTestNow();
        }

        self::assertStringStartsWith('%PDF', $binary);
        self::assertStringContainsString('%%EOF', $binary);

        $pageMarkers = preg_match_all('/\/Type\s*\/Page[^s]/', $binary);
        self::assertGreaterThanOrEqual(3, $pageMarkers, 'O relatório deve ocupar no mínimo 3 páginas.');

        $outDir = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app';
        if (! is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        $outFile = $outDir.DIRECTORY_SEPARATOR.'bill_receive_report_rbpdf_min_3_pages.pdf';
        // Artefato intencionalmente persistido — não apagar após o teste.
        file_put_contents($outFile, $binary);
        self::assertFileExists($outFile);
        self::assertGreaterThan(8000, filesize($outFile));
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function buildSyntheticEntityGroups(int $rowsPerGroup, int $groupCount): array
    {
        $groups = [];

        for ($g = 1; $g <= $groupCount; $g++) {
            $installments = [];

            for ($r = 1; $r <= $rowsPerGroup; $r++) {
                $base = ($g - 1) * $rowsPerGroup + $r;
                $value = 500.0 + ($base * 13.37);
                $discountsFees = 10.0 + $r;
                $discounts = 5.0;
                $feesFines = $r % 4 === 0 ? 25.0 : 0.0;
                $totalValue = $value - $discounts + $feesFines;

                $due = sprintf('2026-03-%02d', min(28, 10 + ($r % 18)));
                $status = $r % 5 === 0 ? 1 : ($r % 7 === 0 ? 2 : 0);
                $paidAt = $status === 1 ? '2026-03-15' : null;

                $installments[] = (object) [
                    'bill_receive_type' => 1,
                    'formated_document' => [
                        'invoices' => (string) (1000 + $base),
                        'nfses' => (string) (200 + $base),
                    ],
                    'document_number' => '',
                    'contract_proposal' => 'CTR-'.str_pad((string) $base, 5, '0', STR_PAD_LEFT),
                    'entity_name' => 'Cliente sintético '.$g.' linha '.$r,
                    'seller_name' => 'Vendedor '.(($r + $g) % 4 + 1),
                    'payment_method' => $r % 2 === 0 ? 'PIX' : 'BOLETO',
                    'due_date' => $due,
                    'paid_at' => $paidAt,
                    'index' => $r,
                    'total_index' => $rowsPerGroup,
                    'value' => (string) $value,
                    'discounts_fees' => (string) $discountsFees,
                    'discounts' => (string) $discounts,
                    'fees_fines' => (string) $feesFines,
                    'total_value' => (string) $totalValue,
                    'status' => $status,
                ];
            }

            $sumValue = array_sum(array_map(static fn (object $i): float => (float) $i->value, $installments));
            $sumTotal = array_sum(array_map(static fn (object $i): float => (float) $i->total_value, $installments));
            $sumDf = array_sum(array_map(static fn (object $i): float => (float) $i->discounts_fees, $installments));
            $sumDisc = array_sum(array_map(static fn (object $i): float => (float) $i->discounts, $installments));
            $sumFf = array_sum(array_map(static fn (object $i): float => (float) $i->fees_fines, $installments));

            $groups[] = [
                'installments' => $installments,
                'name' => 'Grupo entidade '.$g.' — Holding Teste',
                'due_date' => '2026-03-'.sprintf('%02d', min(28, $g + 5)),
                'document' => sprintf('%02d.%03d.%03d/0001-%02d', $g, $g * 11, $g * 13, $g % 97),
                'entity_phone' => sprintf('(11) 98888-%04d', $g * 100 + 11),
                'value' => $this->brl($sumValue),
                'total_value' => $this->brl($sumTotal),
                'fees_fines' => $this->brl($sumFf),
                'discounts' => $this->brl($sumDisc),
                'discounts_fees' => $this->brl($sumDf),
            ];
        }

        return $groups;
    }

    private function brl(float $n): string
    {
        return 'R$ '.number_format($n, 2, ',', '.');
    }
}
