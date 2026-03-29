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

class PautaGeralTemplateExporter
{
    // ── Layout constants ────────────────────────────────────────────────────
    private const DATA_START_ROW   = 15;
    private const OBS_COLUMN      = 'CJ';
    private const RESULT_COLUMN   = 'CK';

    /**
     * Cada disciplina ocupa um bloco de 7 colunas:
     * FALTAS(J), FALTAS(I), MfT2, MAC, NPT, NPT, MT
     * Blocos: E:K, L:R, S:Y, Z:AF, AG:AM, AN:AS, AT:AY, AZ:BF, BG:BM, BN:BS, BT:BZ, CA:CG
     */
    private const DISCIPLINE_BLOCKS = [
        'E:K',  'L:R',  'S:Y',  'Z:AF',
        'AG:AM','AN:AS','AT:AY','AZ:BF',
        'BG:BM','BN:BS','BT:BZ','CA:CG',
    ];

    // ── Cores ─────────────────────────────────────────────────────────────
    private const CLR_HEADER_BG    = 'D9D9D9'; // Cinza claro estilo pauta oficial
    private const CLR_HEADER_FONT  = '000000';
    private const CLR_SUBHEADER    = 'E6E6E6';
    private const CLR_BORDER       = '000000';
    private const CLR_LIGHT_BORDER = '808080';
    private const CLR_ROW_ALT      = 'F9F9F9';
    private const CLR_FOOTER_BG    = 'F5F5F5';
    private const CLR_FONT_MAIN    = '000000';
    private const CLR_APROVADO     = '008000';
    private const CLR_REPROVADO    = 'CC0000';

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

            $filename = 'pauta-' . Str::slug($dados['turma']->nome_completo ?? $dados['turma']->nome, '-') . '.xlsx';

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
        $sheet->setTitle('Pauta');

        $studentCount = $alunos->count();
        $lastDataRow  = self::DATA_START_ROW + $studentCount - 1;
        $footerRow    = $lastDataRow + 3;

