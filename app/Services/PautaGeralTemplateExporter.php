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
use PhpOffice\PhpSpreadsheet\Style\Style;
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

    private const FOOTER_START = 51;

    private const FOOTER_END = 63;

    private const COL_ORD = 'A';

    private const COL_PROC = 'B';

    private const COL_NOME = 'C';

    private const COL_SEXO = 'D';

    private const COL_FIRST_DISC = 'E';

    private const TEMPLATE_LAST_VISIBLE_COL = 'CI';

    private const POS_FALTAS_J = 0;

    private const POS_FALTAS_I = 1;

    private const HEADER_TEMPLATE_RANGES = [
        'instituicao' => ['start' => 24, 'end' => 57], // X:BE
        'titulo' => ['start' => 24, 'end' => 57],      // X:BE
        'info_direita' => ['start' => 16, 'end' => 70], // P:BR
    ];

    private const SIGNATURE_TEMPLATE_RANGES = [
        'director' => ['start' => 8, 'end' => 22],     // H:V
        'coordenador' => ['start' => 43, 'end' => 58], // AQ:BF
        'subdirector' => ['start' => 77, 'end' => 87], // BY:CI
    ];

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
        $styles = $this->capturarEstilosBase($sheet);

        $footerStart = $this->ajustarEstruturaVertical($sheet, $alunos->count());
        $layout = $this->podarEstruturaHorizontal($sheet, $disciplinas->count());
        $lastDataRow = $footerStart - 1;
        $lastRow = $footerStart + (self::FOOTER_END - self::FOOTER_START);

        $this->limparAreaDeDados($sheet, $lastDataRow, $layout['lastCol']);
        $this->normalizarAreaDeDados($sheet, $lastDataRow, $layout['lastCol']);
        $this->atualizarCabecalho($sheet, $turma, $anoLetivo, $config, $layout['lastCol'], $styles);
        $this->atualizarCabecalhosDeDisciplinas($sheet, $disciplinas, $config, $layout);
        $this->preencherLinhasDosAlunos($sheet, $alunos, $disciplinas, $notasIndex, $config, $layout);
        $this->atualizarRodape($sheet, $turma, $disciplinas, $atribuicoes, $footerStart, $layout, $styles);
        $this->ajustarImpressao($sheet, $lastRow, $lastDataRow, $layout['lastCol']);

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

    private function capturarEstilosBase(Worksheet $sheet): array
    {
        return [
            'instituicao' => clone $sheet->getStyle('X5'),
            'titulo' => clone $sheet->getStyle('X6'),
            'info_direita' => clone $sheet->getStyle('P8'),
            'assinatura_rotulo' => clone $sheet->getStyle('I58'),
            'assinatura_linha' => clone $sheet->getStyle('AG59'),
            'assinatura_nome' => clone $sheet->getStyle('H60'),
        ];
    }

    private function ajustarEstruturaVertical(Worksheet $sheet, int $totalAlunos): int
    {
        $capacidadeBase = self::DATA_END - self::DATA_START + 1;
        $extraRows = max(0, $totalAlunos - $capacidadeBase);
        $rowsToRemove = max(0, $capacidadeBase - $totalAlunos);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(self::FOOTER_START, $extraRows);

            $modeloEstilo = $sheet->getStyle('A'.self::DATA_END.':'.self::TEMPLATE_LAST_VISIBLE_COL.self::DATA_END);
            $alturaModelo = $sheet->getRowDimension(self::DATA_END)->getRowHeight();

            for ($row = self::DATA_END + 1; $row <= self::DATA_END + $extraRows; $row++) {
                $sheet->duplicateStyle($modeloEstilo, 'A'.$row.':'.self::TEMPLATE_LAST_VISIBLE_COL.$row);
                $sheet->getRowDimension($row)->setRowHeight($alturaModelo);
            }
        } elseif ($rowsToRemove > 0) {
            $sheet->removeRow(self::DATA_START + $totalAlunos, $rowsToRemove);
        }

        return self::DATA_START + $totalAlunos;
    }

    private function podarEstruturaHorizontal(Worksheet $sheet, int $disciplinasCount): array
    {
        if ($disciplinasCount < count(self::DISCIPLINE_BLOCKS)) {
            $unusedBlocks = array_slice(self::DISCIPLINE_BLOCKS, $disciplinasCount);
            $firstUnusedCol = $unusedBlocks[0][0];
            $colsToRemove = collect($unusedBlocks)
                ->sum(fn (array $block) => $this->columnsCount($block[0], $block[1]));

            $sheet->removeColumn($firstUnusedCol, $colsToRemove);
        }

        $layout = $this->calcularLayoutHorizontal($disciplinasCount);
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $lastColIndex = Coordinate::columnIndexFromString($layout['lastCol']);

        if ($highestColIndex > $lastColIndex) {
            $sheet->removeColumn(
                Coordinate::stringFromColumnIndex($lastColIndex + 1),
                $highestColIndex - $lastColIndex
            );
        }

        return $layout;
    }

    private function calcularLayoutHorizontal(int $disciplinasCount): array
    {
        $blocks = array_slice(self::DISCIPLINE_BLOCKS, 0, $disciplinasCount);
        $firstDiscIndex = Coordinate::columnIndexFromString(self::COL_FIRST_DISC);
        $usedWidth = collect($blocks)->sum(fn (array $block) => $this->columnsCount($block[0], $block[1]));
        $obsIndex = $firstDiscIndex + $usedWidth;
        $resultIndex = $obsIndex + 1;

        return [
            'disciplineBlocks' => $blocks,
            'obsCol' => Coordinate::stringFromColumnIndex($obsIndex),
            'resultCol' => Coordinate::stringFromColumnIndex($resultIndex),
            'lastCol' => Coordinate::stringFromColumnIndex($resultIndex),
        ];
    }

    private function limparAreaDeDados(Worksheet $sheet, int $lastDataRow, string $lastCol): void
    {
        if ($lastDataRow < self::DATA_START) {
            return;
        }

        $lastColIndex = Coordinate::columnIndexFromString($lastCol);

        for ($row = self::DATA_START; $row <= $lastDataRow; $row++) {
            for ($colIndex = 1; $colIndex <= $lastColIndex; $colIndex++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex).$row, null);
            }
        }
    }

    private function normalizarAreaDeDados(Worksheet $sheet, int $lastDataRow, string $lastCol): void
    {
        if ($lastDataRow < self::DATA_START) {
            return;
        }

        $sheet->getStyle('A'.self::DATA_START.':'.$lastCol.$lastDataRow)
            ->getFill()
            ->setFillType(Fill::FILL_NONE);
    }

    private function atualizarCabecalho(
        Worksheet $sheet,
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        array $config,
        string $lastCol,
        array $styles
    ): void {
        $anoNome = $anoLetivo?->nome ?? ($turma->anoLetivo?->nome ?? date('Y').'/'.(date('Y') + 1));
        $lastColIndex = Coordinate::columnIndexFromString($lastCol);

        $this->desfazerMesclasNasLinhas($sheet, [
            self::ROW_DIRECTOR,
            self::ROW_ASSIN,
            self::ROW_INFO,
        ]);

        $sheet->setCellValue('C'.self::ROW_DIRECTOR, 'O DIRECTOR');
        $sheet->setCellValue('C'.self::ROW_ASSIN, '______________________________');
        $sheet->setCellValue('I'.self::ROW_ASSIN, 'Ano Lectivo');
        $sheet->setCellValue('M'.self::ROW_ASSIN, ':'.$anoNome);
        $sheet->setCellValue('C'.self::ROW_NOME_DIR, $this->nomeDirector());
        $sheet->setCellValue('C'.self::ROW_INFO, 'Data: _____/_____/_________');
        $sheet->setCellValue('F'.self::ROW_INFO, $this->infoTurmaLinhaEsquerda($turma));

        [$instStart, $instEnd] = $this->resolverFaixaTemplate(
            self::HEADER_TEMPLATE_RANGES['instituicao'],
            $lastColIndex,
            6
        );
        [$titleStart, $titleEnd] = $this->resolverFaixaTemplate(
            self::HEADER_TEMPLATE_RANGES['titulo'],
            $lastColIndex,
            6
        );
        [$infoStart, $infoEnd] = $this->resolverFaixaTemplate(
            self::HEADER_TEMPLATE_RANGES['info_direita'],
            $lastColIndex,
            5
        );

        $this->preencherFaixaMesclada(
            $sheet,
            $instStart,
            $instEnd,
            self::ROW_DIRECTOR,
            $this->nomeInstituicao(),
            $styles['instituicao']
        );

        $this->preencherFaixaMesclada(
            $sheet,
            $titleStart,
            $titleEnd,
            self::ROW_ASSIN,
            $config['titulo'],
            $styles['titulo']
        );

        if ($infoStart > Coordinate::columnIndexFromString('F')) {
            $this->preencherFaixaMesclada(
                $sheet,
                $infoStart,
                $infoEnd,
                self::ROW_INFO,
                $this->infoTurmaLinhaDireita($turma),
                $styles['info_direita']
            );
        }
    }

    private function atualizarCabecalhosDeDisciplinas(
        Worksheet $sheet,
        Collection $disciplinas,
        array $config,
        array $layout
    ): void {
        $sheet->setCellValue($layout['obsCol'].self::ROW_HDR2, 'OBSERV.');
        $sheet->setCellValue($layout['resultCol'].self::ROW_HDR2, 'RESULTADO');

        foreach ($layout['disciplineBlocks'] as $idx => [$startCol, $endCol]) {
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
        array $config,
        array $layout
    ): void {
        foreach ($alunos as $offset => $aluno) {
            $row = self::DATA_START + $offset;
            $notasAluno = $notasIndex[$aluno->id] ?? [];

            $sheet->setCellValueExplicit(self::COL_ORD.$row, (string) ($offset + 1), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit(self::COL_PROC.$row, (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::COL_NOME.$row, (string) $aluno->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::COL_SEXO.$row, Str::upper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);

            foreach ($disciplinas->values() as $idx => $disciplina) {
                [$startCol, $endCol] = $layout['disciplineBlocks'][$idx];
                $cols = $this->columnsInRange($startCol, $endCol);
                $nota = $notasAluno[$disciplina->id] ?? null;

                foreach ($config['campos'] as $pos => $campo) {
                    if (! isset($cols[$pos])) {
                        continue;
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

            $sheet->setCellValueExplicit($layout['obsCol'].$row, $obs, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($layout['resultCol'].$row, $resultado, DataType::TYPE_STRING);
        }
    }

    private function atualizarRodape(
        Worksheet $sheet,
        Turma $turma,
        Collection $disciplinas,
        Collection $atribuicoes,
        int $footerStart,
        array $layout,
        array $styles
    ): void {
        $sheet->setCellValue('A'.$footerStart, 'Data do Conselho de Turma');
        $sheet->setCellValue('A'.($footerStart + 1), '_____/______/________');

        foreach ($layout['disciplineBlocks'] as [$startCol]) {
            $sheet->setCellValue($startCol.$footerStart, null);
        }

        foreach ($disciplinas->values() as $idx => $disciplina) {
            [$startCol] = $layout['disciplineBlocks'][$idx];
            $professor = $atribuicoes->get($disciplina->id)?->professor?->name ?? '';
            $sheet->setCellValue($startCol.$footerStart, $professor);
        }

        $signatureRows = [
            'label' => $footerStart + 7,
            'line' => $footerStart + 8,
            'name' => $footerStart + 9,
        ];

        $this->desfazerMesclasNasLinhas($sheet, array_values($signatureRows));

        foreach ($signatureRows as $row) {
            $sheet->fromArray(array_fill(0, Coordinate::columnIndexFromString($layout['lastCol']), null), null, 'A'.$row);
        }

        $assinaturas = [
            'director' => [
                'label' => 'O DIRECTOR DE TURMA',
                'name' => $this->nomeDirectorTurma($turma),
            ],
            'coordenador' => [
                'label' => 'O COORDENADOR DE CURSO',
                'name' => $this->nomeCoordenadorCurso($turma),
            ],
            'subdirector' => [
                'label' => 'O SUBDIRECTOR PEDAGÓGICO',
                'name' => $this->nomeSubdirectorPedagogico(),
            ],
        ];

        foreach ($this->resolverFaixasAssinatura(Coordinate::columnIndexFromString($layout['lastCol'])) as $key => [$start, $end]) {
            $assinatura = $assinaturas[$key];

            $this->preencherFaixaMesclada(
                $sheet,
                $start,
                $end,
                $signatureRows['label'],
                $assinatura['label'],
                $styles['assinatura_rotulo']
            );

            $this->preencherFaixaMesclada(
                $sheet,
                $start,
                $end,
                $signatureRows['line'],
                '____________________________________',
                $styles['assinatura_linha']
            );

            $this->preencherFaixaMesclada(
                $sheet,
                $start,
                $end,
                $signatureRows['name'],
                $assinatura['name'],
                $styles['assinatura_nome']
            );
        }
    }

    private function ajustarImpressao(Worksheet $sheet, int $lastRow, int $lastDataRow, string $lastCol): void
    {
        $sheet->freezePane('A'.self::DATA_START);
        $sheet->getPageSetup()->setPrintArea('A1:'.$lastCol.$lastRow);

        if ($lastDataRow >= self::DATA_START) {
            $sheet->getStyle('A'.self::DATA_START.':'.$lastCol.$lastDataRow)
                ->getFill()
                ->setFillType(Fill::FILL_NONE);
        }
    }

    private function resolverFaixaTemplate(array $range, int $lastColIndex, int $minWidth): array
    {
        $preferredStart = $range['start'];
        $preferredEnd = min($range['end'], $lastColIndex);

        if ($lastColIndex >= $preferredStart && ($preferredEnd - $preferredStart + 1) >= $minWidth) {
            return [$preferredStart, $preferredEnd];
        }

        $templateLastIndex = Coordinate::columnIndexFromString(self::TEMPLATE_LAST_VISIBLE_COL);
        $scaledStart = max(1, (int) round(($range['start'] / $templateLastIndex) * $lastColIndex));
        $scaledEnd = max($scaledStart, (int) round(($range['end'] / $templateLastIndex) * $lastColIndex));

        if (($scaledEnd - $scaledStart + 1) < $minWidth) {
            $scaledStart = max(1, $lastColIndex - $minWidth + 1);
            $scaledEnd = $lastColIndex;
        }

        return [$scaledStart, min($scaledEnd, $lastColIndex)];
    }

    private function preencherFaixaMesclada(
        Worksheet $sheet,
        int $startIndex,
        int $endIndex,
        int $row,
        string $value,
        Style $style
    ): void {
        $startCol = Coordinate::stringFromColumnIndex($startIndex);
        $endCol = Coordinate::stringFromColumnIndex($endIndex);
        $range = $startCol.$row.':'.$endCol.$row;

        if ($startIndex !== $endIndex) {
            $sheet->mergeCells($range);
        }

        $sheet->duplicateStyle($style, $range);
        $sheet->setCellValue($startCol.$row, $value);
    }

    private function resolverFaixasAssinatura(int $lastColIndex): array
    {
        $usableStart = $lastColIndex >= 24 ? 8 : 1;
        $usableWidth = max(3, $lastColIndex - $usableStart + 1);
        $baseWidth = intdiv($usableWidth, 3);
        $remainder = $usableWidth % 3;
        $cursor = $usableStart;
        $keys = array_keys(self::SIGNATURE_TEMPLATE_RANGES);
        $ranges = [];

        foreach ($keys as $idx => $key) {
            $width = $baseWidth + ($idx < $remainder ? 1 : 0);
            $end = $idx === array_key_last($keys)
                ? $lastColIndex
                : max($cursor, $cursor + $width - 1);

            $ranges[$key] = [$cursor, $end];
            $cursor = $end + 1;
        }

        return $ranges;
    }

    private function desfazerMesclasNasLinhas(Worksheet $sheet, array $rows): void
    {
        $rowsMap = array_flip($rows);

        foreach (array_keys($sheet->getMergeCells()) as $range) {
            if (! preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $range, $matches)) {
                continue;
            }

            $startRow = (int) $matches[2];
            $endRow = (int) $matches[4];

            for ($row = $startRow; $row <= $endRow; $row++) {
                if (isset($rowsMap[$row])) {
                    $sheet->unmergeCells($range);

                    break;
                }
            }
        }
    }

    private function columnsCount(string $startCol, string $endCol): int
    {
        return Coordinate::columnIndexFromString($endCol) - Coordinate::columnIndexFromString($startCol) + 1;
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
            '%sÂª Classe   TURMA: %s   AREA: %s   CURSO:%s',
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
                'titulo' => 'PAUTA DE APROVEITAMENTO - IÂº TRIMESTRE',
                'labels' => ['F.J', 'F.I', 'MfT2', 'MAC1', 'NPT1', 'NPT1', 'MT1'],
                'campos' => ['faltas_j', 'faltas_i', 'mft2', 'mac1', 'pp1', 'pt1', 'mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIÂº TRIMESTRE',
                'labels' => ['F.J', 'F.I', 'MfT2', 'MAC2', 'NPT2', 'NPT2', 'MT2'],
                'campos' => ['faltas_j', 'faltas_i', 'mft2', 'mac2', 'pp2', 'pt2', 'mt2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIIÂº TRIMESTRE',
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
            return ['EEF', 'NÃ£o Transita'];
        }

        if (! $temReprovacao) {
            return ['', 'Transita'];
        }

        return [$temExame ? 'Exame' : '', 'NÃ£o Transita'];
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
            'INGLES' => 'INGLÃŠS',
            'EDUCACAO FISICA' => 'ED. FÃSICA',
            'MATEMATICA' => 'MATEMAT.',
            'FISICA' => 'FÃSICA',
            'QUIMICA' => 'QUÃMICA',
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
