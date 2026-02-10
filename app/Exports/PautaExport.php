<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PautaExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $turma;
    protected $disciplina;
    protected $notas;
    protected $stats;

    public function __construct($turma, $disciplina, $notas, $stats)
    {
        $this->turma = $turma;
        $this->disciplina = $disciplina;
        $this->notas = $notas;
        $this->stats = $stats;
    }

    public function collection()
    {
        $data = collect();

        // Cabeçalho
        $data->push(['PAUTA DE NOTAS']);
        $data->push(['']);
        $data->push(['Turma:', $this->turma->nome_completo]);
        $data->push(['Disciplina:', $this->disciplina->nome]);
        $data->push(['Ano Letivo:', $this->turma->anoLetivo->nome]);
        $data->push(['']);

        // Notas dos alunos
        $contador = 1;
        foreach ($this->notas as $nota) {
            $data->push([
                $contador++,
                $nota->aluno->name,
                $nota->mac1 ? number_format($nota->mac1, 2) : '-',
                $nota->pp1 ? number_format($nota->pp1, 2) : '-',
                $nota->pt1 ? number_format($nota->pt1, 2) : '-',
                $nota->mt1 ? number_format($nota->mt1, 2) : '-',
                $nota->mac2 ? number_format($nota->mac2, 2) : '-',
                $nota->pp2 ? number_format($nota->pp2, 2) : '-',
                $nota->pt2 ? number_format($nota->pt2, 2) : '-',
                $nota->mt2 ? number_format($nota->mt2, 2) : '-',
                $nota->mft2 ? number_format($nota->mft2, 2) : '-',
                $nota->mac3 ? number_format($nota->mac3, 2) : '-',
                $nota->pp3 ? number_format($nota->pp3, 2) : '-',
                $nota->pg ? number_format($nota->pg, 2) : '-',
                $nota->mt3 ? number_format($nota->mt3, 2) : '-',
                $nota->cf ? number_format($nota->cf, 2) : '-',
                $nota->cfd ? number_format($nota->cfd, 2) : '-',
                $nota->isAprovado() ? 'A' : 'R',
            ]);
        }

        // Estatísticas
        $data->push(['']);
        $data->push(['ESTATÍSTICAS']);
        $data->push(['Média Geral:', number_format($this->stats['mediaGeral'], 2)]);
        $data->push(['Aprovados:', $this->stats['aprovacoes']]);
        $data->push(['Reprovados:', $this->stats['reprovacoes']]);
        $data->push(['Total de Alunos:', $this->stats['totalAlunos']]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nº',
            'Aluno',
            'MAC1',
            'PP1',
            'PT1',
            'MT1',
            'MAC2',
            'PP2',
            'PT2',
            'MT2',
            'MFT2',
            'MAC3',
            'PP3',
            'PG',
            'MT3',
            'CF',
            'CFD',
            'Status'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
            7 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return 'Pauta';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 30,
            'C' => 8,
            'D' => 8,
            'E' => 8,
            'F' => 8,
            'G' => 8,
            'H' => 8,
            'I' => 8,
            'J' => 8,
            'K' => 8,
            'L' => 8,
            'M' => 8,
            'N' => 8,
            'O' => 8,
            'P' => 8,
            'Q' => 8,
            'R' => 10,
        ];
    }
}