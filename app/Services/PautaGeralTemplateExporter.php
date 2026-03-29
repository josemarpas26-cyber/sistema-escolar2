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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PautaGeralTemplateExporter
 *
 * Gera a pauta geral da turma com layout idêntico ao ficheiro oficial.
 *
 * Dados institucionais configuráveis em config/escola.php:
 *   'nome_instituicao', 'nome_director', 'nome_subdirector_pedagogico',
 *   'director_turma_fallback', 'coordenador_curso_fallback'
 */
class PautaGeralTemplateExporter
{
    // ── Linhas fixas ──────────────────────────────────────────────────────
    private const ROW_DIRECTOR = 5;
    private const ROW_ASSIN    = 6;
    private const ROW_NOME_DIR = 7;
    private const ROW_INFO     = 8;
    private const ROW_SPACER9  = 9;
    private const ROW_SPACER10 = 10;
    private const ROW_SPACER11 = 11;
    private const ROW_DISC     = 12;
    private const ROW_HDR1     = 13;
    private const ROW_HDR2     = 14;
    private const DATA_START   = 15;

    // ── Colunas fixas ─────────────────────────────────────────────────────
    private const COL_ORD    = 'A';
    private const COL_PROC   = 'B';
    private const COL_NOME   = 'C';
    private const COL_SEXO   = 'D';
    private const COL_OBS    = 'CH';
    private const COL_RESULT = 'CI';

    // ── Posições semânticas dentro do bloco de 7 colunas ──────────────────
    private const POS_FALTAS_J = 0;
    private const POS_FALTAS_I = 1;
    private const POS_MT       = 6; // última coluna — sempre bold

    // ── Blocos de 7 colunas por disciplina (máx. 12) ──────────────────────
    private const DISCIPLINE_BLOCKS = [
        ['E',  'K'],  ['L',  'R'],  ['S',  'Y'],  ['Z',  'AF'],
        ['AG', 'AM'], ['AN', 'AS'], ['AT', 'AY'], ['AZ', 'BF'],
        ['BG', 'BM'], ['BN', 'BS'], ['BT', 'BZ'], ['CA', 'CG'],
    ];

    // ── Larguras ──────────────────────────────────────────────────────────
    private const COL_WIDTHS_FIXED = [
        'A' => 4.29,  'B' => 7.00,  'C' => 37.43, 'D' => 3.29,
    ];
    private const BLOCK_COL_WIDTHS = [2.0, 1.8, 3.0, 4.5, 4.5, 5.0, 4.5];

    // ── Cores ─────────────────────────────────────────────────────────────
    private const CLR_DISC_BG   = 'D9D9D9';
    private const CLR_BORDER    = '000000';
    private const CLR_APROVADO  = '008000';
    private const CLR_REPROVADO = 'CC0000';

    // ── Cache de estilos (inicializado uma vez) ───────────────────────────
    private ?array $styles = null;

    // =====================================================================
    // API pública
    // =====================================================================