        // Build sections
        $this->buildPageSetup($sheet);
        $this->buildCabecalho($sheet, $turma, $anoLetivo, $config);
        $this->buildDisciplinaHeaders($sheet, $disciplinas, $config, $atribuicoes);
        $this->buildAlunoRows($sheet, $alunos, $disciplinas, $notasIndex, $config, $lastDataRow);
        $this->buildRodape($sheet, $turma, $footerRow, $atribuicoes, $disciplinas, $config);
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
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.5)->setBottom(0.8)
            ->setLeft(0.4)->setRight(0.4);

        $sheet->getSheetView()->setZoomScale(75);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cabeçalho institucional (rows 1–14)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildCabecalho(
        Worksheet $sheet,
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        array $config
    ): void {
        $lastCol = self::RESULT_COLUMN;

        // ── Row 1: Institution banner ──
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'INSTITUTO POLITÉCNICO INDUSTRIAL DO KILAMBA KIAXI Nº 8056 "NOVA VIDA"');
        $this->style($sheet, "A1:{$lastCol}1", [
            'font'      => ['bold' => true, 'size' => 11, 'color' => self::CLR_HEADER_FONT, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Row 2: Director signature line ──
        $sheet->mergeCells("B2:C2");
        $sheet->setCellValue('B2', 'O DIRECTOR');
        $sheet->getStyle('B2')->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CLR_BORDER]]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Row 3: Title + Ano Lectivo ──
        $sheet->mergeCells("A3:D3");
        $sheet->mergeCells("E3:{$lastCol}3");
        $sheet->setCellValue('A3', 'Ano Lectivo:');
        $sheet->setCellValue('E3', $config['titulo']);

        $this->style($sheet, 'A3', ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial']]);
        $this->style($sheet, "E3:{$lastCol}3", [
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // ── Row 4: Director name ──
        $sheet->mergeCells("B4:C4");
        $sheet->setCellValue('B4', 'Ferreira Manuel Fragoso Ph,D');
        $sheet->getStyle('B4')->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(16);

        // ── Row 5: Date line ──
        $sheet->mergeCells("A5:C5");
        $sheet->setCellValue('A5', 'Data: _____/_____/_________');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(16);

        // ── Row 6: Classe / Turma / Área / Curso ──
        $sheet->mergeCells("A6:{$lastCol}6");
        $sheet->setCellValue('A6', sprintf(
            '%sª Classe   TURMA: %s   ÁREA: %s   CURSO: %s',
            $turma->classe,
            $turma->nome,
            $turma->curso?->nome ?? '-',
            $turma->curso?->nome ?? '-'
        ));
        $this->style($sheet, "A6:{$lastCol}6", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(18);

        // ── Row 7: Código da Turma / Sala / Área / Curso (repetido) ──
        $sheet->mergeCells("A7:{$lastCol}7");
        $sheet->setCellValue('A7', sprintf(
            '%s   SALA: %s   ÁREA: %s   CURSO: %s',
            strtoupper($turma->nome . $turma->classe),
            $turma->sala ?? '08',
            $turma->curso?->nome ?? '-',
            $turma->curso?->nome ?? '-'
        ));
        $this->style($sheet, "A7:{$lastCol}7", [
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(16);

        // ── Rows 8–10: Blank spacers ──
        foreach ([8, 9, 10] as $row) {
            $sheet->getRowDimension($row)->setRowHeight(6);
        }

        // ── Rows 11–14: Fixed left headers (Nº, Proc, Nome, Sexo) + OBS/RESULT ──
        $this->buildFixedHeaders($sheet);
    }

    private function buildFixedHeaders(Worksheet $sheet): void
    {
        // Merge A11:A14 → "Nº ORD"
        $sheet->mergeCells('A11:A14');
        $sheet->setCellValue('A11', "Nº\nORD");
        $this->style($sheet, 'A11:A14', $this->headerCellStyle());

        // Merge B11:B14 → "Nº PROC."
        $sheet->mergeCells('B11:B14');
        $sheet->setCellValue('B11', "Nº\nPROC.");
        $this->style($sheet, 'B11:B14', $this->headerCellStyle());

        // Merge C11:C14 → "NOME COMPLETO"
        $sheet->mergeCells('C11:C14');
        $sheet->setCellValue('C11', 'NOME COMPLETO');
        $this->style($sheet, 'C11:C14', $this->headerCellStyle());

        // Merge D11:D14 → "SEXO"
        $sheet->mergeCells('D11:D14');
        $sheet->setCellValue('D11', 'SEXO');
        $this->style($sheet, 'D11:D14', $this->headerCellStyle());

        // Merge OBS column rows 11–14
        $sheet->mergeCells(self::OBS_COLUMN . '11:' . self::OBS_COLUMN . '14');
        $sheet->setCellValue(self::OBS_COLUMN . '11', 'OBSERV.');
        $this->style($sheet, self::OBS_COLUMN . '11:' . self::OBS_COLUMN . '14', $this->headerCellStyle());

        // Merge RESULT column rows 11–14
        $sheet->mergeCells(self::RESULT_COLUMN . '11:' . self::RESULT_COLUMN . '14');
        $sheet->setCellValue(self::RESULT_COLUMN . '11', 'RESULTADO');
        $this->style($sheet, self::RESULT_COLUMN . '11:' . self::RESULT_COLUMN . '14', $this->headerCellStyle());

        // Row heights for discipline header rows
        $sheet->getRowDimension(11)->setRowHeight(14);
        $sheet->getRowDimension(12)->setRowHeight(14);
        $sheet->getRowDimension(13)->setRowHeight(28); // Discipline name (taller)
        $sheet->getRowDimension(14)->setRowHeight(20); // Labels row
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Discipline column headers (rows 11–14)
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

            $disciplina = $disciplinas->get($index);

            if (!$disciplina) {
                // Hide entire block
                foreach ($allCols as $col) {
                    $sheet->getColumnDimension($col)->setVisible(false);
                }
                continue;
            }

            // ── Rows 11–12: blank (reserved for fixed headers on left) ──
            // ── Row 13: Discipline name merged across all 7 cols ──
            $mergeRange = "{$startCol}13:{$endCol}13";
            $sheet->mergeCells($mergeRange);
            $sheet->setCellValue("{$startCol}13", $this->abreviarDisciplina($disciplina));
            $this->style($sheet, $mergeRange, [
                'font'      => ['bold' => true, 'size' => 9, 'color' => self::CLR_HEADER_FONT, 'name' => 'Arial'],
                'fill'      => self::solidFill(self::CLR_HEADER_BG),
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => [
                    'allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER),
                ],
            ]);

            // ── Row 14: Labels (FALTAS J, FALTAS I, MfT2, MAC, NPT, NPT, MT) ──
            $labels = $config['labels'] ?? ['F.J', 'F.I', 'MfT2', 'MAC', 'NPT', 'NPT', 'MT'];
            foreach ($allCols as $pos => $col) {
                $label = $labels[$pos] ?? '';
                $sheet->setCellValue("{$col}14", $label);
                $this->style($sheet, "{$col}14", [
                    'font'      => ['bold' => true, 'size' => 8, 'color' => self::CLR_HEADER_FONT, 'name' => 'Arial'],
                    'fill'      => self::solidFill(self::CLR_SUBHEADER),
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER)],
                ]);
            }
        }

        // OBS / RESULTADO header styles for label row
        $this->style($sheet, self::OBS_COLUMN . '14', [
            'fill'    => self::solidFill(self::CLR_SUBHEADER),
            'borders' => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER)],
        ]);
        $this->style($sheet, self::RESULT_COLUMN . '14', [
            'fill'    => self::solidFill(self::CLR_SUBHEADER),
            'borders' => ['allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER)],
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
        $lastCol = self::RESULT_COLUMN;

        foreach ($alunos as $offset => $aluno) {
            $row    = self::DATA_START_ROW + $offset;
            $isAlt  = ($offset % 2 === 1);
            $fillHex = $isAlt ? self::CLR_ROW_ALT : 'FFFFFF';

            // Base row style
            $sheet->getRowDimension($row)->setRowHeight(18);
            $this->style($sheet, "A{$row}:{$lastCol}{$row}", [
                'fill'      => self::solidFill($fillHex),
                'font'      => ['size' => 9, 'color' => self::CLR_FONT_MAIN, 'name' => 'Arial'],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => [
                    'allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_LIGHT_BORDER),
                ],
            ]);

            // Fixed columns
            $sheet->setCellValueExplicit("A{$row}", (string) ($offset + 1), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$row}", (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", $aluno->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", strtoupper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);

            $this->style($sheet, "A{$row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $this->style($sheet, "B{$row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $this->style($sheet, "D{$row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $notasAluno = $notasIndex[$aluno->id] ?? [];

            // Note columns per discipline
            foreach ($disciplinas->values() as $index => $disciplina) {
                [$startCol, $endCol] = explode(':', self::DISCIPLINE_BLOCKS[$index]);
                $allCols = $this->columnsInRange($startCol, $endCol);
                $nota = $notasAluno[$disciplina->id] ?? null;

                // Campos do 3º trimestre conforme solicitado: MAC3, PP3, (sem PT3), PG atribuída
                $campos = $config['campos'] ?? ['faltas_j', 'faltas_i', 'mft2', 'mac3', 'pp3', 'pg', 'mt3'];

                foreach ($allCols as $pos => $col) {
                    $campo = $campos[$pos] ?? null;
                    $value = null;

                    if ($campo) {
                        if (Str::startsWith($campo, 'faltas')) {
                            $value = ''; // Faltas podem ser preenchidas manualmente
                        } elseif ($campo === 'mft2') {
                            $value = $nota?->mft2;
                        } elseif ($campo === 'pg') {
                            // PG é ATRIBUÍDA, não calculada - exibe o valor direto
                            $value = $nota?->pg;
                        } elseif ($campo === 'mt3') {
                            // MT3 calculado apenas com MAC3+PP3 (sem PT3)
                            $value = $nota?->mt3;
                        } else {
                            $value = $nota?->{$campo};
                        }
                    }

                    if ($value === null || $value === '') {
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
                $disciplinas, $notasAluno, $config['mostrarResultado'] ?? false, $aluno
            );

            $sheet->setCellValueExplicit(self::OBS_COLUMN . $row, $obs, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(self::RESULT_COLUMN . $row, $resultado, DataType::TYPE_STRING);

            // Cor do resultado
            $resultadoStyle = [
                'font'      => ['bold' => !empty($resultado), 'size' => 9, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            if (stripos($resultado, 'transita') !== false || stripos($resultado, 'aprov') !== false) {
                $resultadoStyle['font']['color'] = ['rgb' => self::CLR_APROVADO];
            } elseif (stripos($resultado, 'não transita') !== false || stripos($resultado, 'reprov') !== false) {
                $resultadoStyle['font']['color'] = ['rgb' => self::CLR_REPROVADO];
            }

            $this->style($sheet, self::RESULT_COLUMN . $row, $resultadoStyle);
            $this->style($sheet, self::OBS_COLUMN . $row, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 9, 'name' => 'Arial'],
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
    // Rodapé (assinaturas dos professores e direção)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildRodape(
        Worksheet $sheet,
        Turma $turma,
        int $footerRow,
        Collection $atribuicoes,
        Collection $disciplinas,
        array $config
    ): void {
        $lastCol = self::RESULT_COLUMN;

        // Blank gap row
        $sheet->getRowDimension($footerRow - 1)->setRowHeight(8);

        // Row: "Data do Conselho de Turma" + nomes dos professores por disciplina
        $sheet->mergeCells("A{$footerRow}:D{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'Data do Conselho de Turma');
        $this->style($sheet, "A{$footerRow}:D{$footerRow}", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $dateRow = $footerRow + 1;
        $sheet->mergeCells("A{$dateRow}:D{$dateRow}");
        $sheet->setCellValue("A{$dateRow}", '_____/_____/________');
        $this->style($sheet, "A{$dateRow}:D{$dateRow}", [
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Professor names under each discipline block
        foreach ($disciplinas->values() as $index => $disciplina) {
            [$startCol] = explode(':', self::DISCIPLINE_BLOCKS[$index]);
            $professor  = $atribuicoes->get($disciplina->id)?->professor?->name ?? '';
            
            if ($professor) {
                $sheet->setCellValue("{$startCol}{$footerRow}", $professor);
                $this->style($sheet, "{$startCol}{$footerRow}", [
                    'font' => ['size' => 8, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        $sheet->getRowDimension($footerRow)->setRowHeight(14);
        $sheet->getRowDimension($dateRow)->setRowHeight(14);

        // Observações
        $obsStart = $footerRow + 3;
        $sheet->mergeCells("A{$obsStart}:{$lastCol}{$obsStart}");
        $sheet->setCellValue("A{$obsStart}", 'Observações:');
        $this->style($sheet, "A{$obsStart}", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
        ]);

        $obsTexts = [
            'Na coluna Resultado utilizar: Transita; Não Transita; Anulação de matrícula (AM); Transferido.',
            'Na coluna CF indicar EEF (excluído por excesso de faltas) quando for o caso.',
            'Na coluna Observações escrever "Exame" quando o aluno não obteve aprovação a alguma disciplina terminal e o Regime de Avaliação dos Alunos o permitir.',
        ];

        foreach ($obsTexts as $i => $text) {
            $row = $obsStart + 1 + $i;
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->setCellValue("A{$row}", $text);
            $this->style($sheet, "A{$row}", [
                'font' => ['size' => 8, 'italic' => true, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }

        // Assinaturas: Diretor de Turma, Coordenador de Curso, Subdirector Pedagógico
        $sigStart = $obsStart + 5;
        $sheet->getRowDimension($sigStart)->setRowHeight(8);

        // Director de Turma
        $sigRow = $sigStart + 1;
        $sheet->mergeCells("A{$sigRow}:G{$sigRow}");
        $sheet->setCellValue("A{$sigRow}", 'O DIRECTOR DE TURMA');
        $this->style($sheet, "A{$sigRow}", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $lineRow = $sigRow + 1;
        $sheet->mergeCells("A{$lineRow}:G{$lineRow}");
        $sheet->getStyle("A{$lineRow}")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CLR_BORDER]]],
        ]);

        $nameRow = $lineRow + 1;
        $sheet->mergeCells("A{$nameRow}:G{$nameRow}");
        $sheet->setCellValue("A{$nameRow}", $turma->coordenador?->name ?? '_____________________________');
        $this->style($sheet, "A{$nameRow}", [
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Coordenador de Curso (coluna central)
        $cursoCol = 'AN';
        $sheet->mergeCells("{$cursoCol}{$sigRow}:{$cursoCol}G{$sigRow}"); // Ajustar merge para coluna única
        $sheet->setCellValue("{$cursoCol}{$sigRow}", 'O COORDENADOR DE CURSO');
        $this->style($sheet, "{$cursoCol}{$sigRow}", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("{$cursoCol}{$lineRow}")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CLR_BORDER]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->setCellValue("{$cursoCol}{$nameRow}", $turma->curso?->coordenador?->name ?? '_____________________________');
        $this->style($sheet, "{$cursoCol}{$nameRow}", [
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Subdirector Pedagógico (coluna direita)
        $subCol = 'BY';
        $sheet->setCellValue("{$subCol}{$sigRow}", 'O SUBDIRECTOR PEDAGÓGICO');
        $this->style($sheet, "{$subCol}{$sigRow}", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("{$subCol}{$lineRow}")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CLR_BORDER]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->setCellValue("{$subCol}{$nameRow}", 'Carlos Alberto Brito Teixeira da Silva');
        $this->style($sheet, "{$subCol}{$nameRow}", [
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // Light background for footer area
        $this->style($sheet, "A{$footerRow}:{$lastCol}" . ($nameRow + 2), [
            'fill' => self::solidFill(self::CLR_FOOTER_BG),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Column widths
    // ─────────────────────────────────────────────────────────────────────────

    private function applyColumnWidths(Worksheet $sheet, Collection $disciplinas): void
    {
        $sheet->getColumnDimension('A')->setWidth(6);    // Nº ORD
        $sheet->getColumnDimension('B')->setWidth(14);   // Nº PROC.
        $sheet->getColumnDimension('C')->setWidth(38);   // NOME COMPLETO
        $sheet->getColumnDimension('D')->setWidth(6);    // SEXO

        // Discipline blocks: 7 cols each (F.J, F.I, MfT2, MAC, NPT, NPT, MT)
        foreach (self::DISCIPLINE_BLOCKS as $index => $range) {
            [$startCol, $endCol] = explode(':', $range);
            $hasDisciplina = $disciplinas->get($index) !== null;

            foreach ($this->columnsInRange($startCol, $endCol) as $pos => $col) {
                if (!$hasDisciplina) {
                    $sheet->getColumnDimension($col)->setVisible(false);
                } else {
                    // Faltas mais estreitas, notas um pouco mais largas
                    $width = in_array($pos, [0, 1]) ? 6 : 8;
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            }
        }

        $sheet->getColumnDimension(self::OBS_COLUMN)->setWidth(12);   // OBSERV.
        $sheet->getColumnDimension(self::RESULT_COLUMN)->setWidth(14); // RESULTADO
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Business logic helpers
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
                'labels'         => ['F.J', 'F.I', 'MfT2', 'MAC1', 'PP1', 'PT1', 'MT1'],
                'campos'         => ['faltas_j', 'faltas_i', 'mft2', 'mac1', 'pp1', 'pt1', 'mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo'         => 'PAUTA DE APROVEITAMENTO - IIº TRIMESTRE',
                'labels'         => ['F.J', 'F.I', 'MfT2', 'MAC2', 'PP2', 'PT2', 'MT2'],
                'campos'         => ['faltas_j', 'faltas_i', 'mft2', 'mac2', 'pp2', 'pt2', 'mt2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo'         => 'PAUTA DE APROVEITAMENTO - IIIº TRIMESTRE',
                'labels'         => ['F.J', 'F.I', 'MfT2', 'MAC3', 'PP3', 'PG', 'MT3'],
                'campos'         => ['faltas_j', 'faltas_i', 'mft2', 'mac3', 'pp3', 'pg', 'mt3'],
                'mostrarResultado' => false,
            ],
            default => [
                'titulo'         => 'PAUTA GERAL DO ANO LETIVO',
                'labels'         => ['F.J', 'F.I', 'CF', 'MT1', 'MT2', 'PG', 'CFD'],
                'campos'         => ['faltas_j', 'faltas_i', 'cf', 'mt1', 'mt2', 'pg', 'cfd'],
                'mostrarResultado' => true,
            ],
        };
    }

    private function resolverResultadoAluno(
        Collection $disciplinas,
        array $notasAluno,
        bool $mostrarResultado,
        $aluno = null
    ): array {
        if (!$mostrarResultado) {
            return ['', ''];
        }

        $temNota = $temPendente = $temReprovacao = $temExame = $temEEF = false;

        foreach ($disciplinas as $disciplina) {
            $nota  = $notasAluno[$disciplina->id] ?? null;
            $cfd   = $nota?->cfd;
            $cf    = $nota?->cf;

            // Verificar EEF (excesso de faltas) - se CF for "EEF" ou se faltas > limite
            if ($cf === 'EEF' || (is_string($cf) && strtoupper($cf) === 'EEF')) {
                $temEEF = true;
                continue;
            }

            if ($cfd === null && $cf === null) {
                $temPendente = true;
                continue;
            }

            $temNota = true;
            $valorFinal = $cfd ?? $cf;

            if ($valorFinal !== null && (float) $valorFinal < 10) {
                $temReprovacao = true;
                if ($disciplina->disciplina_terminal) {
                    $temExame = true;
                }
            }
        }

        if (!$temNota || $temPendente) {
            return ['', ''];
        }

        // EEF tem prioridade
        if ($temEEF) {
            return ['EEF', 'Não Transita'];
        }

        if (!$temReprovacao) {
            return ['', 'Transita'];
        }

        return [$temExame ? 'Exame' : '', 'Não Transita'];
    }

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
            'INFORMATICA'       => 'INF.',
            'TECNOLOGIAS INFORMACAO' => 'TIC',
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

    private function style(Worksheet $sheet, string $range, array $def): void
    {
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
            'font'      => ['bold' => true, 'size' => 9, 'color' => self::CLR_HEADER_FONT, 'name' => 'Arial'],
            'fill'      => self::solidFill(self::CLR_HEADER_BG),
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => self::borderDef(Border::BORDER_THIN, self::CLR_BORDER),
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