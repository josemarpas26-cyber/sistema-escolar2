<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PautaExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithEvents
{
    protected $turma;
    protected $disciplina;
    protected $notas;
    protected $stats;

    private const COLOR_TITLE_BG    = '111827';
    private const COLOR_TITLE_FONT  = 'FFFFFF';
    private const COLOR_HEADER_BG   = '1E40AF';
    private const COLOR_HEADER_FONT = 'FFFFFF';
    private const COLOR_INFO_BG     = 'EFF6FF';
    private const COLOR_INFO_LABEL  = '1E3A8A';
    private const COLOR_STAT_BG     = 'F0FDF4';
    private const COLOR_STAT_LABEL  = '166534';
    private const COLOR_ZEBRA       = 'F9FAFB';
    private const COLOR_BORDER      = 'E5E7EB';
    private const COLOR_APROVADO    = '059669';
    private const COLOR_REPROVADO   = 'DC2626';
    private const COLOR_MT_BG       = 'DBEAFE';
    private const COLOR_CFD_BG      = 'D1FAE5';

    private const HEADER_ROW     = 7;
    private const DATA_START_ROW = 8;
    private const LAST_COL       = 'T';



    public function __construct($turma, $disciplina, $notas, $stats)
    {
        $this->turma      = $turma;
        $this->disciplina = $disciplina;
        $this->notas      = $notas;
        $this->stats      = $stats;
    }

    public function collection()
    {
        $data = collect();

        $data->push(['PAUTA DE NOTAS — ' . $this->disciplina->nome]);
        $data->push(['']);
        $data->push(['Turma:', $this->turma->nome_completo, '', 'Disciplina:', $this->disciplina->nome]);
        $data->push(['Ano Letivo:', $this->turma->anoLetivo->nome, '', 'Curso:', $this->turma->curso->nome ?? '-']);
        $data->push(['Total de Alunos:', $this->notas->count(), '', 'Classe:', $this->turma->classe . 'ª']);
        $data->push(['']);

        $contador = 1;
        foreach ($this->notas as $nota) {
            $data->push([
                $contador++,
                $nota->aluno->name,
                $nota->aluno->numero_processo ?? '—',
                in_array($nota->aluno->genero, ['M', 'F']) ? $nota->aluno->genero : '—',
                $this->fmtNota($nota->mac1),
                $this->fmtNota($nota->pp1),
                $this->fmtNota($nota->pt1),
                $this->fmtNota($nota->mt1),
                $this->fmtNota($nota->mac2),
                $this->fmtNota($nota->pp2),
                $this->fmtNota($nota->pt2),
                $this->fmtNota($nota->mt2),
                $this->fmtNota($nota->mft2),
                $this->fmtNota($nota->mac3),
                $this->fmtNota($nota->pp3),
                $this->fmtNota($nota->pg),
                $this->fmtNota($nota->mt3),
                $this->fmtNota($nota->cf),
                $this->fmtNota($nota->cfd),
                $nota->isAprovado() ? '✓ Aprovado' : '✗ Reprovado',
            ]);
        }

        $data->push(['']);
        $data->push(['RESUMO ESTATÍSTICO']);
        $data->push(['Média Geral:', number_format($this->stats['mediaGeral'], 2, ',', '.')]);
        $data->push(['Aprovados:', $this->stats['aprovacoes'], '', 'Reprovados:', $this->stats['reprovacoes']]);
        $data->push([
            'Taxa de Aprovação:',
            number_format(
                $this->stats['totalAlunos'] > 0
                    ? ($this->stats['aprovacoes'] / $this->stats['totalAlunos']) * 100
                    : 0,
                1, ',', '.'
            ) . '%',
        ]);
        $data->push(['']);
        $data->push(['Documento gerado em ' . now()->format('d/m/Y \à\s H:i:s')]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nº', 'Nome do Aluno', 'Nº Processo', 'Gên.',
            'MAC1', 'PP1', 'PT1', 'MT1',
            'MAC2', 'PP2', 'PT2', 'MT2', 'MFT2',
            'MAC3', 'PP3', 'PG', 'MT3', 'CF',
            'CFD', 'Situação',
        ];
    }

    public function title(): string
    {
        return mb_substr('Pauta - ' . $this->disciplina->nome, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        $totalNotas  = $this->notas->count();
        $lastDataRow = self::DATA_START_ROW + $totalNotas - 1;
        $lastCol     = self::LAST_COL;

        // ── Título (linha 1) ──
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getRowDimension(1)->setRowHeight(38);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true, 'size' => 16,
                'color' => ['rgb' => self::COLOR_TITLE_FONT],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_TITLE_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ── Info (linhas 3–5) ──
        foreach ([3, 4, 5] as $row) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_INFO_BG],
                ],
            ]);
            foreach (['A', 'D'] as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_INFO_LABEL]],
                ]);
            }
        }

        // ── Cabeçalho tabela (linha 7) ──
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(28);
        $sheet->getStyle("A" . self::HEADER_ROW . ":{$lastCol}" . self::HEADER_ROW)->applyFromArray([
            'font' => [
                'bold' => true, 'size' => 10,
                'color' => ['rgb' => self::COLOR_HEADER_FONT],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_HEADER_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '1E3A8A'],
                ],
            ],
        ]);

        // ── Dados: zebra + bordas + destaques ──
        for ($row = self::DATA_START_ROW; $row <= $lastDataRow; $row++) {
            $bgColor = ($row % 2 === 0) ? self::COLOR_ZEBRA : 'FFFFFF';

            $sheet->getRowDimension($row)->setRowHeight(22);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => self::COLOR_BORDER],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle("B{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle("E{$row}:{$lastCol}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // MT columns
            foreach (['H', 'L', 'Q'] as $mtCol) {
                $sheet->getStyle("{$mtCol}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::COLOR_MT_BG],
                    ],
                    'font' => ['bold' => true],
                ]);
            }

            // CFD
            $sheet->getStyle("S{$row}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_CFD_BG],
                ],
                'font' => ['bold' => true],
            ]);

            // Status
            $statusVal   = $sheet->getCell("T{$row}")->getValue();
            $statusColor = str_contains($statusVal ?? '', 'Aprovado')
                ? self::COLOR_APROVADO
                : self::COLOR_REPROVADO;

            $sheet->getStyle("T{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $statusColor]],
            ]);
        }

        // ── Estatísticas ──
        $statsStart = $lastDataRow + 2;
        $statsEnd   = $statsStart + 4;

        $sheet->mergeCells("A{$statsStart}:{$lastCol}{$statsStart}");
        $sheet->getRowDimension($statsStart)->setRowHeight(28);
        $sheet->getStyle("A{$statsStart}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::COLOR_STAT_LABEL]],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_STAT_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        for ($row = $statsStart + 1; $row <= $statsEnd; $row++) {
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_STAT_LABEL]],
            ]);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_STAT_BG],
                ],
            ]);
        }

        // ── Rodapé ──
        $footerRow = $statsEnd + 2;
        if ($sheet->getCell("A{$footerRow}")->getValue()) {
            $sheet->mergeCells("A{$footerRow}:{$lastCol}{$footerRow}");
            $sheet->getStyle("A{$footerRow}")->applyFromArray([
                'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '9CA3AF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        return [];
    }

    // ================================================================
    // Eventos
    // ================================================================

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();

            // ===============================
            // LARGURA TOTAL CONTROLADA (SEM AUTOSIZE)
            // ===============================

            $widths = [
                'A' => 6,   // Nº
                'B' => 32,  // Nome
                'C' => 16,  // Processo
                'D' => 8,   // Gênero

                // NOTAS (largura suficiente p/ evitar ###)
                'E' => 12, 'F' => 12, 'G' => 12, 'H' => 12,
                'I' => 12, 'J' => 12, 'K' => 12, 'L' => 12,
                'M' => 12, 'N' => 12, 'O' => 12, 'P' => 12,
                'Q' => 12, 'R' => 12, 'S' => 12,

                'T' => 20, // Situação
            ];

            foreach ($widths as $col => $width) {
                $sheet->getColumnDimension($col)->setAutoSize(false);
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            // ===============================
            // FORÇAR TEXTO NAS NOTAS (ANTI ###)
            // ===============================
            $sheet->getStyle('E:S')->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

            // ===============================
            // CONGELAR
            // ===============================
            $sheet->freezePane('A' . self::DATA_START_ROW);

            // ===============================
            // AUTO FILTER
            // ===============================
            $totalNotas    = $this->notas->count();
            $filterLastRow = self::DATA_START_ROW + $totalNotas - 1;

            $sheet->setAutoFilter(
                'A' . self::HEADER_ROW . ':' . self::LAST_COL . $filterLastRow
            );

            // ===============================
            // PRINT SETTINGS
            // ===============================
            $sheet->getPageSetup()
                ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(PageSetup::PAPERSIZE_A3)
                ->setFitToWidth(1)
                ->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.4)->setRight(0.3)
                ->setLeft(0.3)->setBottom(0.4);

            $sheet->getHeaderFooter()
                ->setOddFooter(
                    '&L' . $this->turma->nome_completo .
                    ' — ' . $this->disciplina->nome .
                    '&RPágina &P de &N'
                );
        },
    ];
}

    private function fmtNota($value): string
    {
        if (is_null($value)) {
            return '—';
        }

        // FORÇA STRING (impede Excel de quebrar)
        return (string) number_format((float) $value, 2, ',', '.');
    }
}