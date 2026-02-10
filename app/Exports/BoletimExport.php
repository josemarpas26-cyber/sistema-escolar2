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
use PhpOffice\PhpSpreadsheet\Style\Border;

class BoletimExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $aluno;
    protected $turma;
    protected $notas;
    protected $mediaGeral;

    public function __construct($aluno, $turma, $notas, $mediaGeral)
    {
        $this->aluno = $aluno;
        $this->turma = $turma;
        $this->notas = $notas;
        $this->mediaGeral = $mediaGeral;
    }

    public function collection()
    {
        $data = collect();

        // Cabeçalho com info do aluno
        $data->push(['BOLETIM ESCOLAR']);
        $data->push(['']);
        $data->push(['Aluno:', $this->aluno->name]);
        $data->push(['Turma:', $this->turma->nome_completo]);
        $data->push(['Curso:', $this->turma->curso->nome]);
        $data->push(['Ano Letivo:', $this->turma->anoLetivo->nome]);
        $data->push(['']);

        // Notas por disciplina
        foreach ($this->notas as $nota) {
            $data->push([
                $nota->disciplina->nome,
                $nota->mt1 ? number_format($nota->mt1, 2) : '-',
                $nota->mt2 ? number_format($nota->mt2, 2) : '-',
                $nota->mt3 ? number_format($nota->mt3, 2) : '-',
                $nota->cfd ? number_format($nota->cfd, 2) : '-',
                $nota->isAprovado() ? 'Aprovado' : 'Reprovado',
            ]);
        }

        $data->push(['']);
        $data->push(['MÉDIA GERAL:', number_format($this->mediaGeral, 2)]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Disciplina',
            'MT1',
            'MT2', 
            'MT3',
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
            6 => ['font' => ['bold' => true]],
            8 => [
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
        return 'Boletim';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 10,
            'C' => 10,
            'D' => 10,
            'E' => 10,
            'F' => 15,
        ];
    }
}