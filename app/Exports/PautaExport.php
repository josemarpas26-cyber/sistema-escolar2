<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * PautaExport
 *
 * Generates a single-discipline grade sheet (pauta) that matches the
 * official layout of the school's xlsx template exactly.
 *
 * Layout (rows):
 *   1       – blank
 *   2       – blank
 *   3       – blank
 *   4       – blank
 *   5       – "O DIRECTOR" (col C) | Institution name (col H, merged H5:U5)
 *   6       – Director signature line | "Ano Lectivo X" | PAUTA title (col H merged)
 *   7       – Director name
 *   8       – "Data:" | Classe / Turma / Área / Curso line
 *   9       – blank
 *   10      – blank
 *   11      – blank
 *   12      – Discipline name header (merged E12:T12, theme-colour background)
 *   13–14   – Two-row column headers
 *   15..N   – One row per student
 *   N+1     – "Data do Conselho de Turma" + teacher names per discipline
 *   N+2     – Date blank
 *   N+3     – blank
 *   N+4     – "Observações:"
 *   N+5     – blank
 *   N+6     – "O DIRECTOR DE TURMA" | "O COORDENADOR DE CURSO" | "O SUBDIRECTOR PEDAGÓGICO"
 *   N+7     – signature lines
 *   N+8     – names
 *   N+9     – blank
 *   N+10    – footnote 1
 *   N+11    – footnote 2
 *   N+12    – footnote 3
 *
 * Column layout (A–V, 22 columns):
 *   A  – Nº (ORD)
 *   B  – Nº PROC.
 *   C  – NOME COMPLETO
 *   D  – SEXO
 *   E  – FALTAS J  (merged E13:F13 label, E14='J')
 *   F  – FALTAS I  (F14='I')
 *   G  – MfT2
 *   H  – MAC1 (1.º Trim)
 *   I  – NPT1
 *   J  – NPT2  (1.º Trim)
 *   K  – MT1  ← highlighted (theme accent)
 *   L  – MAC2 (2.º Trim)
 *   M  – NPT1
 *   N  – NPT2 (2.º Trim)
 *   O  – MT2  ← highlighted
 *   P  – MAC3 (3.º Trim)
 *   Q  – NPT1
 *   R  – PG (Prova Global / NPT2)
 *   S  – MT3  ← highlighted
 *   T  – CF
 *   U  – OBSERV.
 *   V  – RESULTADO
 */
class PautaExport implements FromArray, WithStyles, WithTitle, WithEvents, WithColumnWidths
{
    protected $turma;
    protected $disciplina;
    protected $notas;

    // ── Colours ─────────────────────────────────────────────────────────────
    // Theme index 3  →  light-blue accent (discipline name header row 12)
    // Theme index 5  →  accent (MT / media columns)
    // We use explicit ARGB hex equivalents so openpyxl-generated files look right
    // when not relying on theme resolution:
    private const DISC_HEADER_BG = 'FFD6E4BC'; // theme:3 approximation (light green-blue)
    private const MT_COL_BG      = 'FFCFE2F3'; // theme:5 approximation (light blue)
    private const WHITE          = 'FFFFFFFF';
    private const BLACK          = 'FF000000';
    private const RED            = 'FFFF0000';

    // ── Fixed rows ───────────────────────────────────────────────────────────
    private const ROW_DIRECTOR_LABEL  = 5;
    private const ROW_PAUTA_TITLE     = 6;
    private const ROW_DIRECTOR_NAME   = 7;
    private const ROW_CLASSE          = 8;
    private const ROW_DISC_HEADER     = 12;
    private const ROW_COL_HEADER1     = 13;
    private const ROW_COL_HEADER2     = 14;
    private const ROW_DATA_START      = 15;

    // ── Last data column ─────────────────────────────────────────────────────
    private const LAST_COL = 'V'; // column 22

    public function __construct($turma, $disciplina, $notas)
    {
        $this->turma      = $turma;
        $this->disciplina = $disciplina;
        $this->notas      = $notas;
    }

