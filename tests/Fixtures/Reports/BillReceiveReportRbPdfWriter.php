<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Fixtures\Reports;

use App\Models\Financial\BillReceive\Enum\BillReceiveTypeEnum;
use App\Models\Financial\BillReceive\Enum\ReportGroupingTypeEnum;
use Carbon\Carbon;
use Rubick\RbPdf\Config\RbPdfConfiguration;
use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\Enum\RbPdfTextAlignment;
use Rubick\RbPdf\RbPdf;
use Rubick\RbPdf\Templates\RbPdfTemplateWriter;
use Rubick\RbPdf\ValueObject\RbPdfColor;

/**
 * Fixture de teste: espelha o PDF do relatório de contas a receber (dados como em {@code BillReceiveReportJob} + RbPdf).
 *
 * Não faz parte da API pública do pacote; permanece em {@code tests/} apenas para integração visual.
 *
 * @internal
 */
final class BillReceiveReportRbPdfWriter extends RbPdfTemplateWriter
{
    private const FONT_SIZE_FILTERS = 8.5;

    private const REPORT_HEADER_TITLE = 'CONTAS A RECEBER';

    private const CARD_WIDTH = 38.0;

    private const CARD_HEIGHT = 13.0;

    private const CARD_GAP = 4.0;

    private const COLUMN_WIDTHS_ENTITY = [
        31.0, 38.0, 39.0, 25.0, 27.0, 14.0,
        22.0, 16.0, 16.0, 16.0, 25.0, 18.0,
    ];

    private const LABEL_COLUMN_COUNT_ENTITY = 6;

    private const COLUMN_WIDTHS_NON_ENTITY = [
        30.0, 18.0, 55.0, 20.0, 22.0, 22.0, 9.0,
        23.0, 14.0, 14.0, 14.0, 27.0, 19.0,
    ];

    private const LABEL_COLUMN_COUNT_NON_ENTITY = 7;

    private const CLIENT_NAME_MAX_CHARS = 35;

    /** {@see BillReceiveTypeEnum} */
    private const BILL_RECEIVE_ENTITY_CREDITS = 0;

    private const BILL_RECEIVE_CONTRACT_PAYMENTS = 1;

    /** {@see ReportGroupingTypeEnum::ENTITY} */
    private const GROUPING_ENTITY = 1;

    private RbPdfColor $badgeParcelColor;

    private RbPdfColor $statusPendingColor;

    private RbPdfColor $statusLateColor;

    private RbPdfColor $statusReceivedColor;

    /** @var array<float> */
    private array $columnWidths;

    private float $tableWidth;

    private float $labelBlockWidth;

    /** @var array<float> */
    private array $valueColumnWidths;

    private bool $isEntityGrouping;

    private int $parcelColumnIndex;

    /**
     * @param  array<string, mixed>|RbPdfConfiguration  $config  Mesma árvore que {@code config/rbpdf.php}
     */
    public function __construct(array|RbPdfConfiguration $config = [])
    {
        parent::__construct($config);
        $this->initReportColors();
    }

    /**
     * @param  array<string, mixed>  $resource  Mesmo formato produzido por {@code BillReceiveReportJob::buildDataset()}
     * @param  iterable<int|string, mixed>  $groupingType  Coleção de grupos (array ou {@code \Illuminate\Support\Collection})
     */
    public function render(array $resource, iterable $groupingType): string
    {
        $this->setColumnsByGroup($resource);

        $headerDate = Carbon::parse((string) ($resource['now'] ?? Carbon::now()->toDateString()));

        $pdf = $this->initialize(
            title: self::REPORT_HEADER_TITLE,
            subtitle: $this->upper($headerDate->translatedFormat('d M Y')),
            topRightText: (string) ($resource['user_name'] ?? ''),
        );

        $this->drawFiltersAndCards($pdf, $resource);
        $this->drawReportColumnHeaders($pdf);
        $this->drawTotalRow($pdf, $resource);
        $this->drawGroups($pdf, $groupingType, $resource);

        return $pdf->output();
    }

    private function drawFiltersAndCards(RbPdf $pdf, array $resource): void
    {
        $pageWidth = $pdf->getPageWidth();
        $totalCardsWidth = 3 * self::CARD_WIDTH + 2 * self::CARD_GAP;
        $cardsStartX = $pageWidth - self::MARGIN_HORIZONTAL - $totalCardsWidth;
        $blockTopY = $pdf->getY();
        $maxFilterTextWidth = $cardsStartX - self::MARGIN_HORIZONTAL - 5;

        $this->drawFilterLines($pdf, $resource, $maxFilterTextWidth);
        $this->drawSummaryCards($pdf, $resource, $cardsStartX, $blockTopY);

        $pdf->setXY(self::MARGIN_HORIZONTAL, $blockTopY + self::CARD_HEIGHT + 2);
        $pdf->newLine(2);
    }

