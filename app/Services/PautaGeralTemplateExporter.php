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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PautaGeralTemplateExporter
{
    // ── Layout constants ────────────────────────────────────────────────────
    private const DATA_START_ROW   = 15;
    private const TEMPLATE_DATA_ROWS = 36;
    private const OBS_COLUMN      = 'CH';
    private const RESULT_COLUMN   = 'CI';

    /**
     * Each discipline occupies a block of spreadsheet columns.
     * Blocks of 7 cols (E:K, L:R, …) for trimestres with ≤6 labels.
     * Adjust the count per block if your label sets ever exceed 6.
     */
    private const DISCIPLINE_BLOCKS = [
        'E:K',  'L:R',  'S:Y',  'Z:AF',
        'AG:AM','AN:AS','AT:AY','AZ:BF',
        'BG:BM','BN:BS','BT:BZ','CA:CG',
    ];

    // ── Colours ─────────────────────────────────────────────────────────────
    private const CLR_HEADER_BG   = 'D9E1F2'; // blue-grey header fill
    private const CLR_HEADER_FONT = '1F3864'; // dark blue
    private const CLR_SUBHEADER   = 'BDD7EE'; // lighter blue for row 13–14
    private const CLR_BORDER      = '4472C4'; // medium border colour
    private const CLR_LIGHT_BORDER = 'B8CCE4';
    private const CLR_ROW_ALT     = 'F2F7FC'; // alternate row fill
    private const CLR_FOOTER_BG   = 'F0F4F8';
    private const CLR_FONT_MAIN   = '1A1A2E';

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    public function download(array $dados): BinaryFileResponse
    {
        $spreadsheet = $this->build($dados);

        $path = null;

        try {
            $path = tempnam(sys_get_temp_dir(), 'pauta-geral-') . '.xlsx';
            \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

            $filename = 'pauta-geral-' . Str::slug($dados['turma']->nome, '-') . '.xlsx';

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

        $notas      = $this->resolveNotas($turma, $anoLetivo, $dados);
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

        $config = $this->periodoConfig($trimestre);

        // ── Build spreadsheet from scratch ───────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pauta Geral');

        $studentCount = $alunos->count();
        $rowsNeeded   = max($studentCount, self::TEMPLATE_DATA_ROWS);
        $lastDataRow  = self::DATA_START_ROW + $rowsNeeded - 1;
        $footerRow    = $lastDataRow + 2;   // gap of 1 blank row

        // Build sections
        $this->buildPageSetup($sheet);
        $this->buildCabecalho($sheet, $turma, $anoLetivo, $config);
        $this->buildDisciplinaHeaders($sheet, $disciplinas, $config, $atribuicoes);
        $this->buildAlunoRows($sheet, $alunos, $disciplinas, $notasIndex, $config, $lastDataRow);
        $this->buildRodape($sheet, $turma, $footerRow, $atribuicoes, $disciplinas);
        $this->applyColumnWidths($sheet, $disciplinas);

        return $spreadsheet;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Page / print setup
    // ─────────────────────────────────────────────────────────────────────────

    private function buildPageSetup(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A3)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.5)->setBottom(0.5)
            ->setLeft(0.4)->setRight(0.4);

        $sheet->getSheetView()->setZoomScale(75);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cabeçalho (rows 1–14)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildCabecalho(
        Worksheet $sheet,
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        array $config
    ): void {
        $lastCol = self::RESULT_COLUMN;

        // ── Row 1: institution banner ──
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', Str::upper($turma->curso->nome ?? 'ESCOLA'));
        $this->style($sheet, "A1:{$lastCol}1", [
            'font'      => ['bold' => true, 'size' => 14, 'color' => self::CLR_HEADER_FONT],
            'fill'      => self::solidFill(self::CLR_HEADER_BG),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Row 2: document title ──
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $config['titulo']);
        $this->style($sheet, "A2:{$lastCol}2", [
            'font'      => ['bold' => true, 'size' => 12, 'color' => self::CLR_HEADER_FONT],
            'fill'      => self::solidFill(self::CLR_HEADER_BG),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // ── Row 3: blank spacer ──
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Row 4: Ano Lectivo / Turma / Curso ──
        $sheet->mergeCells("A4:D4");
        $sheet->mergeCells("E4:M4");
        $sheet->mergeCells("N4:Z4");
        $sheet->mergeCells("AA4:{$lastCol}4");

        $sheet->setCellValue('A4', 'ANO LECTIVO:');
        $sheet->setCellValue('E4', $anoLetivo?->nome ?? $turma->anoLetivo?->nome ?? '');
        $sheet->setCellValue('N4', 'TURMA:');
        $sheet->setCellValue('AA4', $turma->nome);

        foreach (['A4', 'N4'] as $cell) {
            $this->style($sheet, $cell, ['font' => ['bold' => true, 'color' => self::CLR_HEADER_FONT]]);
        }
        $sheet->getRowDimension(4)->setRowHeight(18);

        // ── Row 5: Classe / Curso / Período ──
        $sheet->mergeCells("A5:D5");
        $sheet->mergeCells("E5:M5");
        $sheet->mergeCells("N5:Z5");
        $sheet->mergeCells("AA5:{$lastCol}5");

        $sheet->setCellValue('A5', 'CLASSE:');
        $sheet->setCellValue('E5', $turma->classe . 'ª');
        $sheet->setCellValue('N5', 'CURSO:');
        $sheet->setCellValue('AA5', Str::upper($turma->curso->nome ?? ''));

        foreach (['A5', 'N5'] as $cell) {
            $this->style($sheet, $cell, ['font' => ['bold' => true, 'color' => self::CLR_HEADER_FONT]]);
        }
        $sheet->getRowDimension(5)->setRowHeight(18);

        // ── Row 6: Period / Date ──
        $sheet->mergeCells("A6:D6");
        $sheet->mergeCells("E6:M6");
        $sheet->mergeCells("N6:Z6");
        $sheet->mergeCells("AA6:{$lastCol}6");

        $sheet->setCellValue('A6', 'PERÍODO:');
        $sheet->setCellValue('E6', $config['subtitulo']);
        $sheet->setCellValue('N6', 'DATA:');
        $sheet->setCellValue('AA6', now()->format('d/m/Y'));

        foreach (['A6', 'N6'] as $cell) {
            $this->style($sheet, $cell, ['font' => ['bold' => true, 'color' => self::CLR_HEADER_FONT]]);
        }
        $sheet->getRowDimension(6)->setRowHeight(18);

        // ── Rows 4–6 background ──
        $this->style($sheet, "A4:{$lastCol}6", ['fill' => self::solidFill('EBF1F8')]);

        // ── Outer border around header block ──
        $this->style($sheet, "A1:{$lastCol}6", [
            'borders' => [
                'outline' => self::borderDef(Border::BORDER_MEDIUM, self::CLR_BORDER),
            ],
        ]);

        // ── Row 7: blank spacer ──
        $sheet->getRowDimension(7)->setRowHeight(6);

        // ── Rows 8–11: column group labels (Nº / Nome / Sexo + discipline groups) ──
        //    These are built inside buildDisciplinaHeaders() because they depend
        //    on which discipline blocks are visible.

        // Fixed left-side header cells across rows 8–14
        foreach ([8, 9, 10, 11] as $row) {
            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        // Merge A8:A14 → "Nº"
        $sheet->mergeCells('A8:A14');
        $sheet->setCellValue('A8', 'Nº');
        $this->style($sheet, 'A8:A14', $this->headerCellStyle());

        // Merge B8:B14 → "Nº PROCESSO"
        $sheet->mergeCells('B8:B14');
        $sheet->setCellValue('B8', 'Nº PROCESSO');
        $this->style($sheet, 'B8:B14', $this->headerCellStyle());

        // Merge C8:C14 → "NOME DO ALUNO"
        $sheet->mergeCells('C8:C14');
        $sheet->setCellValue('C8', 'NOME DO ALUNO');
        $this->style($sheet, 'C8:C14', $this->headerCellStyle());

        // Merge D8:D14 → "SEXO"
        $sheet->mergeCells('D8:D14');
        $sheet->setCellValue('D8', 'SEXO');
        $this->style($sheet, 'D8:D14', $this->headerCellStyle());

        // Merge OBS column rows 8–14
        $sheet->mergeCells(self::OBS_COLUMN . '8:' . self::OBS_COLUMN . '14');
        $sheet->setCellValue(self::OBS_COLUMN . '8', 'OBS.');
        $this->style($sheet, self::OBS_COLUMN . '8:' . self::OBS_COLUMN . '14', $this->headerCellStyle());

        // Merge RESULT column rows 8–14
        $sheet->mergeCells(self::RESULT_COLUMN . '8:' . self::RESULT_COLUMN . '14');
        $sheet->setCellValue(self::RESULT_COLUMN . '8', 'RESULTADO');
        $this->style($sheet, self::RESULT_COLUMN . '8:' . self::RESULT_COLUMN . '14', $this->headerCellStyle());

        // Row heights for discipline header rows
        $sheet->getRowDimension(12)->setRowHeight(30); // discipline name
        $sheet->getRowDimension(13)->setRowHeight(20); // label row (MAC1, PP1…)
        $sheet->getRowDimension(14)->setRowHeight(20); // sub-label or blank
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Discipline column headers (rows 8–14)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildDisciplinaHeaders(
        Worksheet $sheet,
        Collection $disciplinas,
        array $config,
        Collection $atribuicoes
    ): void {
        foreach (self::DISCIPLINE_BLOCKS as $index => $range) {
            [$startCol, $endCol] = explode(':', $range);
            $allCols = $this->columnsInRange($startCol, $endCol);
            $labelsCount = count($config['labels']);

            // Visible cols = exactly the number of labels for this period
            $visibleCols   = array_slice($allCols, 0, $labelsCount);
            $invisibleCols = array_slice($allCols, $labelsCount);

            // Hide unused columns within the block
            foreach ($invisibleCols as $col) {
                $sheet->getColumnDimension($col)->setVisible(false);
            }

            $disciplina = $disciplinas->get($index);

            if (!$disciplina) {
                // Hide entire block
                foreach ($allCols as $col) {
                    $sheet->getColumnDimension($col)->setVisible(false);
                }
                continue;
            }

            // ── Rows 8–11: discipline name merged across visible cols ──
            $lastVisible = end($visibleCols);
            $mergeRange  = "{$startCol}8:{$lastVisible}11";
            $sheet->mergeCells($mergeRange);
            $sheet->setCellValue("{$startCol}8", $this->abreviarDisciplina($disciplina));
            $this->style($sheet, $mergeRange, [
                'font'      => ['bold' => true, 'size' => 9, 'color' => self::CLR_HEADER_FONT],
                'fill'      => self::solidFill(self::CLR_HEADER_BG),
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => [
                    'allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER),
                    'outline'    => self::borderDef(Border::BORDER_MEDIUM, self::CLR_BORDER),
                ],
            ]);

            // ── Row 12: label row (MAC1, PP1, MT1 …) ──
            foreach ($visibleCols as $pos => $col) {
                $label = $config['labels'][$pos] ?? '';
                $sheet->setCellValue("{$col}12", $label);
                $this->style($sheet, "{$col}12", [
                    'font'      => ['bold' => true, 'size' => 8, 'color' => self::CLR_HEADER_FONT],
                    'fill'      => self::solidFill(self::CLR_SUBHEADER),
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER)],
                ]);
            }

            // ── Rows 13–14: blank (or sub-label if needed) ──
            foreach ($visibleCols as $col) {
                $this->style($sheet, "{$col}13", [
                    'fill'    => self::solidFill(self::CLR_SUBHEADER),
                    'borders' => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER)],
                ]);
                $this->style($sheet, "{$col}14", [
                    'fill'    => self::solidFill(self::CLR_SUBHEADER),
                    'borders' => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER)],
                ]);
            }
        }

        // OBS / RESULTADO header styles (already merged in buildCabecalho)
        $this->style($sheet, self::OBS_COLUMN . '12', [
            'fill'    => self::solidFill(self::CLR_SUBHEADER),
            'borders' => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER)],
        ]);
        $this->style($sheet, self::RESULT_COLUMN . '12', [
            'fill'    => self::solidFill(self::CLR_SUBHEADER),
            'borders' => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER)],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data rows (alunos)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildAlunoRows(
        Worksheet $sheet,
        EloquentCollection $alunos,
        Collection $disciplinas,
        array $notasIndex,
        array $config,
        int $lastDataRow
    ): void {
        $lastCol    = self::RESULT_COLUMN;
        $totalRows  = $lastDataRow - self::DATA_START_ROW + 1;

        for ($offset = 0; $offset < $totalRows; $offset++) {
            $row    = self::DATA_START_ROW + $offset;
            $aluno  = $alunos->values()->get($offset); // null for blank filler rows
            $isAlt  = ($offset % 2 === 1);
            $fillHex = $isAlt ? self::CLR_ROW_ALT : 'FFFFFF';

            // Base row style
            $sheet->getRowDimension($row)->setRowHeight(16);
            $this->style($sheet, "A{$row}:{$lastCol}{$row}", [
                'fill'      => self::solidFill($fillHex),
                'font'      => ['size' => 9, 'color' => self::CLR_FONT_MAIN],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => [
                    'bottom' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER),
                ],
            ]);

            if (!$aluno) {
                // Blank filler row – already styled, just leave cells empty
                $sheet->setCellValue("A{$row}", '');
                continue;
            }

            // Fixed columns
            $sheet->setCellValueExplicit("A{$row}", (string) ($offset + 1), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$row}", (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", $aluno->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", strtoupper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);

            $this->style($sheet, "A{$row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $this->style($sheet, "B{$row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $this->style($sheet, "D{$row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

            $notasAluno = $notasIndex[$aluno->id] ?? [];

            // Note columns per discipline
            foreach ($disciplinas->values() as $index => $disciplina) {
                [$startCol, $endCol] = explode(':', self::DISCIPLINE_BLOCKS[$index]);
                $allCols    = $this->columnsInRange($startCol, $endCol);
                $labelsCount = count($config['labels']);
                $visibleCols = array_slice($allCols, 0, $labelsCount);

                $nota = $notasAluno[$disciplina->id] ?? null;

                foreach ($visibleCols as $pos => $col) {
                    $campo = $config['campos'][$pos] ?? null;
                    $value = $campo ? $this->notaValor($nota, $campo) : null;

                    if ($value === null) {
                        $sheet->setCellValue("{$col}{$row}", '');
                    } else {
                        $sheet->setCellValueExplicit("{$col}{$row}", (string) $value, DataType::TYPE_NUMERIC);
                        $sheet->getStyle("{$col}{$row}")
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    }

                    $this->style($sheet, "{$col}{$row}", [
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }
            }

            // OBS / Resultado
            [$obs, $resultado] = $this->resolverResultadoAluno(
                $disciplinas, $notasAluno, $config['mostrarResultado']
            );

            $sheet->setCellValueExplicit(self::OBS_COLUMN . $row, $obs, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::RESULT_COLUMN . $row, $resultado, DataType::TYPE_STRING);

            $this->style($sheet, self::OBS_COLUMN . $row . ':' . self::RESULT_COLUMN . $row, [
                'font'      => ['bold' => !empty($resultado), 'size' => 8],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Outer border around entire data area
        $this->style($sheet, "A" . self::DATA_START_ROW . ":{$lastCol}{$lastDataRow}", [
            'borders' => [
                'outline' => self::borderDef(Border::BORDER_MEDIUM, self::CLR_BORDER),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rodapé (assinaturas)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildRodape(
        Worksheet $sheet,
        Turma $turma,
        int $footerRow,
        Collection $atribuicoes,
        Collection $disciplinas
    ): void {
        $lastCol = self::RESULT_COLUMN;

        // Blank gap row
        $sheet->getRowDimension($footerRow - 1)->setRowHeight(10);

        // Row: professor signatures (one per discipline)
        $sheet->getRowDimension($footerRow)->setRowHeight(14);
        $sigRow  = $footerRow + 6; // leave space for a "signature line"
        $labelRow = $footerRow;

        $sheet->setCellValue("A{$labelRow}", 'PROFESSORES:');
        $this->style($sheet, "A{$labelRow}", ['font' => ['bold' => true, 'size' => 9]]);

        foreach ($disciplinas->values() as $index => $disciplina) {
            [$startCol] = explode(':', self::DISCIPLINE_BLOCKS[$index]);
            $professor  = $atribuicoes->get($disciplina->id)?->professor?->name ?? '________________________';
            $sheet->setCellValue("{$startCol}{$labelRow}", $professor);
            $sheet->setCellValue("{$startCol}" . ($labelRow + 1), $this->abreviarDisciplina($disciplina));
            $this->style($sheet, "{$startCol}{$labelRow}", [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font'      => ['size' => 8, 'color' => self::CLR_FONT_MAIN],
                'borders'   => ['bottom' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER)],
            ]);
            $this->style($sheet, "{$startCol}" . ($labelRow + 1), [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font'      => ['bold' => true, 'size' => 8, 'color' => self::CLR_HEADER_FONT],
            ]);
        }

        $sheet->getRowDimension($labelRow + 1)->setRowHeight(12);

        // Date + Director + Coordinator rows
        $signRow = $labelRow + 4;
        $sheet->getRowDimension($signRow)->setRowHeight(28);

        $sheet->mergeCells("A{$signRow}:G{$signRow}");
        $sheet->setCellValue("A{$signRow}", now()->format('d/m/Y'));
        $this->style($sheet, "A{$signRow}:G{$signRow}", [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_BOTTOM],
            'font'      => ['size' => 9],
        ]);

        // Director of turma
        $dirTurmaCol  = 'H';
        $dirCursoCol  = 'AQ';
        $dirSecretCol = 'BY';

        foreach ([
            [$dirTurmaCol,  $turma->coordenador?->name ?? '________________________', 'DIRECTOR(A) DE TURMA'],
            [$dirCursoCol,  $turma->curso->coordenador?->name ?? '________________________', 'COORDENADOR(A) DE CURSO'],
            [$dirSecretCol, '________________________', 'SECRETARIA'],
        ] as [$col, $name, $label]) {
            $sheet->setCellValue("{$col}{$signRow}", $name);
            $sheet->setCellValue("{$col}" . ($signRow + 1), $label);

            $this->style($sheet, "{$col}{$signRow}", [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM],
                'font'      => ['size' => 9],
                'borders'   => ['bottom' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER)],
            ]);
            $this->style($sheet, "{$col}" . ($signRow + 1), [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font'      => ['bold' => true, 'size' => 8, 'color' => self::CLR_HEADER_FONT],
            ]);
        }

        $sheet->getRowDimension($signRow + 1)->setRowHeight(14);

        // Light background for footer area
        $this->style($sheet, "A{$footerRow}:{$lastCol}" . ($signRow + 1), [
            'fill' => self::solidFill(self::CLR_FOOTER_BG),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Column widths
    // ─────────────────────────────────────────────────────────────────────────

    private function applyColumnWidths(Worksheet $sheet, Collection $disciplinas): void
    {
        $sheet->getColumnDimension('A')->setWidth(5);   // Nº
        $sheet->getColumnDimension('B')->setWidth(12);  // Nº Processo
        $sheet->getColumnDimension('C')->setWidth(32);  // Nome
        $sheet->getColumnDimension('D')->setWidth(6);   // Sexo

        $labelsPerBlock = 6; // max across all periods – will be trimmed by visibility

        foreach (self::DISCIPLINE_BLOCKS as $index => $range) {
            [$startCol, $endCol] = explode(':', $range);
            $hasDisciplina = $disciplinas->get($index) !== null;

            foreach ($this->columnsInRange($startCol, $endCol) as $pos => $col) {
                if (!$hasDisciplina || $pos >= $labelsPerBlock) {
                    $sheet->getColumnDimension($col)->setVisible(false);
                } else {
                    $sheet->getColumnDimension($col)->setWidth(7.5);
                }
            }
        }

        $sheet->getColumnDimension(self::OBS_COLUMN)->setWidth(10);
        $sheet->getColumnDimension(self::RESULT_COLUMN)->setWidth(12);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Business logic helpers (unchanged from original)
    // ─────────────────────────────────────────────────────────────────────────

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
                'titulo'         => 'PAUTA DE APROVEITAMENTO - Iº TRIMESTRE',
                'subtitulo'      => 'Iº TRIMESTRE',
                'labels'         => ['MAC1', 'PP1', 'PT1', 'MT1'],
                'campos'         => ['mac1', 'pp1', 'pt1', 'mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo'         => 'PAUTA DE APROVEITAMENTO - IIº TRIMESTRE',
                'subtitulo'      => 'IIº TRIMESTRE',
                'labels'         => ['MAC2', 'PP2', 'PT2', 'MT2', 'MFT2'],
                'campos'         => ['mac2', 'pp2', 'pt2', 'mt2', 'mft2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo'         => 'PAUTA DE APROVEITAMENTO - IIIº TRIMESTRE',
                'subtitulo'      => 'IIIº TRIMESTRE',
                'labels'         => ['MAC3', 'PP3', 'MT3', 'CF', 'PG', 'CA'],
                'campos'         => ['mac3', 'pp3', 'mt3', 'cf', 'pg', 'ca'],
                'mostrarResultado' => false,
            ],
            default => [
                'titulo'         => 'PAUTA GERAL DO ANO LETIVO',
                'subtitulo'      => 'RESULTADOS FINAIS',
                'labels'         => ['MT1', 'MT2', 'MT3', 'PG', 'CA', 'CFD'],
                'campos'         => ['mt1', 'mt2', 'mt3', 'pg', 'ca', 'cfd'],
                'mostrarResultado' => true,
            ],
        };
    }

    private function resolverResultadoAluno(Collection $disciplinas, array $notasAluno, bool $mostrarResultado): array
    {
        if (!$mostrarResultado) {
            return ['', ''];
        }

        $temNota = $temPendente = $temReprovacao = $temExame = false;

        foreach ($disciplinas as $disciplina) {
            $nota  = $notasAluno[$disciplina->id] ?? null;
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

    private function ordenarDisciplinas(Collection $disciplinas): Collection
    {
        $ordem = [
            'LINGUA PORTUGUESA' => 10, 'PORTUGUES' => 10,
            'INGLES' => 20, 'FILOSOFIA' => 30, 'HISTORIA' => 40,
            'GEOGRAFIA' => 50, 'EDUCACAO FISICA' => 60, 'MATEMATICA' => 70,
            'FISICA' => 80, 'QUIMICA' => 90, 'BIOLOGIA' => 100,
            'EMPREENDEDORISMO' => 110, 'TIC' => 120,
        ];

        return $disciplinas->sortBy(function (Disciplina $d) use ($ordem) {
            $n    = $this->normalize($d->nome);
            $rank = $ordem[$n] ?? 999;
            return str_pad((string) $rank, 4, '0', STR_PAD_LEFT) . '-' . $n;
        });
    }

    private function abreviarDisciplina(Disciplina $disciplina): string
    {
        $aliases = [
            'LINGUA PORTUGUESA' => 'L. PORTUGUESA',
            'INGLES'            => 'INGLÊS',
            'EDUCACAO FISICA'   => 'ED. FÍSICA',
            'MATEMATICA'        => 'MATEMAT.',
            'FISICA'            => 'FÍSICA',
            'QUIMICA'           => 'QUÍMICA',
            'EMPREENDEDORISMO'  => 'EMPREEND.',
        ];

        $normalized = $this->normalize($disciplina->nome);
        return $aliases[$normalized] ?? Str::upper(Str::limit($disciplina->nome, 18, ''));
    }

    private function normalize(string $value): string
    {
        return Str::upper(Str::ascii($value));
    }

    private function columnsInRange(string $startCol, string $endCol): array
    {
        $start   = Coordinate::columnIndexFromString($startCol);
        $end     = Coordinate::columnIndexFromString($endCol);
        $columns = [];
        for ($c = $start; $c <= $end; $c++) {
            $columns[] = Coordinate::stringFromColumnIndex($c);
        }
        return $columns;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Style helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apply a flat style array to a range.
     * Accepts the same shorthand structure PhpSpreadsheet understands:
     *   font, fill, alignment, borders  →  with 'color' as plain hex string.
     */
    private function style(Worksheet $sheet, string $range, array $def): void
    {
        // Translate plain hex colour strings to ARGB arrays PhpSpreadsheet wants
        array_walk_recursive($def, function (&$v, $k) {
            if ($k === 'color' && is_string($v) && strlen($v) === 6) {
                $v = ['rgb' => $v];
            }
        });

        $sheet->getStyle($range)->applyFromArray($def);
    }

    private function headerCellStyle(): array
    {
        return [
            'font'      => ['bold' => true, 'size' => 9, 'color' => self::CLR_HEADER_FONT],
            'fill'      => self::solidFill(self::CLR_HEADER_BG),
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER),
                'outline'    => self::borderDef(Border::BORDER_MEDIUM, self::CLR_BORDER),
            ],
        ];
    }

    private static function solidFill(string $hex): array
    {
        return [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $hex],
        ];
    }

    private static function borderDef(string $style, string $hex): array
    {
        return [
            'borderStyle' => $style,
            'color'       => ['rgb' => $hex],
        ];
    }
}