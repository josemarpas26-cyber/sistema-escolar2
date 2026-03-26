<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LogsDashboardSummarySheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private array $sectionRows = [];
    private array $headerRows = [];

    public function __construct(private readonly array $dados)
    {
    }

    public function array(): array
    {
        $rows = [
            ['DASHBOARD DE LOGS'],
            ['Gerado em ' . now()->format('d/m/Y H:i')],
            [],
        ];

        $this->addSection($rows, 'Resumo Geral', ['Indicador', 'Valor'], [
            ['Total de Logs', $this->dados['totalLogs']],
            ['Hoje', $this->dados['logsHoje']],
            ['Esta Semana', $this->dados['logsSemana']],
            ['Este Mês', $this->dados['logsMes']],
        ]);

        $this->addSection($rows, 'Logs por Ação', ['Ação', 'Total'], $this->pairRows($this->dados['logsPorAcao']));
        $this->addSection($rows, 'Logs por Trimestre', ['Trimestre', 'Total'], $this->pairRows($this->dados['logsPorTrimestre']));
        $this->addSection($rows, 'Utilizadores Mais Ativos', ['Utilizador', 'Função', 'Total'], $this->usuariosRows($this->dados['topUsuarios']));
        $this->addSection($rows, 'Disciplinas Mais Editadas', ['Disciplina', 'Código', 'Total'], $this->disciplinasRows($this->dados['topDisciplinas']));
        $this->addSection($rows, 'Atividade dos Últimos 7 Dias', ['Data', 'Total'], $this->atividadeRows($this->dados['atividadeSemanal']));

        return $rows;
    }

    public function title(): string
    {
        return 'Resumo';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");

                $sheet->getStyle("A1:{$lastColumn}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                foreach ($this->sectionRows as $row) {
                    $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '0F766E'],
                        ],
                    ]);
                }

                foreach ($this->headerRows as $rowInfo) {
                    $range = 'A' . $rowInfo['row'] . ':' . $rowInfo['lastColumn'] . $rowInfo['row'];

                    $sheet->getStyle($range)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '2563EB'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                for ($row = 4; $row <= $lastRow; $row++) {
                    if (in_array($row, $this->sectionRows, true)) {
                        continue;
                    }

                    $isHeader = collect($this->headerRows)->contains(fn(array $info) => $info['row'] === $row);

                    if ($isHeader || $row % 2 !== 0) {
                        continue;
                    }

                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID);
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                        ->getFill()
                        ->getStartColor()
                        ->setRGB('F8FAFC');
                }
            },
        ];
    }

    private function addSection(array &$rows, string $title, array $headers, array $items): void
    {
        $this->sectionRows[] = count($rows) + 1;
        $rows[] = [$title];

        $headerRow = count($rows) + 1;
        $rows[] = $headers;
        $this->headerRows[] = [
            'row' => $headerRow,
            'lastColumn' => Coordinate::stringFromColumnIndex(count($headers)),
        ];

        if (empty($items)) {
            $rows[] = ['Sem dados disponíveis'];
            $rows[] = [];
            return;
        }

        foreach ($items as $item) {
            $rows[] = $item;
        }

        $rows[] = [];
    }

    private function pairRows(Collection $collection): array
    {
        return $collection
            ->map(fn($total, $label) => [ucfirst((string) $label), $total])
            ->values()
            ->all();
    }

    private function usuariosRows(Collection $usuarios): array
    {
        return $usuarios
            ->map(fn($item) => [
                optional($item->usuario)->name ?? 'Sistema',
                optional(optional($item->usuario)->role)->display_name ?? 'Sem função',
                $item->total,
            ])
            ->values()
            ->all();
    }

    private function disciplinasRows(Collection $disciplinas): array
    {
        return $disciplinas
            ->map(fn($item) => [
                optional($item->disciplina)->nome ?? '-',
                optional($item->disciplina)->codigo ?? '-',
                $item->total,
            ])
            ->values()
            ->all();
    }

    private function atividadeRows(Collection $atividade): array
    {
        return $atividade
            ->map(fn($total, $dia) => [
                \Carbon\Carbon::parse($dia)->format('d/m/Y'),
                $total,
            ])
            ->values()
            ->all();
    }
}
