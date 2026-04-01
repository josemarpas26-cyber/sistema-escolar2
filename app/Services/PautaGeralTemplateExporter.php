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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PautaGeralTemplateExporter
{
    private const TEMPLATE_PATH = 'resources/templates/pauta-geral-template.xlsx';

    private const ROW_DIRECTOR = 5;

    private const ROW_ASSIN = 6;

    private const ROW_NOME_DIR = 7;

    private const ROW_INFO = 8;

    private const ROW_DISC = 12;

    private const ROW_HDR1 = 13;

    private const ROW_HDR2 = 14;

    private const DATA_START = 15;

    private const DATA_END = 50;

    private const GUIDE_ROW = 48;

    private const FOOTER_START = 51;

    private const FOOTER_END = 63;

    private const COL_ORD = 'A';

    private const COL_PROC = 'B';

    private const COL_NOME = 'C';

    private const COL_SEXO = 'D';

    private const COL_OBS = 'CH';

    private const COL_RESULT = 'CI';

    private const COL_LAST_DATA = 'CI';

    private const COL_LAST_TEMPLATE = 'DR';

    private const POS_FALTAS_J = 0;

    private const POS_FALTAS_I = 1;

    private const DISCIPLINE_BLOCKS = [
        ['E', 'K'], ['L', 'R'], ['S', 'Y'], ['Z', 'AF'],
        ['AG', 'AM'], ['AN', 'AS'], ['AT', 'AY'], ['AZ', 'BF'],
        ['BG', 'BM'], ['BN', 'BS'], ['BT', 'BZ'], ['CA', 'CG'],
    ];

    public function download(array $dados): BinaryFileResponse
    {
        $spreadsheet = $this->build($dados);
        $path = null;

        try {
            $path = tempnam(sys_get_temp_dir(), 'pauta-geral-').'.xlsx';
            $filename = 'pauta-'.Str::slug(
                $dados['turma']->nome_completo ?? $dados['turma']->nome,
                '-'
            ).'.xlsx';

            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

            return response()
                ->download($path, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }

            throw $e;
        }
    }

    public function build(array $dados): Spreadsheet
    {
        /** @var Turma $turma */
        $turma = $dados['turma'];
        $anoLetivo = $dados['anoLetivo'] ?? null;
        $trimestre = (string) ($dados['trimestre'] ?? 'final');

        $turma->loadMissing(['curso.coordenador', 'coordenador', 'disciplinas']);
        $anoLetivo ??= $turma->anoLetivo;

        $alunos = $turma->alunos()
            ->wherePivot('status', 'matriculado')
            ->orderBy('name')
            ->get();

        $disciplinas = $this->ordenarDisciplinas($turma->disciplinas)->values();

        if ($disciplinas->count() > count(self::DISCIPLINE_BLOCKS)) {
            throw new \RuntimeException(
                'O template suporta no maximo '.count(self::DISCIPLINE_BLOCKS).' disciplinas por pauta geral.'
            );
        }

        $notas = $this->resolveNotas($turma, $anoLetivo, $dados);
        $notasIndex = $this->indexarNotas($notas);
        $atribuicoes = $turma->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
            ->with(['professor', 'disciplina'])
            ->get()
            ->keyBy('disciplina_id');

        $config = $this->periodoConfig($trimestre);
        $spreadsheet = $this->loadTemplateSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $extraRows = $this->expandirAreaDeDadosSeNecessario($sheet, $alunos->count());
        $footerStart = self::FOOTER_START + $extraRows;
        $lastDataRow = max(self::DATA_START, self::DATA_START + $alunos->count() - 1);
        $dataEnd = self::DATA_END + $extraRows;

        $this->limparAreaDeDados($sheet, $dataEnd);
        $this->removerLinhaGuiaAmarela($sheet, $dataEnd);
        $this->atualizarCabecalho($sheet, $turma, $anoLetivo, $config);
        $this->atualizarCabecalhosDeDisciplinas($sheet, $disciplinas, $config);
        $this->preencherLinhasDosAlunos($sheet, $alunos, $disciplinas, $notasIndex, $config);
        $this->atualizarRodape($sheet, $turma, $disciplinas, $atribuicoes, $footerStart);
        $this->ajustarImpressao($sheet, self::FOOTER_END + $extraRows, $lastDataRow);

        return $spreadsheet;
    }

    private function loadTemplateSpreadsheet(): Spreadsheet
    {
        $templatePath = base_path(self::TEMPLATE_PATH);

        if (! file_exists($templatePath)) {
            throw new \RuntimeException('Template da pauta geral nao encontrado em '.$templatePath);
        }

        return IOFactory::load($templatePath);
    }

    private function expandirAreaDeDadosSeNecessario(Worksheet $sheet, int $totalAlunos): int
    {
        $capacidadeBase = self::DATA_END - self::DATA_START + 1;
        $extraRows = max(0, $totalAlunos - $capacidadeBase);

        if ($extraRows === 0) {
            return 0;
        }

        $sheet->insertNewRowBefore(self::FOOTER_START, $extraRows);

        $modeloEstilo = $sheet->getStyle('A'.self::DATA_END.':'.self::COL_LAST_DATA.self::DATA_END);
        $alturaModelo = $sheet->getRowDimension(self::DATA_END)->getRowHeight();

        for ($row = self::DATA_END + 1; $row <= self::DATA_END + $extraRows; $row++) {
            $sheet->duplicateStyle($modeloEstilo, 'A'.$row.':'.self::COL_LAST_DATA.$row);
            $sheet->getRowDimension($row)->setRowHeight($alturaModelo);
        }

        return $extraRows;
    }

    private function limparAreaDeDados(Worksheet $sheet, int $dataEnd): void
    {
        $lastColIndex = Coordinate::columnIndexFromString(self::COL_LAST_DATA);

        for ($row = self::DATA_START; $row <= $dataEnd; $row++) {
            for ($colIndex = 1; $colIndex <= $lastColIndex; $colIndex++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex).$row, null);
            }
        }
    }

    private function removerLinhaGuiaAmarela(Worksheet $sheet, int $dataEnd): void
    {
        $sheet->getStyle('A'.self::DATA_START.':CG'.$dataEnd)
            ->getFill()
            ->setFillType(Fill::FILL_NONE);

        $sheet->getStyle('A'.self::GUIDE_ROW.':CG'.self::GUIDE_ROW)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID);

        $sheet->getStyle('A'.self::GUIDE_ROW.':CG'.self::GUIDE_ROW)
            ->getFill()
            ->getStartColor()
            ->setRGB('FFFFFF');
    }

    private function atualizarCabecalho(
        Worksheet $sheet,
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        array $config
    ): void {
        $anoNome = $anoLetivo?->nome ?? ($turma->anoLetivo?->nome ?? date('Y').'/'.(date('Y') + 1));

        $sheet->setCellValue('C'.self::ROW_DIRECTOR, 'O DIRECTOR');
        $sheet->setCellValue('X'.self::ROW_DIRECTOR, $this->nomeInstituicao());
        $sheet->setCellValue('C'.self::ROW_ASSIN, '______________________________');
        $sheet->setCellValue('I'.self::ROW_ASSIN, 'Ano Lectivo');
        $sheet->setCellValue('M'.self::ROW_ASSIN, ':'.$anoNome);
        $sheet->setCellValue('X'.self::ROW_ASSIN, $config['titulo']);
        $sheet->setCellValue('C'.self::ROW_NOME_DIR, $this->nomeDirector());
        $sheet->setCellValue('C'.self::ROW_INFO, 'Data: _____/_____/_________');
        $sheet->setCellValue('F'.self::ROW_INFO, $this->infoTurmaLinhaEsquerda($turma));
        $sheet->setCellValue('P'.self::ROW_INFO, $this->infoTurmaLinhaDireita($turma));
    }

    private function atualizarCabecalhosDeDisciplinas(
        Worksheet $sheet,
        Collection $disciplinas,
        array $config
    ): void {
        $sheet->setCellValue(self::COL_OBS.self::ROW_HDR2, 'OBSERV.');
        $sheet->setCellValue(self::COL_RESULT.self::ROW_HDR2, 'RESULTADO');

        foreach (self::DISCIPLINE_BLOCKS as $idx => [$startCol, $endCol]) {
            $disciplina = $disciplinas->get($idx);
            $cols = $this->columnsInRange($startCol, $endCol);

            $sheet->setCellValue($startCol.self::ROW_DISC, $disciplina ? $this->abreviarDisciplina($disciplina) : '');
            $sheet->setCellValue($cols[self::POS_FALTAS_J].self::ROW_HDR1, 'FALTAS');
            $sheet->setCellValue($cols[self::POS_FALTAS_J].self::ROW_HDR2, 'J');
            $sheet->setCellValue($cols[self::POS_FALTAS_I].self::ROW_HDR2, 'I');

            for ($pos = 2; $pos < count($cols); $pos++) {
                $sheet->setCellValue($cols[$pos].self::ROW_HDR1, $config['labels'][$pos] ?? '');
            }
        }
    }

    private function preencherLinhasDosAlunos(
        Worksheet $sheet,
        EloquentCollection $alunos,
        Collection $disciplinas,
        array $notasIndex,
        array $config
    ): void {
        foreach ($alunos as $offset => $aluno) {
            $row = self::DATA_START + $offset;
            $notasAluno = $notasIndex[$aluno->id] ?? [];

            $sheet->setCellValueExplicit(self::COL_ORD.$row, (string) ($offset + 1), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit(self::COL_PROC.$row, (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::COL_NOME.$row, (string) $aluno->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::COL_SEXO.$row, Str::upper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);

            foreach ($disciplinas->values() as $idx => $disciplina) {
                [$startCol, $endCol] = self::DISCIPLINE_BLOCKS[$idx];
                $cols = $this->columnsInRange($startCol, $endCol);
                $nota = $notasAluno[$disciplina->id] ?? null;

                foreach ($config['campos'] as $pos => $campo) {
                    if (!isset($cols[$pos])) {
                        continue; // or break if you prefer
                    }

                    $cell = $cols[$pos].$row;
                    $valor = $this->resolverValorCelula($nota, $campo);

                    if ($valor === null || $valor === '') {
                        $sheet->setCellValue($cell, null);

                        continue;
                    }

                    if (is_numeric($valor)) {
                        $sheet->setCellValueExplicit($cell, (string) $valor, DataType::TYPE_NUMERIC);

                        continue;
                    }

                    $sheet->setCellValueExplicit($cell, (string) $valor, DataType::TYPE_STRING);
                }
            }

            [$obs, $resultado] = $this->resolverResultadoAluno(
                $disciplinas,
                $notasAluno,
                $config['mostrarResultado'] ?? false
            );

            $sheet->setCellValueExplicit(self::COL_OBS.$row, $obs, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::COL_RESULT.$row, $resultado, DataType::TYPE_STRING);
        }
    }

    private function atualizarRodape(
        Worksheet $sheet,
        Turma $turma,
        Collection $disciplinas,
        Collection $atribuicoes,
        int $footerStart
    ): void {
        $sheet->setCellValue('A'.$footerStart, 'Data do Conselho de Turma');
        $sheet->setCellValue('A'.($footerStart + 1), '_____/______/________');

        foreach (self::DISCIPLINE_BLOCKS as [$startCol]) {
            $sheet->setCellValue($startCol.$footerStart, null);
        }

        foreach ($disciplinas->values() as $idx => $disciplina) {
            [$startCol] = self::DISCIPLINE_BLOCKS[$idx];
            $professor = $atribuicoes->get($disciplina->id)?->professor?->name ?? '';
            $sheet->setCellValue($startCol.$footerStart, $professor);
        }

        $sheet->setCellValue('H'.($footerStart + 9), $this->nomeDirectorTurma($turma));
        $sheet->setCellValue('AQ'.($footerStart + 9), $this->nomeCoordenadorCurso($turma));
        $sheet->setCellValue('BY'.($footerStart + 9), $this->nomeSubdirectorPedagogico());
    }

    private function ajustarImpressao(Worksheet $sheet, int $lastRow, int $lastDataRow): void
    {
        $sheet->freezePane('A'.self::DATA_START);
        $sheet->getPageSetup()->setPrintArea('A1:'.self::COL_LAST_TEMPLATE.$lastRow);

        if ($lastDataRow >= self::DATA_START) {
            $sheet->getStyle('A'.self::DATA_START.':'.self::COL_LAST_DATA.$lastDataRow)
                ->getFill()
                ->setFillType(Fill::FILL_NONE);
        }
    }

    private function cfg(string $key, string $fallback = ''): string
    {
        return config("escola.{$key}", $fallback);
    }

    private function nomeInstituicao(): string
    {
        return $this->cfg(
            'nome_instituicao',
            'INSTITUTO POLITECNICO INDUSTRIAL DO KILAMBA KIAXI N 8056 "NOVA VIDA"'
        );
    }

    private function nomeDirector(): string
    {
        return $this->cfg('nome_director', 'Ferreira Manuel Fragoso  Ph,D');
    }

    private function nomeSubdirectorPedagogico(): string
    {
        return $this->cfg('nome_subdirector_pedagogico', 'Carlos Alberto Brito Teixeira da Silva');
    }

    private function nomeDirectorTurma(Turma $turma): string
    {
        return $turma->coordenador?->name
            ?? $this->cfg('director_turma_fallback', 'Benjamim Mboloquele');
    }

    private function nomeCoordenadorCurso(Turma $turma): string
    {
        return $turma->curso?->coordenador?->name
            ?? $this->cfg('coordenador_curso_fallback', 'Manuel Goncalves Victor');
    }

    private function infoTurmaLinhaEsquerda(Turma $turma): string
    {
        return sprintf(
            '%sª Classe   TURMA: %s   AREA: %s   CURSO:%s',
            $turma->classe ?? '',
            $this->codigoTurma($turma),
            $this->cursoOuArea($turma),
            Str::upper($turma->curso?->nome ?? '')
        );
    }

    private function infoTurmaLinhaDireita(Turma $turma): string
    {
        return sprintf(
            '%s            SALA:%s                  AREA: %s             CURSO: %s',
            $this->codigoTurma($turma),
            data_get($turma, 'sala', '08'),
            $this->cursoOuArea($turma),
            Str::upper($turma->curso?->codigo ?? $turma->curso?->nome ?? '')
        );
    }

    private function codigoTurma(Turma $turma): string
    {
        $prefixo = Str::upper((string) ($turma->curso?->codigo ?? ''));
        $sufixo = Str::upper((string) ($turma->nome ?? ''));

        return trim($prefixo.$turma->classe.$sufixo);
    }

    private function cursoOuArea(Turma $turma): string
    {
        return Str::upper($turma->curso?->nome ?? 'INFORMATICA');
    }

    private function resolveNotas(Turma $turma, ?AnoLetivo $anoLetivo, array $dados): Collection
    {
        if (isset($dados['notas']) && $dados['notas'] instanceof Collection) {
            return $dados['notas']->values();
        }

        if (isset($dados['notasPorDisciplina']) && $dados['notasPorDisciplina'] instanceof Collection) {
            return $dados['notasPorDisciplina']->flatten(1)->values();
        }

        return $turma->notas()
            ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
            ->with(['aluno', 'disciplina'])
            ->get()
            ->values();
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
                'labels' => ['F.J', 'F.I', 'MfT2', 'MAC1', 'NPT1', 'NPT1', 'MT1'],
                'campos' => ['faltas_j', 'faltas_i', 'mft2', 'mac1', 'pp1', 'pt1', 'mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIº TRIMESTRE',
                'labels' => ['F.J', 'F.I', 'MfT2', 'MAC2', 'NPT2', 'NPT2', 'MT2'],
                'campos' => ['faltas_j', 'faltas_i', 'mft2', 'mac2', 'pp2', 'pt2', 'mt2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIIº TRIMESTRE',
                'labels' => ['F.J', 'F.I', 'MfT2', 'MAC3', 'NPT2', 'PG', 'MT3'],
                'campos' => ['faltas_j', 'faltas_i', 'mft2', 'mac3', 'pp3', 'pg', 'mt3'],
                'mostrarResultado' => false,
            ],
            default => [
                'titulo' => 'PAUTA GERAL DO ANO LETIVO',
                'labels' => ['F.J', 'F.I', 'CF', 'MT1', 'MT2', 'PG', 'CFD'],
                'campos' => ['faltas_j', 'faltas_i', 'cf', 'mt1', 'mt2', 'pg', 'cfd'],
                'mostrarResultado' => true,
            ],
        };
    }

    private function resolverValorCelula(?object $nota, ?string $campo): mixed
    {
        if ($campo === null || in_array($campo, ['faltas_j', 'faltas_i'], true)) {
            return null;
        }

        return $nota?->{$campo};
    }

    private function resolverResultadoAluno(
        Collection $disciplinas,
        array $notasAluno,
        bool $mostrarResultado
    ): array {
        if (! $mostrarResultado) {
            return ['', ''];
        }

        $temNota = false;
        $temPendente = false;
        $temReprovacao = false;
        $temExame = false;
        $temEEF = false;

        foreach ($disciplinas as $disciplina) {
            $nota = $notasAluno[$disciplina->id] ?? null;
            $cf = $nota?->cf;
            $cfd = $nota?->cfd;

            if (is_string($cf) && strtoupper($cf) === 'EEF') {
                $temEEF = true;

                continue;
            }

            if ($cfd === null && $cf === null) {
                $temPendente = true;

                continue;
            }

            $temNota = true;
            $valor = $cfd ?? $cf;

            if ($valor !== null && (float) $valor < 10) {
                $temReprovacao = true;

                if ($disciplina->disciplina_terminal) {
                    $temExame = true;
                }
            }
        }

        if (! $temNota || $temPendente) {
            return ['', ''];
        }

        if ($temEEF) {
            return ['EEF', 'Não Transita'];
        }

        if (! $temReprovacao) {
            return ['', 'Transita'];
        }

        return [$temExame ? 'Exame' : '', 'Não Transita'];
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
            'INFORMATICA' => 120,
            'ELECTROTECNIA' => 130,
            'TLP' => 140,
            'SEAC' => 150,
            'FAI' => 160,
        ];

        return $disciplinas->sortBy(function (Disciplina $disciplina) use ($ordem) {
            $nome = $this->normalize($disciplina->nome);

            return str_pad((string) ($ordem[$nome] ?? 999), 4, '0', STR_PAD_LEFT).'-'.$nome;
        });
    }

    private function abreviarDisciplina(Disciplina $disciplina): string
    {
        $aliases = [
            'LINGUA PORTUGUESA' => 'L. PORTUGUESA',
            'PORTUGUES' => 'L. PORTUGUESA',
            'INGLES' => 'INGLÊS',
            'EDUCACAO FISICA' => 'ED. FÍSICA',
            'MATEMATICA' => 'MATEMAT.',
            'FISICA' => 'FÍSICA',
            'QUIMICA' => 'QUÍMICA',
            'ELECTROTECNIA' => 'ELECTROTECNIA',
            'EMPREENDEDORISMO' => 'EMPREEND.',
            'INFORMATICA' => 'TIC',
            'TECNOLOGIAS INFORMACAO' => 'TIC',
            'FORMACAO DE ATITUDES INTEGRADORAS' => 'F.A.I',
            'FAI' => 'F.A.I',
            'TECNICAS DE LINGUAGEM DE PROGRAMACAO' => 'T.L.P',
            'TLP' => 'T.L.P',
            'SISTEMAS DE EXPLORACAO E APLICACOES' => 'SEAC',
        ];

        $normalized = $this->normalize($disciplina->nome);

        return $aliases[$normalized] ?? Str::upper(Str::limit($disciplina->nome, 14, ''));
    }

    private function normalize(string $value): string
    {
        return Str::upper(Str::ascii($value));
    }

    private function columnsInRange(string $startCol, string $endCol): array
    {
        $start = Coordinate::columnIndexFromString($startCol);
        $end = Coordinate::columnIndexFromString($endCol);
        $cols = [];

        for ($column = $start; $column <= $end; $column++) {
            $cols[] = Coordinate::stringFromColumnIndex($column);
        }

        return $cols;
    }
}