    // =========================================================================
    // DATA
    // =========================================================================

    public function array(): array
    {
        $rows = [];

        // Rows 1-4 blank
        for ($i = 0; $i < 4; $i++) {
            $rows[] = $this->blankRow();
        }

        // Row 5 – Director label | Institution
        $rows[] = $this->makeRow([
            'C' => 'O DIRECTOR',
            'H' => 'INSTITUTO POLITÉCNICO INDUSTRIAL DO KILAMBA KIAXI Nº 8056 "NOVA VIDA"',
        ]);

        // Row 6 – Director signature | Ano Lectivo | PAUTA title
        $anoLetivo = $this->turma->anoLetivo->nome ?? '2022/2023';
        $trimestre = $this->disciplina->trimestre ?? 'IIIº';
        $rows[] = $this->makeRow([
            'C' => '______________________________',
            'E' => 'Ano Lectivo:',
            'F' => $anoLetivo,
            'H' => "PAUTA DE APROVEITAMENTO - {$trimestre} TRIMESTRE",
        ]);

        // Row 7 – Director name
        $rows[] = $this->makeRow([
            'C' => $this->turma->director?->name ?? 'Ferreira Manuel Fragoso  Ph,D',
        ]);

        // Row 8 – Date | Classe / Turma / Área / Curso
        $classe  = $this->turma->classe ?? '10';
        $turmaId = $this->turma->nome ?? 'GSI10AM';
        $area    = $this->turma->area ?? 'INFORMÁTICA';
        $curso   = $this->turma->curso->nome ?? 'TÉCNICO DE GESTÃO DE SISTEMAS INFORMÁTICOS';
        $sala    = $this->turma->sala ?? '08';
        $codDisc = $this->disciplina->codigo ?? 'INF10AM';

        $rows[] = $this->makeRow([
            'C' => 'Data: _____/_____/_________',
            'E' => "{$classe}ª Classe   TURMA: {$turmaId}   ÁREA: {$area}   CURSO: {$curso}",
            'M' => "{$codDisc}   SALA: {$sala}   ÁREA: {$area}   CURSO: {$curso}",
        ]);

        // Rows 9-11 blank
        for ($i = 0; $i < 3; $i++) {
            $rows[] = $this->blankRow();
        }

        // Row 12 – Discipline name (data placed in col E; will be merged E12:V12 via styles)
        $rows[] = $this->makeRow([
            'E' => strtoupper($this->disciplina->nome),
        ]);

        // Row 13 – Header line 1
        $rows[] = $this->makeRow([
            'A' => 'Nº',
            'B' => 'Nº',
            'C' => 'NOME COMPLETO',
            'D' => 'SEXO',
            'E' => 'FALTAS',
            'G' => 'MfT2',
            'H' => 'MAC1',
            'I' => 'NPT1',
            'J' => 'NPT2',
            'K' => 'MT1',
            'L' => 'MAC2',
            'M' => 'NPT1',
            'N' => 'NPT2',
            'O' => 'MT2',
            'P' => 'MAC3',
            'Q' => 'NPT1',
            'R' => 'PG',
            'S' => 'MT3',
            'T' => 'CF',
            'U' => 'OBSERV.',
            'V' => 'RESULTADO',
        ]);

        // Row 14 – Header line 2 (sub-labels)
        $rows[] = $this->makeRow([
            'A' => 'ORD',
            'B' => 'PROC.',
            'E' => 'J',
            'F' => 'I',
        ]);

        // ── Student rows ──────────────────────────────────────────────────────
        $counter = 1;
        foreach ($this->notas as $nota) {
            $isFirst = ($counter === 1);
            $row = $this->makeRow([
                'A' => $isFirst ? 1 : "=A" . (self::ROW_DATA_START + $counter - 2) . "+1",
                'B' => $nota->aluno->numero_processo ?? '',
                'C' => $nota->aluno->name ?? '',
                'D' => $nota->aluno->genero ?? '',
                'E' => $nota->faltas_justificadas ?? '',
                'F' => $nota->faltas_injustificadas ?? '',
                'G' => $this->n($nota->mft2),
                'H' => $this->n($nota->mac1),
                'I' => $this->n($nota->pp1),
                'J' => $this->n($nota->pt1),
                'K' => $this->calcOrValue($nota, 'mt1', 'J', 'I', 'H', self::ROW_DATA_START + $counter - 1),
                'L' => $this->n($nota->mac2),
                'M' => $this->n($nota->pp2),
                'N' => $this->n($nota->pt2),
                'O' => $this->calcOrValue($nota, 'mt2', 'N', 'M', 'L', self::ROW_DATA_START + $counter - 1),
                'P' => $this->n($nota->mac3),
                'Q' => $this->n($nota->pp3),
                'R' => $this->n($nota->pg),
                'S' => $this->calcOrValue($nota, 'mt3', 'R', 'Q', 'P', self::ROW_DATA_START + $counter - 1),
                'T' => $this->n($nota->cf),
                'U' => $nota->observacao ?? '',
                'V' => $nota->resultado ?? ($nota->isAprovado() ? 'Transita' : 'Não Transita'),
            ]);
            $rows[] = $row;
            $counter++;
        }

        $lastDataRow = self::ROW_DATA_START + count($this->notas) - 1;

        // ── Teacher / council row ─────────────────────────────────────────────
        $professor = $this->disciplina->professor?->name ?? '';
        $rows[] = $this->makeRow([
            'A' => 'Data do Conselho de Turma',
            'E' => $professor,
        ]);

        // Date blank
        $rows[] = $this->makeRow(['A' => '_____/______/________']);

        // Blank
        $rows[] = $this->blankRow();

        // Observações
        $rows[] = $this->makeRow(['A' => 'Observações:']);

        // Blank
        $rows[] = $this->blankRow();

        // Signatures row 1 – titles
        $rows[] = $this->makeRow([
            'D'  => 'O DIRECTOR DE TURMA',
            'L'  => 'O COORDENADOR DE CURSO',
            'S'  => 'O SUBDIRECTOR PEDAGÓGICO',
        ]);

        // Signatures row 2 – underlines
        $rows[] = $this->makeRow([
            'D'  => '_________________________________________',
            'L'  => '____________________________________________',
            'S'  => '____________________________________',
        ]);

        // Signatures row 3 – names
        $dirTurma    = $this->turma->coordenador?->name ?? '';
        $coordCurso  = $this->turma->curso?->coordenador?->name ?? 'Manuel Gonçalves Victor';
        $subDirector = 'Carlos Alberto Brito Teixeira da Silva';

        $rows[] = $this->makeRow([
            'D' => $dirTurma,
            'L' => $coordCurso,
            'S' => $subDirector,
        ]);

        // Blank
        $rows[] = $this->blankRow();

        // Footnotes
        $rows[] = $this->makeRow(['A' => 'Na coluna Resultado utilizar: Transita; Não Transita; Anulação de matrícula (AM); Transferido.']);
        $rows[] = $this->makeRow(['A' => 'Na coluna CF indicar EEF (excluído por excesso de faltas) quando for o caso.']);
        $rows[] = $this->makeRow(['A' => 'Na coluna Observações escrever "Exame" quando o aluno não obteve aprovação a alguma disciplina terminal e o Regime de Avaliação dos Alunos o permitir.']);

        return $rows;
    }

