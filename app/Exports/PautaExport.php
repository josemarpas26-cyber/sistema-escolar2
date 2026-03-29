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
use PhpOffice\PhpSpreadsheet\Style\Font;

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

    // Cores inspiradas no documento oficial
    private const COLOR_TITLE_BG    = 'FFFFFF';
    private const COLOR_TITLE_FONT  = '000000';
    private const COLOR_HEADER_BG   = 'D9D9D9'; // Cinza claro estilo pauta oficial
    private const COLOR_HEADER_FONT = '000000';
    private const COLOR_INFO_BG     = 'FFFFFF';
    private const COLOR_INFO_LABEL  = '000000';
    private const COLOR_STAT_BG     = 'F3F4F6';
    private const COLOR_STAT_LABEL  = '1F2937';
    private const COLOR_ZEBRA_ODD   = 'FFFFFF';
    private const COLOR_ZEBRA_EVEN  = 'F9FAFB';
    private const COLOR_BORDER      = '000000';
    private const COLOR_APROVADO    = '008000'; // Verde escuro
    private const COLOR_REPROVADO   = 'CC0000'; // Vermelho
    private const COLOR_MT_BG       = 'E6F3FF'; // Azul claro para MT/CF/CFD
    private const COLOR_CFD_BG      = 'DFF0D8'; // Verde claro para CFD

    private const HEADER_ROW     = 9;  // Linha do cabeçalho da tabela
    private const DATA_START_ROW = 10; // Primeira linha de dados
    private const LAST_COL       = 'T'; // Última coluna (Situação)

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

        // ===== CABEÇALHO INSTITUCIONAL =====
        $data->push(['INSTITUTO POLITÉCNICO INDUSTRIAL DO KILAMBA KIAXI Nº 8056 "NOVA VIDA"']);
        $data->push(['']);
        $data->push(['PAUTA DE APROVEITAMENTO — ' . strtoupper($this->disciplina->nome)]);
        $data->push(['']);
        $data->push([
            'Ano Lectivo:', $this->turma->anoLetivo->nome ?? '2025/2026',
            '', 'Classe:', $this->turma->classe . 'ª',
            '', 'Turma:', $this->turma->nome,
            '', 'Curso:', $this->turma->curso->nome ?? '-'
        ]);
        $data->push(['']);
        $data->push(['Disciplina: ' . $this->disciplina->nome]);
        $data->push(['']);
        $data->push(['']); // Linha em branco antes da tabela

        // ===== DADOS DOS ALUNOS =====
        $contador = 1;
        foreach ($this->notas as $nota) {
            $data->push([
                $contador++,
                $nota->aluno->numero_processo ?? '—',
                $nota->aluno->name,
                $nota->aluno->genero === 'M' ? 'M' : ($nota->aluno->genero === 'F' ? 'F' : ''),
                '', '', // Faltas J/I (vazio se não houver controle)
                $this->fmtNota($nota->mft2),
                $this->fmtNota($nota->mac1),
                $this->fmtNota($nota->pp1),
                $this->fmtNota($nota->pt1),
                $this->fmtNota($nota->mt1),
                $this->fmtNota($nota->mac2),
                $this->fmtNota($nota->pp2),
                $this->fmtNota($nota->pt2),
                $this->fmtNota($nota->mt2),
                $this->fmtNota($nota->mac3),
                $this->fmtNota($nota->pp3),
                $this->fmtNota($nota->pg),      // PG atribuída (não calculada)
                $this->fmtNota($nota->mt3),
                $this->fmtNota($nota->cf),
                $this->fmtNota($nota->cfd),
                $nota->isAprovado() ? 'Aprovado' : 'Reprovado',
            ]);
        }

        // ===== RODAPÉ COM ASSINATURAS =====
        $totalAlunos = $this->notas->count();
        $lastDataRow = self::DATA_START_ROW + $totalAlunos - 1;

        $data->push(['']); // Linha em branco após tabela
        $data->push(['OBSERVAÇÕES:']);
        $data->push(['• Na coluna Resultado: "Aprovado" (CFD ≥ 10) ou "Reprovado" (CFD < 10).']);
        $data->push(['• PG (Prova Global) é atribuída, não calculada.']);
        $data->push(['• CFD = Classificação Final Definitiva (arredondamento comercial de CA).']);
        $data->push(['']);

        $data->push([
            'Luanda, ___/___/______',
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            'O DIRECTOR DE TURMA',
        ]);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            '_____________________________',
        ]);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            $this->turma->coordenador?->name ?? '_____________________________',
        ]);
        $data->push(['']);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            'O COORDENADOR DE CURSO',
        ]);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            '_____________________________',
        ]);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            $this->turma->curso?->coordenador?->name ?? '_____________________________',
        ]);
        $data->push(['']);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            'O SUBDIRECTOR PEDAGÓGICO',
        ]);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            '_____________________________',
        ]);
        $data->push([
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            'Carlos Alberto Brito Teixeira da Silva',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nº', 'Nº Proc.', 'Nome Completo', 'Sexo',
            'Faltas J', 'Faltas I', 'MFT2',
            'MAC1', 'PP1', 'PT1', 'MT1',
            'MAC2', 'PP2', 'PT2', 'MT2',
            'MAC3', 'PP3', 'PG', 'MT3',
            'CF', 'CFD', 'Resultado',
        ];
    }

    public function title(): string
    {
        return mb_substr('Pauta-' . $this->disciplina->codigo . '-' . $this->turma->nome, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        $totalNotas  = $this->notas->count();
        $lastDataRow = self::DATA_START_ROW + $totalNotas - 1;
        $lastCol     = self::LAST_COL;

        // ===== TÍTULO PRINCIPAL (Linha 1) =====
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true, 'size' => 14, 'color' => ['rgb' => self::COLOR_TITLE_FONT],
                'name' => 'Arial',
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ===== SUBTÍTULO (Linha 3) =====
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getRowDimension(3)->setRowHeight(25);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000'], 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ===== INFO TURMA/DISCIPLINA (Linha 5) =====
        $sheet->mergeCells("A5:{$lastCol}5");
        $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // ===== CABEÇALHO DA TABELA (Linha 9) =====
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(35);
        $sheet->getStyle("A" . self::HEADER_ROW . ":{$lastCol}" . self::HEADER_ROW)->applyFromArray([
            'font' => [
                'bold' => true, 'size' => 9, 'color' => ['rgb' => self::COLOR_HEADER_FONT],
                'name' => 'Arial',
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
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => self::COLOR_BORDER],
                ],
            ],
        ]);

        // ===== DADOS DOS ALUNOS =====
        for ($row = self::DATA_START_ROW; $row <= $lastDataRow; $row++) {
            $bgColor = ($row % 2 === 0) ? self::COLOR_ZEBRA_EVEN : self::COLOR_ZEBRA_ODD;

            $sheet->getRowDimension($row)->setRowHeight(20);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'font' => ['size' => 9, 'name' => 'Arial'],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => self::COLOR_BORDER],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Nome do aluno alinhado à esquerda
            $sheet->getStyle("C{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Notas centralizadas
            $sheet->getStyle("G{$row}:{$lastCol}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Destaque para colunas MT, CF, CFD
            foreach (['K', 'O', 'S', 'T'] as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            }
            $sheet->getStyle("K{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(self::COLOR_MT_BG);
            $sheet->getStyle("O{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(self::COLOR_MT_BG);
            $sheet->getStyle("S{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(self::COLOR_CFD_BG);

            // Cor do Resultado
            $statusVal = $sheet->getCell("T{$row}")->getValue();
            $statusColor = (stripos($statusVal ?? '', 'aprov') !== false)
                ? self::COLOR_APROVADO
                : self::COLOR_REPROVADO;
            $sheet->getStyle("T{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $statusColor]],
            ]);
        }

        // ===== OBSERVAÇÕES =====
        $obsStart = $lastDataRow + 2;
        $sheet->mergeCells("A{$obsStart}:{$lastCol}{$obsStart}");
        $sheet->getStyle("A{$obsStart}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $row = $obsStart + $i;
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['size' => 9, 'italic' => true, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }

        // ===== ASSINATURAS =====
        $sigStart = $obsStart + 5;

        // Diretor de Turma
        $sheet->mergeCells("A{$sigStart}:U{$sigStart}");
        $sheet->getStyle("A{$sigStart}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle("V{$sigStart}:{$lastCol}{$sigStart}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->mergeCells("A" . ($sigStart+1) . ":U" . ($sigStart+1));
        $sheet->getStyle("V" . ($sigStart+1) . ":{$lastCol}" . ($sigStart+1))->applyFromArray([
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->getStyle("V" . ($sigStart+2) . ":{$lastCol}" . ($sigStart+2))->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // Coordenador de Curso
        $sig2 = $sigStart + 4;
        $sheet->mergeCells("A{$sig2}:U{$sig2}");
        $sheet->getStyle("V{$sig2}:{$lastCol}{$sig2}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("V" . ($sig2+1) . ":{$lastCol}" . ($sig2+1))->applyFromArray([
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("V" . ($sig2+2) . ":{$lastCol}" . ($sig2+2))->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // Subdirector Pedagógico
        $sig3 = $sig2 + 4;
        $sheet->mergeCells("A{$sig3}:U{$sig3}");
        $sheet->getStyle("V{$sig3}:{$lastCol}{$sig3}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("V" . ($sig3+1) . ":{$lastCol}" . ($sig3+1))->applyFromArray([
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("V" . ($sig3+2) . ":{$lastCol}" . ($sig3+2))->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalNotas = $this->notas->count();
                $lastDataRow = self::DATA_START_ROW + $totalNotas - 1;

                // ===== LARGURA DAS COLUNAS =====
                $widths = [
                    'A' => 5,   // Nº
                    'B' => 12,  // Nº Proc.
                    'C' => 35,  // Nome Completo
                    'D' => 6,   // Sexo
                    'E' => 8, 'F' => 8,  // Faltas
                    'G' => 9,   // MFT2
                    'H' => 9, 'I' => 9, 'J' => 9, 'K' => 9,   // T1
                    'L' => 9, 'M' => 9, 'N' => 9, 'O' => 9,   // T2
                    'P' => 9, 'Q' => 9, 'R' => 9, 'S' => 9,   // T3 + CF
                    'T' => 14,  // CFD/Resultado
                ];
                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setAutoSize(false);
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // ===== FORMATO NUMÉRICO PARA NOTAS =====
                $sheet->getStyle('G:S')->getNumberFormat()
                    ->setFormatCode('#,##0.00;-#,##0.00;"—"');

                // ===== CONGELAR CABEÇALHO =====
                $sheet->freezePane('A' . self::DATA_START_ROW);

                // ===== AUTO FILTER =====
                $sheet->setAutoFilter('A' . self::HEADER_ROW . ':' . self::LAST_COL . $lastDataRow);

                // ===== CONFIGURAÇÃO DE IMPRESSÃO =====
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()
                    ->setTop(0.5)->setRight(0.4)
                    ->setLeft(0.4)->setBottom(0.8);

                // ===== CABEÇALHO E RODAPÉ DE IMPRESSÃO =====
                $sheet->getHeaderFooter()
                    ->setOddHeader('&C&"Arial,Bold"&14INSTITUTO POLITÉCNICO INDUSTRIAL DO KILAMBA KIAXI Nº 8056 "NOVA VIDA"')
                    ->setOddFooter('&L' . $this->disciplina->nome . ' — ' . $this->turma->nome_completo . '&RPág. &P de &N');
            },
        ];
    }

    private function fmtNota($value): string
    {
        if (is_null($value) || $value === '') {
            return '—';
        }
        return number_format((float) $value, 2, ',', '.');
    }
}