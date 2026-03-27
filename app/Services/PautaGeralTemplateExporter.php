<?php

namespace App\Services;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PautaGeralTemplateExporter
{
    private const TEMPLATE_PATH = 'templates/pauta-geral-template.xlsx';
    private const DATA_START_ROW = 15;
    private const TEMPLATE_DATA_ROWS = 36;
    private const FOOTER_START_ROW = 51;
    private const OBS_COLUMN = 'CH';
    private const RESULT_COLUMN = 'CI';

    private const DISCIPLINE_BLOCKS = [
        'E:K',
        'L:R',
        'S:Y',
        'Z:AF',
        'AG:AM',
        'AN:AS',
        'AT:AY',
        'AZ:BF',
        'BG:BM',
        'BN:BS',
        'BT:BZ',
        'CA:CG',
    ];

    public function download(array $dados): BinaryFileResponse
    {
        $spreadsheet = $this->build($dados);

        $tempFile = tempnam(sys_get_temp_dir(), 'pauta-geral-');
        $path = $tempFile . '.xlsx';

        if ($tempFile !== false && file_exists($tempFile)) {
            @unlink($tempFile);
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        $filename = 'pauta-geral-' . Str::slug($dados['turma']->nome, '-') . '.xlsx';

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function build(array $dados): Spreadsheet
    {
        /** @var Turma $turma */
        $turma = $dados['turma'];
        /** @var AnoLetivo|null $anoLetivo */
        $anoLetivo = $dados['anoLetivo'] ?? null;
        $trimestre = (string) ($dados['trimestre'] ?? 'final');

        $turma->loadMissing(['curso.coordenador', 'coordenador', 'disciplinas']);

        $anoLetivo ??= $turma->anoLetivo;

        /** @var EloquentCollection $alunos */
        $alunos = $turma->alunos()
            ->wherePivot('status', 'matriculado')
            ->orderBy('name')
            ->get();

        $notas = $this->resolveNotas($turma, $anoLetivo, $dados);
        $notasIndex = $this->indexarNotas($notas);
        $disciplinas = $this->ordenarDisciplinas($turma->disciplinas)->values();

        if ($disciplinas->count() > count(self::DISCIPLINE_BLOCKS)) {
            throw new \RuntimeException('O template suporta no máximo 12 disciplinas por pauta geral.');
        }

        $atribuicoes = $turma->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
            ->with(['professor', 'disciplina'])
            ->get()
            ->keyBy('disciplina_id');

        $spreadsheet = IOFactory::load(resource_path(self::TEMPLATE_PATH));
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pauta Geral');

        $config = $this->periodoConfig($trimestre);
        $lastDataRow = $this->prepareStudentRows($sheet, $alunos->count());

        $this->preencherCabecalho($sheet, $turma, $anoLetivo, $config);
        $this->preencherDisciplinas($sheet, $disciplinas, $config, $atribuicoes);
        $this->limparAreaDeDados($sheet, $lastDataRow);
        $this->preencherAlunos($sheet, $alunos, $disciplinas, $notasIndex, $config);
        $this->preencherRodape($sheet, $turma);

        return $spreadsheet;
    }

    private function resolveNotas(Turma $turma, ?AnoLetivo $anoLetivo, array $dados): Collection
    {
        $notas = collect();

        if (isset($dados['notas']) && $dados['notas'] instanceof Collection) {
            $notas = $dados['notas'];
        } elseif (isset($dados['notasPorDisciplina']) && $dados['notasPorDisciplina'] instanceof Collection) {
            $notas = $dados['notasPorDisciplina']->flatten(1);
        }

        if ($notas->isEmpty()) {
            $notas = $turma->notas()
                ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
                ->with(['aluno', 'disciplina'])
                ->get();
        }

        if ($notas instanceof EloquentCollection) {
            $notas->loadMissing(['aluno', 'disciplina']);
        }

        return $notas->values();
    }

    private function indexarNotas(Collection $notas): array
    {
        $index = [];

        foreach ($notas as $nota) {
            $index[$nota->aluno_id][$nota->disciplina_id] = $nota;
        }

        return $index;
    }

    private function periodoConfig(string $trimestre): array
    {
        return match ($trimestre) {
            '1' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - Iº TRIMESTRE',
                'subtitulo' => 'Iº TRIMESTRE',
                'labels' => ['MAC1', 'PP1', 'PT1', 'MT1'],
                'campos' => ['mac1', 'pp1', 'pt1', 'mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIº TRIMESTRE',
                'subtitulo' => 'IIº TRIMESTRE',
                'labels' => ['MAC2', 'PP2', 'PT2', 'MT2', 'MFT2'],
                'campos' => ['mac2', 'pp2', 'pt2', 'mt2', 'mft2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIIº TRIMESTRE',
                'subtitulo' => 'IIIº TRIMESTRE',
                'labels' => ['MAC3', 'PP3', 'MT3', 'CF', 'PG', 'CA'],
                'campos' => ['mac3', 'pp3', 'mt3', 'cf', 'pg', 'ca'],
                'mostrarResultado' => false,
            ],
            default => [
                'titulo' => 'PAUTA GERAL DO ANO LETIVO',
                'subtitulo' => 'RESULTADOS FINAIS',
                'labels' => ['MT1', 'MT2', 'MT3', 'PG', 'CA', 'CFD'],
                'campos' => ['mt1', 'mt2', 'mt3', 'pg', 'ca', 'cfd'],
                'mostrarResultado' => true,
            ],
        };
    }

    private function prepareStudentRows(Worksheet $sheet, int $studentCount): int
    {
        $rowsNeeded = max($studentCount, self::TEMPLATE_DATA_ROWS);
        $extraRows = $rowsNeeded - self::TEMPLATE_DATA_ROWS;
        $lastTemplateRow = self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS - 1;

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(self::FOOTER_START_ROW, $extraRows);

            for ($offset = 0; $offset < $extraRows; $offset++) {
                $row = self::FOOTER_START_ROW + $offset;
                $sheet->duplicateStyle(
                    $sheet->getStyle("A{$lastTemplateRow}:" . self::RESULT_COLUMN . "{$lastTemplateRow}"),
                    "A{$row}:" . self::RESULT_COLUMN . "{$row}"
                );
                $sheet->getRowDimension($row)
                    ->setRowHeight($sheet->getRowDimension($lastTemplateRow)->getRowHeight());
            }
        }

        return self::DATA_START_ROW + $rowsNeeded - 1;
    }

    private function preencherCabecalho(Worksheet $sheet, Turma $turma, ?AnoLetivo $anoLetivo, array $config): void
    {
        $sheet->setCellValue('C7', '');
        $sheet->setCellValue('I6', 'Ano Lectivo');
        $sheet->setCellValue('M6', ':' . ($anoLetivo?->nome ?? $turma->anoLetivo?->nome ?? ''));
        $sheet->setCellValue('X6', $config['titulo']);
        $sheet->setCellValue('C8', 'Data: ' . now()->format('d/m/Y'));

        $turmaLabel = sprintf(
            '%sª Classe   TURMA: %s                               ÁREA: %s                      CURSO: %s',
            $turma->classe,
            $turma->nome,
            $turma->curso->nome,
            $turma->curso->nome
        );

        $codigo = Str::upper((string) ($turma->curso->codigo ?? 'TURMA')) . $turma->classe . Str::upper($turma->nome);
        $cursoLabel = sprintf(
            '%s            PERÍODO: %s             CURSO: %s',
            $codigo,
            $config['subtitulo'],
            Str::upper($turma->curso->nome)
        );

        $sheet->setCellValue('F8', $turmaLabel);
        $sheet->setCellValue('P8', $cursoLabel);
    }

    private function preencherDisciplinas(
        Worksheet $sheet,
        Collection $disciplinas,
        array $config,
        Collection $atribuicoes
    ): void {
        foreach (self::DISCIPLINE_BLOCKS as $index => $range) {
            [$startCol, $endCol] = explode(':', $range);
            $allColumns = $this->columnsInRange($startCol, $endCol);
            $visibleColumns = $this->visibleColumns($sheet, $allColumns);
            $disciplina = $disciplinas->get($index);

            foreach ($allColumns as $column) {
                $sheet->setCellValue("{$column}13", '');
                $sheet->setCellValue("{$column}14", '');
            }

            if (!$disciplina) {
                $sheet->setCellValue("{$startCol}12", '');

                foreach ($allColumns as $column) {
                    $sheet->getColumnDimension($column)->setVisible(false);
                }

                $sheet->setCellValue("{$startCol}51", '');
                continue;
            }

            $sheet->setCellValue("{$startCol}12", $this->abreviarDisciplina($disciplina));

            foreach ($config['labels'] as $position => $label) {
                $column = $visibleColumns[$position] ?? null;

                if (!$column) {
                    break;
                }

                $sheet->setCellValue("{$column}13", $label);
            }

            $professor = $atribuicoes->get($disciplina->id)?->professor?->name ?? '';
            $sheet->setCellValue("{$startCol}51", $professor);
        }

        $sheet->setCellValue(self::OBS_COLUMN . '13', '');
        $sheet->setCellValue(self::OBS_COLUMN . '14', 'OBSERV.');
        $sheet->setCellValue(self::RESULT_COLUMN . '13', '');
        $sheet->setCellValue(self::RESULT_COLUMN . '14', 'RESULTADO');
    }

    private function limparAreaDeDados(Worksheet $sheet, int $lastDataRow): void
    {
        $lastColumnIndex = Coordinate::columnIndexFromString(self::RESULT_COLUMN);

        for ($row = self::DATA_START_ROW; $row <= $lastDataRow; $row++) {
            for ($columnIndex = 1; $columnIndex <= $lastColumnIndex; $columnIndex++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . $row, null);
            }
        }
    }

    private function preencherAlunos(
        Worksheet $sheet,
        EloquentCollection $alunos,
        Collection $disciplinas,
        array $notasIndex,
        array $config
    ): void {
        foreach ($alunos->values() as $offset => $aluno) {
            $row = self::DATA_START_ROW + $offset;

            $sheet->setCellValueExplicit("A{$row}", (string) ($offset + 1), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$row}", (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", $aluno->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", strtoupper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);

            $notasAluno = $notasIndex[$aluno->id] ?? [];

            foreach ($disciplinas->values() as $index => $disciplina) {
                [$startCol, $endCol] = explode(':', self::DISCIPLINE_BLOCKS[$index]);
                $visibleColumns = $this->visibleColumns($sheet, $this->columnsInRange($startCol, $endCol));
                $nota = $notasAluno[$disciplina->id] ?? null;

                foreach ($visibleColumns as $position => $column) {
                    $campo = $config['campos'][$position] ?? null;
                    $value = $campo ? $this->notaValor($nota, $campo) : null;

                    if ($value === null) {
                        $sheet->setCellValue("{$column}{$row}", '');
                        continue;
                    }

                    $sheet->setCellValueExplicit("{$column}{$row}", (string) $value, DataType::TYPE_NUMERIC);
                    $sheet->getStyle("{$column}{$row}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                }
            }

            [$observacao, $resultado] = $this->resolverResultadoAluno($disciplinas, $notasAluno, $config['mostrarResultado']);

            $sheet->setCellValueExplicit(self::OBS_COLUMN . $row, $observacao, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::RESULT_COLUMN . $row, $resultado, DataType::TYPE_STRING);

            $sheet->getStyle(self::OBS_COLUMN . $row . ':' . self::RESULT_COLUMN . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
    }

    private function preencherRodape(Worksheet $sheet, Turma $turma): void
    {
        $sheet->setCellValue('A52', now()->format('d/m/Y'));
        $sheet->setCellValue('H60', $turma->coordenador?->name ?? '');
        $sheet->setCellValue('AQ60', $turma->curso->coordenador?->name ?? '');
        $sheet->setCellValue('BY60', '');
    }

    private function resolverResultadoAluno(Collection $disciplinas, array $notasAluno, bool $mostrarResultado): array
    {
        if (!$mostrarResultado) {
            return ['', ''];
        }

        $temNota = false;
        $temPendente = false;
        $temReprovacao = false;
        $temExame = false;

        foreach ($disciplinas as $disciplina) {
            $nota = $notasAluno[$disciplina->id] ?? null;
            $valor = $nota?->cfd;

            if ($valor === null) {
                $temPendente = true;
                continue;
            }

            $temNota = true;

            if ((float) $valor < 10) {
                $temReprovacao = true;

                if ($disciplina->disciplina_terminal) {
                    $temExame = true;
                }
            }
        }

        if (!$temNota || $temPendente) {
            return ['', ''];
        }

        if (!$temReprovacao) {
            return ['', 'Transita'];
        }

        return [$temExame ? 'Exame' : '', 'Não Transita'];
    }

    private function notaValor(mixed $nota, string $campo): ?float
    {
        if (!$nota || $nota->{$campo} === null) {
            return null;
        }

        return (float) $nota->{$campo};
    }

    private function columnsInRange(string $startCol, string $endCol): array
    {
        $start = Coordinate::columnIndexFromString($startCol);
        $end = Coordinate::columnIndexFromString($endCol);
        $columns = [];

        for ($column = $start; $column <= $end; $column++) {
            $columns[] = Coordinate::stringFromColumnIndex($column);
        }

        return $columns;
    }

    private function visibleColumns(Worksheet $sheet, array $columns): array
    {
        return array_values(array_filter($columns, function (string $column) use ($sheet) {
            return $sheet->getColumnDimension($column)->getVisible();
        }));
    }

    private function ordenarDisciplinas(Collection $disciplinas): Collection
    {
        $ordem = [
            'LINGUA PORTUGUESA' => 10,
            'PORTUGUES' => 10,
            'INGLES' => 20,
            'FILOSOFIA' => 30,
            'HISTORIA' => 40,
            'GEOGRAFIA' => 50,
            'EDUCACAO FISICA' => 60,
            'MATEMATICA' => 70,
            'FISICA' => 80,
            'QUIMICA' => 90,
            'BIOLOGIA' => 100,
            'EMPREENDEDORISMO' => 110,
            'TIC' => 120,
        ];

        return $disciplinas->sortBy(function (Disciplina $disciplina) use ($ordem) {
            $normalized = $this->normalize($disciplina->nome);
            $rank = $ordem[$normalized] ?? 999;

            return str_pad((string) $rank, 4, '0', STR_PAD_LEFT) . '-' . $normalized;
        });
    }

    private function abreviarDisciplina(Disciplina $disciplina): string
    {
        $aliases = [
            'LINGUA PORTUGUESA' => 'L. PORTUGUESA',
            'INGLES' => 'INGLÊS',
            'EDUCACAO FISICA' => 'ED. FÍSICA',
            'MATEMATICA' => 'MATEMAT.',
            'FISICA' => 'FÍSICA',
            'QUIMICA' => 'QUÍMICA',
            'EMPREENDEDORISMO' => 'EMPREEND.',
        ];

        $normalized = $this->normalize($disciplina->nome);

        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        return Str::upper(Str::limit($disciplina->nome, 18, ''));
    }

    private function normalize(string $value): string
    {
        return Str::upper(Str::ascii($value));
    }
}