    // =========================================================================
    // COLUMN WIDTHS
    // =========================================================================

    public function columnWidths(): array
    {
        return [
            'A' => 4.3,
            'B' => 7.0,
            'C' => 37.5,
            'D' => 3.3,
            'E' => 3.5,
            'F' => 3.5,
            'G' => 4.0,
            'H' => 5.5,
            'I' => 5.5,
            'J' => 5.5,
            'K' => 5.5,
            'L' => 5.5,
            'M' => 5.5,
            'N' => 5.5,
            'O' => 5.5,
            'P' => 5.5,
            'Q' => 5.5,
            'R' => 5.5,
            'S' => 5.5,
            'T' => 5.5,
            'U' => 10.0,
            'V' => 12.0,
        ];
    }

    // =========================================================================
    // STYLES
    // =========================================================================

    public function styles(Worksheet $sheet): array
    {
        $nStudents   = count($this->notas);
        $lastDataRow = self::ROW_DATA_START + $nStudents - 1;

        // ── Row 5 – Director / Institution ───────────────────────────────────
        $sheet->mergeCells('H5:V5');
        $sheet->getStyle('C5')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);
        $sheet->getStyle('H5')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Row 6 – Pauta title ───────────────────────────────────────────────
        $sheet->mergeCells('H6:V6');
        $sheet->getStyle('H6')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('C6:G6')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);

        // ── Row 7 – Director name ─────────────────────────────────────────────
        $sheet->getStyle('C7')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);

        // ── Row 8 – Classe info ───────────────────────────────────────────────
        $sheet->mergeCells('E8:L8');
        $sheet->mergeCells('M8:V8');
        $sheet->getStyle('C8:V8')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);

        // ── Row 12 – Discipline header ────────────────────────────────────────
        $sheet->mergeCells('E12:V12');
        $sheet->getStyle('E12:V12')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8, 'bold' => false],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::DISC_HEADER_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
        $sheet->getRowDimension(12)->setRowHeight(13.5);

        // ── Rows 13–14 – Column headers ───────────────────────────────────────
        // Merges matching the original: each data column spans rows 13:14 except
        // "FALTAS" which spans E13:F13 and J13:K13 for sub-labels.
        $sheet->mergeCells('A13:A14');
        $sheet->mergeCells('B13:B14');
        $sheet->mergeCells('C13:C14');
        $sheet->mergeCells('D13:D14');
        $sheet->mergeCells('E13:F13'); // FALTAS label (J & I split in row 14)
        $sheet->mergeCells('G13:G14');
        $sheet->mergeCells('H13:H14');
        $sheet->mergeCells('I13:I14');
        $sheet->mergeCells('J13:J14');
        $sheet->mergeCells('K13:K14');
        $sheet->mergeCells('L13:L14');
        $sheet->mergeCells('M13:M14');
        $sheet->mergeCells('N13:N14');
        $sheet->mergeCells('O13:O14');
        $sheet->mergeCells('P13:P14');
        $sheet->mergeCells('Q13:Q14');
        $sheet->mergeCells('R13:R14');
        $sheet->mergeCells('S13:S14');
        $sheet->mergeCells('T13:T14');
        $sheet->mergeCells('U13:U14');
        $sheet->mergeCells('V13:V14');

        $headerStyle = [
            'font' => ['name' => 'Arial', 'size' => 5, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];

        $sheet->getStyle('A13:V14')->applyFromArray($headerStyle);

        // Override font size for specific columns
        $sheet->getStyle('A13')->getFont()->setSize(9)->setBold(true);
        $sheet->getStyle('B13')->getFont()->setSize(9)->setBold(true);
        $sheet->getStyle('C13')->getFont()->setSize(9)->setBold(true);

        // MT columns get accent background in header too
        foreach (['K', 'O', 'S'] as $mtCol) {
            $sheet->getStyle("{$mtCol}13")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB(self::MT_COL_BG);
        }

        $sheet->getRowDimension(13)->setRowHeight(14.1);
        $sheet->getRowDimension(14)->setRowHeight(11.65);

        // ── Student data rows ─────────────────────────────────────────────────
        for ($r = self::ROW_DATA_START; $r <= $lastDataRow; $r++) {
            $sheet->getStyle("A{$r}:V{$r}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 8],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Name: left-align
            $sheet->getStyle("C{$r}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Numbers: center
            $sheet->getStyle("A{$r}:B{$r}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}:V{$r}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // MT columns – accent fill + bold
            foreach (['K', 'O', 'S'] as $mtCol) {
                $sheet->getStyle("{$mtCol}{$r}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => self::MT_COL_BG],
                    ],
                ]);
            }

            // CF column – bold
            $sheet->getStyle("T{$r}")->getFont()->setBold(true);

            // RESULTADO – bold red
            $sheet->getStyle("V{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => self::RED]],
            ]);

            $sheet->getRowDimension($r)->setRowHeight(13.5);
        }

        // ── Footer rows ───────────────────────────────────────────────────────
        $r = $lastDataRow + 1; // teacher/council row
        $sheet->getStyle("A{$r}:V{$r}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);

        // Observations label
        $obsRow = $lastDataRow + 4;
        $sheet->getStyle("A{$obsRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
        ]);

        // Signature titles
        $sigRow = $lastDataRow + 6;
        $sheet->getStyle("D{$sigRow}:V{$sigRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
        ]);

        // Signature underlines
        $sheet->getStyle("D" . ($sigRow + 1) . ":V" . ($sigRow + 1))->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);

        // Signature names
        $sheet->getStyle("D" . ($sigRow + 2) . ":V" . ($sigRow + 2))->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
        ]);

        // Footnotes
        for ($i = 0; $i < 3; $i++) {
            $fnRow = $lastDataRow + 10 + $i;
            $sheet->mergeCells("A{$fnRow}:V{$fnRow}");
            $sheet->getStyle("A{$fnRow}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 7, 'italic' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }

        return [];
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet       = $event->sheet->getDelegate();
                $nStudents   = count($this->notas);
                $lastDataRow = self::ROW_DATA_START + $nStudents - 1;

                // Number format for grade columns
                $sheet->getStyle('G:T')->getNumberFormat()
                    ->setFormatCode('0.##');

                // Freeze pane at first data row
                $sheet->freezePane('A' . self::ROW_DATA_START);

                // Auto-filter on header
                $sheet->setAutoFilter('A13:V' . $lastDataRow);

                // Print setup
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()
                    ->setTop(0.5)->setRight(0.4)
                    ->setLeft(0.4)->setBottom(0.8);

                $sheet->getHeaderFooter()
                    ->setOddHeader(
                        '&C&"Arial,Bold"&12INSTITUTO POLITÉCNICO INDUSTRIAL DO KILAMBA KIAXI Nº 8056 "NOVA VIDA"'
                    )
                    ->setOddFooter(
                        '&L' . ($this->disciplina->nome ?? '') .
                        ' — ' . ($this->turma->nome_completo ?? $this->turma->nome ?? '') .
                        '&RPág. &P de &N'
                    );
            },
        ];
    }

    // =========================================================================
    // TITLE
    // =========================================================================

    public function title(): string
    {
        return mb_substr(
            'Pauta-' . ($this->disciplina->codigo ?? 'DISC') . '-' . ($this->turma->nome ?? 'TURMA'),
            0,
            31
        );
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Build a sparse row array indexed A=0, B=1, … so FromArray can use it.
     * We receive a map like ['A' => val, 'C' => val] and spread it to a flat
     * numeric array with gaps filled by null (empty cell).
     */
    private function makeRow(array $colValues): array
    {
        $row = array_fill(0, 22, null); // 22 columns A-V
        foreach ($colValues as $col => $val) {
            $idx = ord(strtoupper($col)) - ord('A');
            $row[$idx] = $val;
        }
        return $row;
    }

    private function blankRow(): array
    {
        return array_fill(0, 22, null);
    }

    /**
     * Return a numeric grade value or null (empty) for blank/null grades.
     */
    private function n($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return (float) $value;
    }

    /**
     * Usa sempre o valor já calculado no backend.
     *
     * Isso evita divergências no Excel para alunos que não têm um trimestre
     * aplicável (ex.: matrícula no 2º trimestre, onde MT1 deve permanecer vazio)
     * e garante compatibilidade com pesos dinâmicos da configuração de avaliação.
     */
    private function calcOrValue($nota, string $field, string $c3, string $c2, string $c1, int $row)
    {
            return $this->n($nota->$field ?? null);
    }
}