    private function drawFilterLines(RbPdf $pdf, array $resource, float $maxTextWidth): void
    {
        $filters = [
            ['label' => 'PERÍODO: ',     'value' => $this->upper("{$resource['start']} A {$resource['end']}")],
            ['label' => 'CENTRAL: ',     'value' => $this->upper((string) ($resource['company_plant'] ?? ''))],
            ['label' => 'AGRUPAMENTO: ', 'value' => $this->upper((string) ($resource['grouping_type_title'] ?? ''))],
        ];

        $pdf->textColor(RbPdfColor::black());

        foreach ($filters as $filter) {
            $this->drawFilterLine(
                $pdf,
                $filter['label'],
                $filter['value'],
                maxWidth: $maxTextWidth,
                fontSize: self::FONT_SIZE_FILTERS,
            );
        }
    }

    private function drawSummaryCards(RbPdf $pdf, array $resource, float $startX, float $topY): void
    {
        $this->drawHorizontalCards($pdf, $startX, $topY, [
            ['title' => 'PENDENTE', 'value' => $this->upper((string) ($resource['pending'] ?? '')),  'color' => $this->statusPendingColor],
            ['title' => 'ATRASADO', 'value' => $this->upper((string) ($resource['late'] ?? '')),     'color' => $this->statusLateColor],
            ['title' => 'RECEBIDO', 'value' => $this->upper((string) ($resource['received'] ?? '')), 'color' => $this->statusReceivedColor],
        ], cardWidth: self::CARD_WIDTH, cardHeight: self::CARD_HEIGHT, gap: self::CARD_GAP);
    }

    private function drawReportColumnHeaders(RbPdf $pdf): void
    {
        $labels = $this->isEntityGrouping
            ? ['DOCUMENTO', 'VENDEDOR', 'MÉTODO', 'VENCIMENTO', 'RECEBIMENTO', 'PARC.', 'VALOR BRUTO', 'TAXAS', 'DESC.', 'JUROS/M.', 'VALOR LIQ.', 'STATUS']
            : ['DOCUMENTO', 'CONTRATO', 'CLIENTE', 'MÉTODO', 'VENCIMENTO', 'RECEBIMENTO', 'PARC.', 'VALOR BRUTO', 'TAXAS', 'DESC.', 'JUROS/M.', 'VALOR LIQ.', 'STATUS'];

        $alignments = array_map(
            fn (int $i) => $this->resolveHeaderAlignment($i),
            array_keys($labels),
        );

        $this->drawColumnHeaders($pdf, $labels, $this->columnWidths, $alignments);
    }

    private function drawTotalRow(RbPdf $pdf, array $resource): void
    {
        if ($this->wouldOverflow($pdf, self::ROW_HEIGHT_SUMMARY)) {
            $this->addPageWithHeaders($pdf);
        }

        $this->drawSummaryBand(
            $pdf,
            $this->brandColor,
            RbPdfColor::white(),
            'TOTAL GERAL',
            $this->labelBlockWidth,
            [
                (string) $resource['value'],
                (string) $resource['discounts_fees'],
                (string) $resource['discounts'],
                (string) $resource['fees_fines'],
                (string) $resource['total_value'],
            ],
            array_slice($this->valueColumnWidths, 0, 5),
            $this->tableWidth,
            trailingWidth: $this->valueColumnWidths[5] ?? 18.0,
        );
    }

    /**
     * @param  iterable<int|string, mixed>  $groupingType
     */
    private function drawGroups(RbPdf $pdf, iterable $groupingType, array $resource): void
    {
        foreach ($groupingType as $item) {
            $group = is_array($item) ? $item : (array) $item;

            $groupTitle = $this->buildGroupTitle($group);

            if ($this->wouldOverflow($pdf, self::ROW_HEIGHT_SUMMARY + self::ROW_HEIGHT_DATA)) {
                $this->addPageWithHeaders($pdf);
            }

            $this->drawSummaryBand(
                $pdf,
                $this->groupRowBackground,
                RbPdfColor::black(),
                $groupTitle,
                $this->labelBlockWidth,
                [
                    (string) ($group['value'] ?? ''),
                    (string) ($group['discounts_fees'] ?? ''),
                    (string) ($group['discounts'] ?? ''),
                    (string) ($group['fees_fines'] ?? ''),
                    (string) ($group['total_value'] ?? ''),
                ],
                array_slice($this->valueColumnWidths, 0, 5),
                $this->tableWidth,
                trailingWidth: $this->valueColumnWidths[5] ?? 18.0,
            );

            $this->drawInstallments($pdf, $group, $resource);
        }
    }