    public function download(array $dados): BinaryFileResponse
    {
        $spreadsheet = $this->build($dados);
        $path = null;

        try {
            $path     = tempnam(sys_get_temp_dir(), 'pauta-geral-') . '.xlsx';
            $filename = 'pauta-' . Str::slug(
                $dados['turma']->nome_completo ?? $dados['turma']->nome, '-'
            ) . '.xlsx';

            \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

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
        $turma     = $dados['turma'];
        $anoLetivo = $dados['anoLetivo'] ?? null;
        $trimestre = (string) ($dados['trimestre'] ?? '3');

        $turma->loadMissing(['curso.coordenador', 'coordenador', 'disciplinas']);
        $anoLetivo ??= $turma->anoLetivo;

        $alunos = $turma->alunos()
            ->wherePivot('status', 'matriculado')
            ->orderBy('name')
            ->get();

        $notas       = $this->resolveNotas($turma, $anoLetivo, $dados);
        $notasIndex  = $this->indexarNotas($notas);
        $disciplinas = $this->ordenarDisciplinas($turma->disciplinas)->values();

        if ($disciplinas->count() > count(self::DISCIPLINE_BLOCKS)) {
            throw new \RuntimeException(
                'O template suporta no máximo ' . count(self::DISCIPLINE_BLOCKS) . ' disciplinas por pauta geral.'
            );
        }

        $atribuicoes = $turma->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
            ->with(['professor', 'disciplina'])
            ->get()
            ->keyBy('disciplina_id');

        $config      = $this->periodoConfig($trimestre);
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pauta');

        $lastDataRow = self::DATA_START + $alunos->count() - 1;

        // Reset style cache para cada build
        $this->styles = null;

        $this->buildPageSetup($sheet);
        $this->applyColumnWidths($sheet, $disciplinas);
        $this->buildCabecalho($sheet, $turma, $anoLetivo, $config);
        $this->buildDisciplinaHeaders($sheet, $disciplinas, $config);
        $this->buildAlunoRows($sheet, $alunos, $disciplinas, $notasIndex, $config, $lastDataRow);
        $this->buildRodape($sheet, $turma, $lastDataRow, $atribuicoes, $disciplinas);

        return $spreadsheet;
    }

    // =====================================================================
    // Cache de estilos — criados UMA vez, reutilizados em todo o export
    // =====================================================================

    private function getStyles(): array
    {
        if ($this->styles !== null) {
            return $this->styles;
        }

        $font9        = ['size' => 9, 'name' => 'Arial'];
        // FIX: FILL_NONE explícito em todos os estilos de dados —
        // impede que fills herdados de temas ou templates apareçam
        // nas linhas de alunos (ex.: linha amarela do template original).
        $noFill       = ['fillType' => Fill::FILL_NONE];
        $thinBorder   = ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => self::CLR_BORDER]];
        $mediumBorder = ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CLR_BORDER]];

        $this->styles = [
            'font9' => $font9,

            // Célula de dados — centro, bordas finas, sem fill
            'cellCenter' => [
                'font'      => $font9,
                'fill'      => $noFill,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => $thinBorder],
            ],

            // Célula de dados — centro, bordas finas, bold (para MT), sem fill
            'cellCenterBold' => [
                'font'      => array_merge($font9, ['bold' => true]),
                'fill'      => $noFill,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => $thinBorder],
            ],

            // Nome do aluno — esquerda, bordas laterais médias, sem fill
            'cellName' => [
                'font'      => $font9,
                'fill'      => $noFill,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => [
                    'left'   => $mediumBorder,
                    'right'  => $mediumBorder,
                    'top'    => $thinBorder,
                    'bottom' => $thinBorder,
                ],
            ],

            // Separadores de bloco de disciplina — sem fill
            'borderLeftMedium'  => [
                'fill'    => $noFill,
                'borders' => ['left'  => $mediumBorder],
            ],
            'borderRightMedium' => [
                'fill'    => $noFill,
                'borders' => ['right' => $mediumBorder],
            ],

            // Cabeçalho institucional
            'headerInst' => [
                'font'      => ['bold' => false, 'size' => 9, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ],

            // Assinatura no rodapé
            'sigCenter' => [
                'font'      => $font9,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        return $this->styles;
    }

    // =====================================================================
    // Helpers de escrita — eliminam repetição de setCellValue + getStyle
    // =====================================================================

    private function writeCell(
        Worksheet $sheet,
        string $cell,
        mixed $value,
        string $styleName,
        ?string $dataType = null
    ): void {
        if ($dataType !== null) {
            $sheet->setCellValueExplicit($cell, (string) $value, $dataType);
        } else {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle($cell)->applyFromArray($this->getStyles()[$styleName]);
    }

    private function writeMergedCell(
        Worksheet $sheet,
        string $range,
        mixed $value,
        string $styleName
    ): void {
        $sheet->mergeCells($range);

        // Extrair célula inicial do range (ex.: "X5:BE5" → "X5")
        $startCell = Str::before($range, ':');
        $sheet->setCellValue($startCell, $value);
        $sheet->getStyle($range)->applyFromArray($this->getStyles()[$styleName]);
    }

    /**
     * Escreve valor numérico com formato 0.00 e estilo do cache.
     */
    private function writeNumericCell(Worksheet $sheet, string $cell, mixed $value, bool $bold = false): void
    {
        $styles = $this->getStyles();

        if ($value !== null && $value !== '') {
            $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_NUMERIC);
            $sheet->getStyle($cell)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        } else {
            $sheet->setCellValue($cell, '');
        }

        $sheet->getStyle($cell)->applyFromArray(
            $bold ? $styles['cellCenterBold'] : $styles['cellCenter']
        );
    }

    private function setRowHeights(Worksheet $sheet, array $heights): void
    {
        foreach ($heights as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }
    }

    // =====================================================================
    // Dados institucionais — centralizados, configuráveis via config/escola.php
    // =====================================================================

    private function cfg(string $key, string $fallback = ''): string
    {
        return config("escola.{$key}", $fallback);
    }

    private function nomeInstituicao(): string
    {
        return $this->cfg(
            'nome_instituicao',
            'INSTITUTO POLITÉCNICO INDUSTRIAL DO KILAMBA KIAXI Nº 8056 "NOVA VIDA" '
        );
    }

    private function nomeDirector(Turma $turma): string
    {
        return $turma->director?->name
            ?? $this->cfg('nome_director', 'Ferreira Manuel Fragoso  Ph,D');
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
            ?? $this->cfg('coordenador_curso_fallback', 'Manuel Gonçalves Victor');
    }

    // =====================================================================
    // Configuração de página
    // =====================================================================

    private function buildPageSetup(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.5)->setBottom(0.8)
            ->setLeft(0.4)->setRight(0.4);

        $sheet->getSheetView()->setZoomScale(75);
        $sheet->freezePane('A' . self::DATA_START);
    }

    // =====================================================================
    // Larguras de coluna
    // =====================================================================

    private function applyColumnWidths(Worksheet $sheet, Collection $disciplinas): void
    {
        foreach (self::COL_WIDTHS_FIXED as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        foreach (self::DISCIPLINE_BLOCKS as $idx => [$startCol]) {
            $startIdx      = Coordinate::columnIndexFromString($startCol);
            $hasDisciplina = $disciplinas->has($idx);

            foreach (self::BLOCK_COL_WIDTHS as $pos => $w) {
                $col = Coordinate::stringFromColumnIndex($startIdx + $pos);

                if (!$hasDisciplina) {
                    $sheet->getColumnDimension($col)->setVisible(false)->setWidth(0);
                } else {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
            }
        }

        $sheet->getColumnDimension(self::COL_OBS)->setWidth(7.57);
        $sheet->getColumnDimension(self::COL_RESULT)->setWidth(10.86);
    }

    // =====================================================================
    // Cabeçalho institucional (linhas 1–11)
    // =====================================================================

    private function buildCabecalho(
        Worksheet $sheet,
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        array $config
    ): void {
        $styles = $this->getStyles();

        $this->setRowHeights($sheet, [
            1 => 12.75, 2 => 12.75, 3 => 12.75, 4 => 12.75,
            self::ROW_DIRECTOR => 17.25,
            self::ROW_ASSIN    => 17.25,
            self::ROW_NOME_DIR => 17.25,
            self::ROW_INFO     => 17.25,
            self::ROW_SPACER9  => 17.25,
            self::ROW_SPACER10 => 6.75,
            self::ROW_SPACER11 => 6.75,
            self::ROW_DISC     => 13.5,
            self::ROW_HDR1     => 14.1,
            self::ROW_HDR2     => 11.65,
        ]);

        // ── Linha 5: "O DIRECTOR" + Instituição ──────────────────────────
        $this->writeCell($sheet, 'C5', 'O DIRECTOR', 'font9');
        $this->writeMergedCell($sheet, 'X5:BE5', $this->nomeInstituicao(), 'headerInst');

        // ── Linha 6: Assinatura + Ano Lectivo + Título ───────────────────
        $this->writeCell($sheet, 'C6', '______________________________', 'font9');
        $this->writeCell($sheet, 'I6', 'Ano Lectivo', 'font9');

        $anoNome = $anoLetivo?->nome ?? ($turma->anoLetivo?->nome ?? date('Y') . '/' . (date('Y') + 1));
        $this->writeCell($sheet, 'M6', ':' . $anoNome, 'font9');
        $this->writeMergedCell($sheet, 'X6:BE6', $config['titulo'], 'headerInst');

        // ── Linha 7: Nome do director ────────────────────────────────────
        $this->writeCell($sheet, 'C7', $this->nomeDirector($turma), 'font9');

        // ── Linha 8: Data + Info turma ───────────────────────────────────
        $this->writeCell($sheet, 'C8', 'Data: _____/_____/_________', 'font9');

        $infoTurma = sprintf(
            '%s   SALA:%s   ÁREA: %s   CURSO: %s',
            strtoupper($turma->nome . ($turma->classe ?? '')),
            $turma->sala ?? '08',
            strtoupper($turma->curso?->area?->nome ?? $turma->curso?->nome ?? 'INFORMÁTICA'),
            strtoupper($turma->curso?->nome ?? 'INFORMÁTICA')
        );

        $this->writeMergedCell($sheet, 'P8:BR8', $infoTurma, 'headerInst');
    }

    // =====================================================================
    // Cabeçalhos das disciplinas (linhas 12–14) — BUG DO FOREACH CORRIGIDO
    // =====================================================================

    private function buildDisciplinaHeaders(
        Worksheet $sheet,
        Collection $disciplinas,
        array $config
    ): void {
        $r12 = self::ROW_DISC;
        $r13 = self::ROW_HDR1;
        $r14 = self::ROW_HDR2;

        $headerStyle = $this->headerCellStyle();

        // ── Colunas fixas A–D (linhas 13–14) ─────────────────────────────
        $fixedHeaders = [
            "A{$r13}" => 'Nº',   "A{$r14}" => 'ORD',
            "B{$r13}" => 'Nº',   "B{$r14}" => 'PROC.',
        ];

        foreach ($fixedHeaders as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->applyFromArray($headerStyle);
        }

        foreach (["C{$r13}:C{$r14}" => 'NOME COMPLETO', "D{$r13}:D{$r14}" => 'SEXO'] as $range => $value) {
            $sheet->mergeCells($range);
            $sheet->setCellValue(Str::before($range, ':'), $value);
            $sheet->getStyle($range)->applyFromArray($headerStyle);
        }

        // ── OBS / RESULTADO (lado direito) ───────────────────────────────
        $obsCell    = self::COL_OBS . $r14;
        $resultCell = self::COL_RESULT . $r14;

        $sheet->setCellValue($obsCell, 'OBSERV.');
        $sheet->setCellValue($resultCell, 'RESULTADO');
        $sheet->getStyle($obsCell)->applyFromArray($headerStyle);
        $sheet->getStyle($resultCell)->applyFromArray($headerStyle);

        // ── Blocos de disciplinas ─────────────────────────────────────────
        $labels = $config['labels'] ?? ['F.J', 'F.I', 'MfT2', 'MAC', 'NPT', 'NPT', 'MT'];

        foreach (self::DISCIPLINE_BLOCKS as $idx => [$startCol, $endCol]) {
            $disciplina = $disciplinas->get($idx);

            if (!$disciplina) {
                continue;
            }

            $cols = $this->columnsInRange($startCol, $endCol);

            // Linha 12: nome da disciplina (merge sobre 7 colunas)
            $this->buildDiscNameHeader($sheet, $startCol, $endCol, $r12, $disciplina);

            // Linha 13: FALTAS (merge FJ+FI)
            $mergeFaltas = "{$cols[self::POS_FALTAS_J]}{$r13}:{$cols[self::POS_FALTAS_I]}{$r13}";
            $sheet->mergeCells($mergeFaltas);
            $sheet->setCellValue("{$cols[self::POS_FALTAS_J]}{$r13}", 'FALTAS');
            $sheet->getStyle($mergeFaltas)->applyFromArray($this->headerCellStyle(4));

            // Linhas 13–14: colunas com merge vertical (MfT2, MAC, NPT, NPT, MT)
            $verticalHeaders = [];
            for ($pos = 2; $pos < count($cols); $pos++) {
                $verticalHeaders[$cols[$pos]] = $labels[$pos] ?? '';
            }

            $smallHeader = $this->headerCellStyle(5);

            foreach ($verticalHeaders as $col => $label) {
                $mergeV = "{$col}{$r13}:{$col}{$r14}";
                $sheet->mergeCells($mergeV);
                $sheet->setCellValue("{$col}{$r13}", $label);
                $sheet->getStyle($mergeV)->applyFromArray($smallHeader);
            }

            // Linha 14: J | I (sub-cabeçalho das faltas)
            $sheet->setCellValue("{$cols[self::POS_FALTAS_J]}{$r14}", 'J');
            $sheet->setCellValue("{$cols[self::POS_FALTAS_I]}{$r14}", 'I');
            $sheet->getStyle("{$cols[self::POS_FALTAS_J]}{$r14}")->applyFromArray($smallHeader);
            $sheet->getStyle("{$cols[self::POS_FALTAS_I]}{$r14}")->applyFromArray($smallHeader);
        }
    }

    /**
     * Célula de nome de disciplina (linha 12) com estilo e merge.
     */
    private function buildDiscNameHeader(
        Worksheet $sheet,
        string $startCol,
        string $endCol,
        int $row,
        Disciplina $disciplina
    ): void {
        $range = "{$startCol}{$row}:{$endCol}{$row}";
        $sheet->mergeCells($range);
        $sheet->setCellValue("{$startCol}{$row}", $this->abreviarDisciplina($disciplina));
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => false, 'size' => 8, 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_DISC_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => [
                'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CLR_BORDER]],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CLR_BORDER]],
                'left'   => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CLR_BORDER]],
                'right'  => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CLR_BORDER]],
            ],
        ]);
    }

    // =====================================================================
    // Linhas de dados dos alunos — dividido em sub-métodos
    // =====================================================================

    private function buildAlunoRows(
        Worksheet $sheet,
        EloquentCollection $alunos,
        Collection $disciplinas,
        array $notasIndex,
        array $config,
        int $lastDataRow
    ): void {
        // FIX: Reset de fill em toda a zona de dados de uma vez, antes de
        // escrever qualquer célula. Garante que nenhuma linha herda um fill
        // colorido (ex.: amarelo) de temas, estilos globais ou templates.
        if ($alunos->isNotEmpty()) {
            $sheet->getStyle('A' . self::DATA_START . ':' . self::COL_RESULT . $lastDataRow)
                  ->applyFromArray(['fill' => ['fillType' => Fill::FILL_NONE]]);
        }

        foreach ($alunos as $offset => $aluno) {
            $row        = self::DATA_START + $offset;
            $notasAluno = $notasIndex[$aluno->id] ?? [];

            $sheet->getRowDimension($row)->setRowHeight(13.5);

            $this->writeAlunoFixedColumns($sheet, $aluno, $row, $offset + 1);
            $this->writeAlunoDisciplinaNotas($sheet, $row, $disciplinas, $notasAluno, $config);
            $this->writeAlunoResultado($sheet, $row, $disciplinas, $notasAluno, $config);
        }

        // Bordas externas da tabela de dados
        $sheet->getStyle('A' . self::DATA_START . ':' . self::COL_RESULT . $lastDataRow)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CLR_BORDER]],
            ],
        ]);
    }

    /**
     * Colunas A–D: ordinal, nº processo, nome, sexo.
     */
    private function writeAlunoFixedColumns(Worksheet $sheet, object $aluno, int $row, int $ordinal): void
    {
        $styles = $this->getStyles();

        $sheet->setCellValueExplicit("A{$row}", (string) $ordinal, DataType::TYPE_NUMERIC);
        $sheet->getStyle("A{$row}")->applyFromArray($styles['cellCenter']);

        $sheet->setCellValueExplicit("B{$row}", (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
        $sheet->getStyle("B{$row}")->applyFromArray($styles['cellCenter']);

        $sheet->setCellValueExplicit("C{$row}", $aluno->name, DataType::TYPE_STRING);
        $sheet->getStyle("C{$row}")->applyFromArray($styles['cellName']);

        $sheet->setCellValueExplicit("D{$row}", strtoupper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);
        $sheet->getStyle("D{$row}")->applyFromArray($styles['cellCenter']);
    }

    /**
     * Notas de todas as disciplinas para um aluno numa linha.
     */
    private function writeAlunoDisciplinaNotas(
        Worksheet $sheet,
        int $row,
        Collection $disciplinas,
        array $notasAluno,
        array $config
    ): void {
        $styles = $this->getStyles();
        $campos = $config['campos'];

        foreach ($disciplinas->values() as $idx => $disciplina) {
            [$startCol, $endCol] = self::DISCIPLINE_BLOCKS[$idx];
            $cols = $this->columnsInRange($startCol, $endCol);
            $nota = $notasAluno[$disciplina->id] ?? null;

            foreach ($cols as $pos => $col) {
                $value = $this->resolverValorCelula($nota, $campos[$pos] ?? null);
                $bold  = ($pos === self::POS_MT);

                $this->writeNumericCell($sheet, "{$col}{$row}", $value, $bold);
            }

            // Bordas médias nas extremidades do bloco (separador visual)
            $sheet->getStyle("{$startCol}{$row}")->applyFromArray($styles['borderLeftMedium']);
            $sheet->getStyle("{$endCol}{$row}")->applyFromArray($styles['borderRightMedium']);
        }
    }

    /**
     * Resolve o valor de uma célula a partir do campo e da nota.
     */
    private function resolverValorCelula(?object $nota, ?string $campo): mixed
    {
        if ($campo === null) {
            return null;
        }

        if (in_array($campo, ['faltas_j', 'faltas_i'], true)) {
            return '';
        }

        return $nota?->{$campo};
    }

    /**
     * Colunas OBS e RESULTADO para um aluno.
     */
    private function writeAlunoResultado(
        Worksheet $sheet,
        int $row,
        Collection $disciplinas,
        array $notasAluno,
        array $config
    ): void {
        $styles    = $this->getStyles();
        $obsCol    = self::COL_OBS;
        $resultCol = self::COL_RESULT;

        [$obs, $resultado] = $this->resolverResultadoAluno(
            $disciplinas,
            $notasAluno,
            $config['mostrarResultado'] ?? false
        );

        $sheet->setCellValueExplicit("{$obsCol}{$row}", $obs, DataType::TYPE_STRING);
        $sheet->getStyle("{$obsCol}{$row}")->applyFromArray($styles['cellCenter']);

        $sheet->setCellValueExplicit("{$resultCol}{$row}", $resultado, DataType::TYPE_STRING);

        $resultStyle = $styles['cellCenter'];

        if (!empty($resultado)) {
            $resultStyle['font']['bold'] = true;

            if ($this->isTransita($resultado)) {
                $resultStyle['font']['color'] = ['rgb' => self::CLR_APROVADO];
            } elseif ($this->isNaoTransita($resultado)) {
                $resultStyle['font']['color'] = ['rgb' => self::CLR_REPROVADO];
            }
        }

        $sheet->getStyle("{$resultCol}{$row}")->applyFromArray($resultStyle);
    }

    private function isTransita(string $resultado): bool
    {
        return stripos($resultado, 'transita') !== false
            && stripos($resultado, 'não') === false;
    }

    private function isNaoTransita(string $resultado): bool
    {
        return stripos($resultado, 'não') !== false
            || stripos($resultado, 'reprov') !== false;
    }

    // =====================================================================
    // Rodapé
    // =====================================================================

    private function buildRodape(
        Worksheet $sheet,
        Turma $turma,
        int $lastDataRow,
        Collection $atribuicoes,
        Collection $disciplinas
    ): void {
        $styles = $this->getStyles();
        $font9  = $styles['font9'];

        // Linha em branco
        $this->setRowHeights($sheet, [$lastDataRow + 1 => 8]);

        // ── Professores por disciplina ───────────────────────────────────
        $rConselho = $lastDataRow + 2;
        $sheet->getRowDimension($rConselho)->setRowHeight(14);
        $this->writeCell($sheet, "A{$rConselho}", 'Data do Conselho de Turma', 'font9');

        foreach ($disciplinas->values() as $idx => $disciplina) {
            [$startCol] = self::DISCIPLINE_BLOCKS[$idx];
            $professor  = $atribuicoes->get($disciplina->id)?->professor?->name ?? '';

            if ($professor) {
                $this->writeCell($sheet, "{$startCol}{$rConselho}", $professor, 'sigCenter');
            }
        }

        // ── Data do conselho ─────────────────────────────────────────────
        $rData = $rConselho + 1;
        $sheet->getRowDimension($rData)->setRowHeight(14);
        $this->writeCell($sheet, "A{$rData}", '_____/______/________', 'font9');

        // ── Observações ──────────────────────────────────────────────────
        $rObs = $rData + 2; // +1 linha vazia
        $this->setRowHeights($sheet, [$rData + 1 => 8]);

        $this->buildObservacoes($sheet, $rObs);

        // ── Assinaturas ──────────────────────────────────────────────────
        $rSigLabel = $rObs + 4; // 1 label + 3 linhas de obs
        $this->buildAssinaturas($sheet, $turma, $rSigLabel);
    }

    private function buildObservacoes(Worksheet $sheet, int $startRow): void
    {
        $resultCol = self::COL_RESULT;

        $this->writeMergedCell($sheet, "A{$startRow}:{$resultCol}{$startRow}", 'Observações:', 'font9');
        $sheet->getRowDimension($startRow)->setRowHeight(12);

        $textos = [
            'Na coluna Resultado utilizar: Transita; Não Transita; Anulação de matrícula (AM); Transferido.',
            'Na coluna CF indicar EEF (excluído por excesso de faltas) quando for o caso.',
            'Na coluna Observações escrever "Exame" quando o aluno não obteve aprovação a alguma disciplina terminal e o Regime de Avaliação dos Alunos o permitir.',
        ];

        foreach ($textos as $i => $texto) {
            $row = $startRow + 1 + $i;
            $sheet->mergeCells("A{$row}:{$resultCol}{$row}");
            $sheet->setCellValue("A{$row}", $texto);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font'      => $this->getStyles()['font9'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(12);
        }
    }

    private function buildAssinaturas(Worksheet $sheet, Turma $turma, int $startRow): void
    {
        $rLabel = $startRow + 1;
        $rRisco = $rLabel + 1;
        $rNome  = $rRisco + 1;

        $this->setRowHeights($sheet, [
            $rLabel => 14, $rRisco => 14, $rNome => 14,
        ]);

        $signatarios = [
            // [colLabel, label, colRisco, risco, colNome, nome]
            ['I',  'O DIRECTOR DE TURMA',      'D',  '______________________________', 'H',  $this->nomeDirectorTurma($turma)],
            ['AQ', 'O COORDENADOR DE CURSO',   'AG', '______________________________', 'AQ', $this->nomeCoordenadorCurso($turma)],
            ['BW', 'O SUBDIRECTOR PEDAGÓGICO', 'BY', '____________________________________', 'BY', $this->nomeSubdirectorPedagogico()],
        ];

        foreach ($signatarios as [$colL, $label, $colR, $risco, $colN, $nome]) {
            $this->writeCell($sheet, "{$colL}{$rLabel}", $label, 'sigCenter');
            $this->writeCell($sheet, "{$colR}{$rRisco}", $risco, 'font9');
            $this->writeCell($sheet, "{$colN}{$rNome}", $nome, 'sigCenter');
        }
    }

    // =====================================================================
    // Lógica de negócio
    // =====================================================================

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
                'titulo'           => 'PAUTA DE APROVEITAMENTO - Iº TRIMESTRE',
                'labels'           => ['F.J', 'F.I', 'MfT2', 'MAC1', 'NPT1', 'NPT1', 'MT1'],
                'campos'           => ['faltas_j', 'faltas_i', 'mft2', 'mac1', 'pp1', 'pt1', 'mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo'           => 'PAUTA DE APROVEITAMENTO - IIº TRIMESTRE',
                'labels'           => ['F.J', 'F.I', 'MfT2', 'MAC2', 'NPT2', 'NPT2', 'MT2'],
                'campos'           => ['faltas_j', 'faltas_i', 'mft2', 'mac2', 'pp2', 'pt2', 'mt2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo'           => 'PAUTA DE APROVEITAMENTO - IIIº TRIMESTRE',
                'labels'           => ['F.J', 'F.I', 'MfT2', 'MAC3', 'NPT2', 'PG', 'MT3'],
                'campos'           => ['faltas_j', 'faltas_i', 'mft2', 'mac3', 'pp3', 'pg', 'mt3'],
                'mostrarResultado' => false,
            ],
            default => [
                'titulo'           => 'PAUTA GERAL DO ANO LETIVO',
                'labels'           => ['F.J', 'F.I', 'CF', 'MT1', 'MT2', 'PG', 'CFD'],
                'campos'           => ['faltas_j', 'faltas_i', 'cf', 'mt1', 'mt2', 'pg', 'cfd'],
                'mostrarResultado' => true,
            ],
        };
    }

    private function resolverResultadoAluno(
        Collection $disciplinas,
        array $notasAluno,
        bool $mostrarResultado
    ): array {
        if (!$mostrarResultado) {
            return ['', ''];
        }

        $temNota = $temPendente = $temReprovacao = $temExame = $temEEF = false;

        foreach ($disciplinas as $disciplina) {
            $nota = $notasAluno[$disciplina->id] ?? null;
            $cf   = $nota?->cf;
            $cfd  = $nota?->cfd;

            if (is_string($cf) && strtoupper($cf) === 'EEF') {
                $temEEF = true;
                continue;
            }

            if ($cfd === null && $cf === null) {
                $temPendente = true;
                continue;
            }

            $temNota = true;
            $val     = $cfd ?? $cf;

            if ($val !== null && (float) $val < 10) {
                $temReprovacao = true;

                if ($disciplina->disciplina_terminal) {
                    $temExame = true;
                }
            }
        }

        if (!$temNota || $temPendente) {
            return ['', ''];
        }

        if ($temEEF) {
            return ['EEF', 'Não Transita'];
        }

        if (!$temReprovacao) {
            return ['', 'Transita'];
        }

        return [$temExame ? 'Exame' : '', 'Não Transita'];
    }

    // =====================================================================
    // Helpers de ordenação / abreviação
    // =====================================================================

    private function ordenarDisciplinas(Collection $disciplinas): Collection
    {
        $ordem = [
            'LINGUA PORTUGUESA' => 10, 'PORTUGUES' => 10,
            'INGLES' => 20, 'FILOSOFIA' => 30, 'HISTORIA' => 40,
            'GEOGRAFIA' => 50, 'EDUCACAO FISICA' => 60, 'MATEMATICA' => 70,
            'FISICA' => 80, 'QUIMICA' => 90, 'BIOLOGIA' => 100,
            'EMPREENDEDORISMO' => 110, 'TIC' => 120, 'INFORMATICA' => 120,
        ];

        return $disciplinas->sortBy(function (Disciplina $d) use ($ordem) {
            $n = $this->normalize($d->nome);
            return str_pad((string) ($ordem[$n] ?? 999), 4, '0', STR_PAD_LEFT) . '-' . $n;
        });
    }

    private function abreviarDisciplina(Disciplina $disciplina): string
    {
        $aliases = [
            'LINGUA PORTUGUESA'      => 'L. PORTUGUESA',
            'PORTUGUES'              => 'L. PORTUGUESA',
            'INGLES'                 => 'INGLÊS',
            'EDUCACAO FISICA'        => 'ED. FÍSICA',
            'MATEMATICA'             => 'MATEMAT.',
            'FISICA'                 => 'FÍSICA',
            'QUIMICA'               => 'QUÍMICA',
            'ELECTROTECNIA'          => 'ELECTROTECNIA',
            'EMPREENDEDORISMO'       => 'EMPREEND.',
            'INFORMATICA'            => 'TIC',
            'TECNOLOGIAS INFORMACAO' => 'TIC',
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
        $end   = Coordinate::columnIndexFromString($endCol);
        $cols  = [];

        for ($c = $start; $c <= $end; $c++) {
            $cols[] = Coordinate::stringFromColumnIndex($c);
        }

        return $cols;
    }

    // =====================================================================
    // Style helpers
    // =====================================================================

    private function headerCellStyle(int $fontSize = 9): array
    {
        return [
            'font' => ['bold' => false, 'size' => $fontSize, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CLR_DISC_BG]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CLR_BORDER]],
            ],
        ];
    }
}