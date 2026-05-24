<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BoletimExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $aluno;
    protected $turma;
    protected $notas;
    protected $mediaGeral;
    protected string $trimestre;
    protected ?array $classificacaoEnsinoMedioResumo;

    public function __construct($aluno, $turma, $notas, $mediaGeral, string $trimestre = 'final', ?array $classificacaoEnsinoMedioResumo = null)
    {
        $this->aluno = $aluno;
        $this->turma = $turma;
        $this->notas = $notas;
        $this->mediaGeral = $mediaGeral;
        $this->trimestre = $trimestre;
        $this->classificacaoEnsinoMedioResumo = $classificacaoEnsinoMedioResumo;
    }

    public function collection()
    {
        $data = collect();

        $data->push(['BOLETIM ESCOLAR']);
        $data->push(['']);
        $data->push(['Aluno:', $this->aluno->name]);
        $data->push(['Turma:', $this->turma->nome_completo]);
        $data->push(['Curso:', $this->turma->curso->nome]);
        $data->push(['Ano Letivo:', $this->turma->anoLetivo->nome]);
        $data->push(['']);

        foreach ($this->notas as $nota) {
            if ($this->isDecimaTerceiraFinal()) {
                $data->push([
                    $nota->disciplina->nome,
                    $nota->cfd_efetiva !== null ? number_format($nota->cfd_efetiva, 2) : '-',
                    $this->formatarResumo13('pc'),
                    $this->formatarResumo13('classificacao.ecs'),
                    $this->formatarResumo13('classificacao.pap'),
                    $this->formatarResumo13('media_final'),
                    data_get($this->classificacaoEnsinoMedioResumo, 'resultado', 'Pendente'),
                    data_get($this->classificacaoEnsinoMedioResumo, 'classificacao.observacoes', '-'),
                ]);

                continue;
            }

            $data->push([
                $nota->disciplina->nome,
                $nota->mt1 !== null ? number_format($nota->mt1, 2) : '-',
                $nota->mt2 !== null ? number_format($nota->mt2, 2) : '-',
                $nota->mt3 !== null ? number_format($nota->mt3, 2) : '-',
                $nota->cfd_efetiva !== null ? number_format($nota->cfd_efetiva, 2) : '-',
                $nota->recursoPendente() ? 'Em recurso' : ($nota->isAprovado() ? 'Aprovado' : 'Reprovado'),
            ]);
        }

        $data->push(['']);
        $data->push([
            $this->isDecimaTerceiraFinal() ? 'Média Final (MF):' : 'MÉDIA GERAL:',
            number_format(
                (float) ($this->isDecimaTerceiraFinal()
                    ? (data_get($this->classificacaoEnsinoMedioResumo, 'media_final') ?? 0)
                    : $this->mediaGeral),
                2
            ),
        ]);

        return $data;
    }

    public function headings(): array
    {
        if ($this->isDecimaTerceiraFinal()) {
            return [
                'Disciplina',
                'CFD',
                'PC',
                'E.C.S',
                'PAP',
                'Média Final (MF)',
                'Resultado',
                'Observações',
            ];
        }

        return [
            'Disciplina',
            'MT1',
            'MT2',
            'MT3',
            'CFD',
            'Status',
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
        if ($this->isDecimaTerceiraFinal()) {
            return [
                'A' => 30,
                'B' => 10,
                'C' => 10,
                'D' => 10,
                'E' => 10,
                'F' => 16,
                'G' => 15,
                'H' => 28,
            ];
        }

        return [
            'A' => 30,
            'B' => 10,
            'C' => 10,
            'D' => 10,
            'E' => 10,
            'F' => 15,
        ];
    }

    protected function isDecimaTerceiraFinal(): bool
    {
        return (int) $this->turma->classe === 13 && $this->trimestre === 'final';
    }

    protected function formatarResumo13(string $key): string
    {
        $valor = data_get($this->classificacaoEnsinoMedioResumo, $key);

        return is_numeric($valor) ? number_format((float) $valor, 2) : '-';
    }
}