    private function drawInstallments(RbPdf $pdf, array $group, array $resource): void
    {
        $installments = $group['installments'] ?? [];
        if (! is_iterable($installments)) {
            return;
        }

        $list = is_array($installments) ? $installments : iterator_to_array($installments);
        $lastIndex = count($list) - 1;

        foreach ($list as $index => $installment) {
            $row = is_object($installment) ? $installment : (object) $installment;

            if ($this->wouldOverflow($pdf, self::ROW_HEIGHT_DATA)) {
                $this->addPageWithHeaders($pdf);
            }

            $this->drawInstallmentRow($pdf, $row, $resource);

            if ($index < $lastIndex) {
                $this->drawRowSeparator($pdf, $this->tableWidth);
            }
        }
    }

    private function drawInstallmentRow(RbPdf $pdf, object $row, array $resource): void
    {
        $pdf->setX(self::MARGIN_HORIZONTAL)
            ->font(self::FONT_FAMILY, RbPdfFontStyle::Regular, self::FONT_SIZE_BODY)
            ->textColor(RbPdfColor::black());

        $col = 0;

        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper($this->formatDocumentText($row)));
        $col++;

        if (! $this->isEntityGrouping) {
            $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper((string) ($row->contract_proposal ?? '')));
            $col++;
            $clientName = $this->limitUtf8String((string) ($row->entity_name ?? ''), self::CLIENT_NAME_MAX_CHARS);
            $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper($clientName));
            $col++;
        }

        if ($this->isEntityGrouping) {
            $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper((string) ($row->seller_name ?? '')));
            $col++;
        }

        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper((string) ($row->payment_method ?? '')));
        $col++;

        $dueDate = $row->due_date ? Carbon::parse((string) $row->due_date)->format('d/m/Y') : '-';
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper($dueDate));
        $col++;

        $paidAt = $row->paid_at ? Carbon::parse((string) $row->paid_at)->format('d/m/Y') : '-';
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper($paidAt));
        $col++;

        $parcelText = "{$row->index}/{$row->total_index}";
        $this->drawParcelBadge($pdf, $this->columnWidths[$col], self::ROW_HEIGHT_DATA, $parcelText);
        $col++;

        $pdf->font(self::FONT_FAMILY, RbPdfFontStyle::Regular, self::FONT_SIZE_BODY);
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper(self::formatMoney((string) $row->value)), 0, 0, RbPdfTextAlignment::Right);
        $col++;
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper(self::formatMoney((string) $row->discounts_fees)), 0, 0, RbPdfTextAlignment::Right);
        $col++;
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper(self::formatMoney((string) $row->discounts)), 0, 0, RbPdfTextAlignment::Right);
        $col++;
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper(self::formatMoney((string) $row->fees_fines)), 0, 0, RbPdfTextAlignment::Right);
        $col++;
        $pdf->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper(self::formatMoney((string) $row->total_value)), 0, 0, RbPdfTextAlignment::Right);
        $col++;

        $statusLabel = $this->resolveStatusLabel((int) $row->status, (string) ($row->due_date ?? ''), (string) ($resource['now'] ?? ''));

        $pdf->textColor($this->resolveStatusColor($statusLabel))
            ->cell($this->columnWidths[$col], self::ROW_HEIGHT_DATA, $this->upper($statusLabel), 0, 0, RbPdfTextAlignment::Center);

        $pdf->textColor(RbPdfColor::black())
            ->newLine(self::ROW_HEIGHT_DATA);
    }

    private function drawParcelBadge(RbPdf $pdf, float $cellWidth, float $rowHeight, string $parcel): void
    {
        $isCompact = ! $this->isEntityGrouping;

        $this->drawBadge(
            $pdf,
            $cellWidth,
            $rowHeight,
            $parcel,
            backgroundColor: $this->badgeParcelColor,
            maxBadgeWidth: $isCompact ? 8.0 : 11.0,
            fontSize: $isCompact ? 4.5 : 5.0,
        );
    }

    private function addPageWithHeaders(RbPdf $pdf): void
    {
        $pdf->addPage();
        $this->drawReportColumnHeaders($pdf);
    }

    private function setColumnsByGroup(array $resource): void
    {
        $gt = $resource['grouping_type'] ?? '';
        $this->isEntityGrouping = $gt === self::GROUPING_ENTITY || $gt === (string) self::GROUPING_ENTITY;

        if ($this->isEntityGrouping) {
            $this->columnWidths = self::COLUMN_WIDTHS_ENTITY;
            $labelCount = self::LABEL_COLUMN_COUNT_ENTITY;
            $this->parcelColumnIndex = 5;
        } else {
            $this->columnWidths = self::COLUMN_WIDTHS_NON_ENTITY;
            $labelCount = self::LABEL_COLUMN_COUNT_NON_ENTITY;
            $this->parcelColumnIndex = 6;
        }

        $this->tableWidth = (float) array_sum($this->columnWidths);
        $this->labelBlockWidth = (float) array_sum(array_slice($this->columnWidths, 0, $labelCount));
        $this->valueColumnWidths = array_slice($this->columnWidths, $labelCount);
    }

    private function resolveHeaderAlignment(int $index): RbPdfTextAlignment
    {
        if ($index < $this->parcelColumnIndex) {
            return RbPdfTextAlignment::Left;
        }

        if ($index === $this->parcelColumnIndex) {
            return RbPdfTextAlignment::Center;
        }

        $totalColumns = count($this->columnWidths);
        if ($index >= $this->parcelColumnIndex + 1 && $index <= $totalColumns - 2) {
            return RbPdfTextAlignment::Right;
        }

        return RbPdfTextAlignment::Center;
    }

    private function buildGroupTitle(array $group): string
    {
        $title = $this->upper((string) ($group['name'] ?? ''));

        if (($group['document'] ?? '') !== '') {
            $title .= ' ('.$this->upper((string) $group['document']).')';
        }

        if ($this->isEntityGrouping && ($group['entity_phone'] ?? '') !== '') {
            $title .= ' | CONTATO: '.$this->upper((string) $group['entity_phone']);
        }

        return $title;
    }

    private function formatDocumentText(object $row): string
    {
        $billReceiveType = $row->bill_receive_type ?? null;

        if ($billReceiveType === self::BILL_RECEIVE_ENTITY_CREDITS) {
            return 'CRED. ANTECI.';
        }

        if ($billReceiveType === self::BILL_RECEIVE_CONTRACT_PAYMENTS) {
            $invoices = $row->formated_document['invoices'] ?? '';
            $nfses = $row->formated_document['nfses'] ?? '';

            if ($invoices !== '') {
                $text = "F. {$invoices}";
                if ($nfses !== '') {
                    $text .= " | NFS-e {$nfses}";
                }

                return $text;
            }

            return '-';
        }

        $docNumber = $row->document_number ?? '';

        return $docNumber !== '' ? "OF. {$docNumber}" : '-';
    }

    private function resolveStatusLabel(int $status, string $dueDate, string $now): string
    {
        return match ($status) {
            0 => $dueDate < $now ? 'Atrasado' : 'Pendente',
            1 => 'Recebido',
            2 => 'Atrasado',
            3 => 'Cancelado',
            4 => 'Serasa',
            5 => 'Cartório',
            6 => 'Protestado',
            7 => 'Jurídico',
            8 => 'Aprovado',
            default => '',
        };
    }

    private function resolveStatusColor(string $label): RbPdfColor
    {
        return match (mb_strtolower($label)) {
            'atrasado', 'cancelado' => $this->statusLateColor,
            'pendente' => $this->statusPendingColor,
            'recebido' => $this->statusReceivedColor,
            default => RbPdfColor::black(),
        };
    }

    private function initReportColors(): void
    {
        $this->badgeParcelColor = RbPdfColor::rgb(73, 126, 168);
        $this->statusPendingColor = RbPdfColor::rgb(242, 181, 50);
        $this->statusLateColor = RbPdfColor::rgb(169, 68, 66);
        $this->statusReceivedColor = RbPdfColor::rgb(60, 118, 61);
    }

    private static function formatMoney(?string $value): string
    {
        $num = ($value === null || $value === '') ? 0.0 : (float) $value;

        return 'R$ '.number_format($num, 2, ',', '.');
    }

    private function limitUtf8String(string $value, int $limit): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit - 3, 'UTF-8').'...';
    }
}
