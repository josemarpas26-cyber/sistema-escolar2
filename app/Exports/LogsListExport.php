<?php

namespace App\Exports;

use App\Models\NotaLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LogsListExport implements FromArray, ShouldAutoSize, WithEvents
{
    public function __construct(
        private readonly Collection $logs,
        private readonly array $filtros = []
    ) {
    }

    public function array(): array
    {
        $linhas = [
            ['EXPORTAÇÃO DE LOGS'],
            ['Gerado em ' . now()->format('d/m/Y H:i')],
            ['Filtros aplicados', $this->resumoFiltros()],
            [],
            [
                'Data/Hora',
                'Usuário',
                'Ação',
                'Aluno',
                'Turma',
                'Disciplina',
                'Campo',
                'Valor Anterior',
                'Valor Novo',
                'Trimestre',
                'IP',
            ],
        ];

        foreach ($this->logs as $log) {
            /** @var NotaLog $log */
            $linhas[] = [
                optional($log->data_alteracao)->format('d/m/Y H:i:s') ?? '-',
                optional($log->usuario)->name ?? 'Sistema',
                $log->descricao_acao,
                optional($log->aluno)->name ?? '-',
                optional($log->turma)->nome_completo ?? '-',
                optional($log->disciplina)->nome ?? '-',
                $log->descricao_campo,
                $this->valorExportavel($log->valor_anterior),
                $this->valorExportavel($log->valor_novo),
                $log->trimestre ? $log->trimestre . 'º' : '-',
                $log->ip_address ?? '-',
            ];
        }

        return $linhas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');

                $sheet->freezePane('A6');
                $sheet->setAutoFilter("A5:{$lastColumn}{$lastRow}");

                $sheet->getStyle('A1:K2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1D4ED8'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A3:B3')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle('A5:K5')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F766E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A5:K{$lastRow}")->applyFromArray([
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

                for ($row = 6; $row <= $lastRow; $row++) {
                    if ($row % 2 !== 0) {
                        continue;
                    }

                    $sheet->getStyle("A{$row}:K{$row}")->getFill()->setFillType(Fill::FILL_SOLID);
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()->getStartColor()->setRGB('F8FAFC');
                }

                $sheet->getStyle("A6:A{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    private function resumoFiltros(): string
    {
        if (empty($this->filtros)) {
            return 'Sem filtros';
        }

        return collect($this->filtros)
            ->map(fn(string $value, string $label) => "{$label}: {$value}")
            ->implode(' | ');
    }

    private function valorExportavel(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '-';
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, ',', '.');
        }

        return (string) $valor;
    }
}
