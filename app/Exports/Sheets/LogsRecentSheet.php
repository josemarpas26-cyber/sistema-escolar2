<?php

namespace App\Exports\Sheets;

use App\Models\NotaLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LogsRecentSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(private readonly Collection $logs) {}

    public function array(): array
    {
        $rows = [[
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
            'Motivo',
        ]];

        foreach ($this->logs as $log) {
            /** @var NotaLog $log */
            $rows[] = [
                optional($log->data_alteracao)->format('d/m/Y H:i:s') ?? '-',
                optional($log->usuario)->name ?? 'Sistema',
                $log->descricao_acao,
                $log->alvo_exibicao,
                optional($log->turma)->nome_completo ?? '-',
                optional($log->disciplina)->nome ?? '-',
                $log->descricao_campo,
                $this->valor($log->valor_anterior),
                $this->valor($log->valor_novo),
                $log->trimestre ? $log->trimestre.'º' : '-',
                $log->motivo ?? '-',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Logs Recentes';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
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

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 !== 0) {
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

    private function valor(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '-';
        }
        
        if (is_string($valor)) {
            $dados = json_decode($valor, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($dados) && array_key_exists('valor', $dados)) {
                $valorAvaliacao = $dados['valor'];

                return is_numeric($valorAvaliacao)
                    ? number_format((float) $valorAvaliacao, 2, ',', '.')
                    : '-';
            }
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, ',', '.');
        }

        return (string) $valor;
    }
}
