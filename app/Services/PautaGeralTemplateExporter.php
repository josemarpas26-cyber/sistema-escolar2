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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
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
        'instituicao' => ['start' => 24, 'end' => 57],
        'titulo'      => ['start' => 24, 'end' => 57],
        'info_direita'=> ['start' => 16, 'end' => 70],
    ];

    private const SIGNATURE_TEMPLATE_RANGES = [
        'director'    => ['start' => 8,  'end' => 22],
        'coordenador' => ['start' => 43, 'end' => 58],
        'subdirector' => ['start' => 77, 'end' => 87],
    ];

    private const DISCIPLINE_BLOCKS = [
        ['E','K'],['L','R'],['S','Y'],['Z','AF'],
        ['AG','AM'],['AN','AS'],['AT','AY'],['AZ','BF'],
        ['BG','BM'],['BN','BS'],['BT','BZ'],['CA','CG'],
    ];

    // ── Estatísticas: cores ──────────────────────────────────────────────────
    private const ESTAT_TITLE_BG = '2F75B6';
    private const ESTAT_GRP_BG   = 'D9E1F2';
    private const ESTAT_SUB_BG   = 'BDD7EE';
    private const ESTAT_TOTAL_BG = 'E2EFDA';
    private const ESTAT_ALT_BG   = 'F2F2F2';
    private const ESTAT_NOTE_CLR = '595959';

    /**
     * Layout da grelha de estatísticas mapeado nas 87 colunas reais (A..CI).
     * Formato: [coluna_inicial, coluna_final] (1-based).
     */
    private const ESTAT_SLOTS = [
        'label'     => [1,  4],
        'aval'      => [5,  10],
        'aval_m'    => [11, 16],
        'aval_f'    => [17, 22],
        'pos_m'     => [23, 29],
        'neg_m'     => [30, 36],
        'pos_f'     => [37, 43],
        'neg_f'     => [44, 50],
        'pct_pos_m' => [51, 58],
        'pct_neg_m' => [59, 66],
        'pct_pos_f' => [67, 74],
        'pct_neg_f' => [75, 82],
        'pct_pos'   => [83, 85],
        'pct_neg'   => [86, 87],
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    public function download(array $dados): BinaryFileResponse
    {
        $spreadsheet = $this->build($dados);
        $path = null;

        try {
            $path = tempnam(sys_get_temp_dir(), 'pauta-geral-').'.xlsx';
            $filename = 'pauta-'.Str::slug(
                $dados['turma']->nome_completo ?? $dados['turma']->nome, '-'
            ).'.xlsx';

            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
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
        $trimestre = (string) ($dados['trimestre'] ?? 'final');

        if ((int) $turma->classe === 13 && ! in_array($trimestre, ['1', '2', '3'], true)) {
            return app(PautaGeralDecimaTerceiraTemplateExporter::class)->build($dados);
        }

        $turma->loadMissing(['curso.coordenador', 'coordenador', 'disciplinas.cursos']);
        $anoLetivo ??= $turma->anoLetivo;

        $alunos = $turma->alunos()
            ->wherePivotIn('status', ['matriculado', 'recurso', 'aprovado', 'reprovado', 'concluido'])
            ->orderBy('name')
            ->get();

        $disciplinas = $this->ordenarDisciplinas($turma->disciplinas)->values();

        if ($disciplinas->count() > count(self::DISCIPLINE_BLOCKS)) {
            throw new \RuntimeException(
                'O template suporta no maximo '.count(self::DISCIPLINE_BLOCKS).' disciplinas por pauta geral.'
            );
        }

        $notas       = $this->resolveNotas($turma, $anoLetivo, $dados);
        $notasIndex  = $this->indexarNotas($notas);
        $atribuicoes = $turma->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
            ->with(['professor', 'disciplina'])
            ->get()
            ->keyBy('disciplina_id');

        $config      = $this->periodoConfig($trimestre);
        $spreadsheet = $this->loadTemplateSpreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $styles      = $this->capturarEstilosBase($sheet);

        $footerStart = $this->ajustarEstruturaVertical($sheet, $alunos->count());
        $layout      = $this->podarEstruturaHorizontal($sheet, $disciplinas->count());
        $lastDataRow = $footerStart - 1;
        $lastRow     = $footerStart + (self::FOOTER_END - self::FOOTER_START);

        $this->limparAreaDeDados($sheet, $lastDataRow, $layout['lastCol']);
        $this->normalizarAreaDeDados($sheet, $lastDataRow, $layout['lastCol']);
        $this->atualizarCabecalho($sheet, $turma, $anoLetivo, $config, $layout['lastCol'], $styles);
        $this->atualizarCabecalhosDeDisciplinas($sheet, $disciplinas, $config, $layout);
        $this->preencherLinhasDosAlunos($sheet, $turma, $alunos, $disciplinas, $notasIndex, $config, $layout);
        $this->atualizarRodape($sheet, $turma, $disciplinas, $atribuicoes, $footerStart, $layout, $styles);
        $this->ajustarImpressao($sheet, $lastRow, $lastDataRow, $layout['lastCol']);

        // ── Estatísticas na mesma folha, 2 linhas abaixo do rodapé ──────────
        $this->adicionarEstatisticasNaMesmaFolha(
            sheet: $sheet,
            turma: $turma,
            anoLetivo: $anoLetivo,
            alunos: $alunos,
            disciplinas: $disciplinas,
            secStartRow: $lastRow + 2,
        );

        return $spreadsheet;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SECÇÃO ESTATÍSTICAS / CLASSIFICAÇÃO (na mesma folha)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Escreve a grelha de estatísticas/classificação abaixo do rodapé da pauta.
     *
     * Estrutura (87 colunas A..CI):
     *  [R+0] Título de secção
     *  [R+1] Grupos: TOTAL AVALIADOS | MASCULINO | FEMININO | % POR SEXO | % TOTAL
     *  [R+2] Sub-cabeçalhos por coluna lógica
     *  [R+3] 1º Trimestre
     *  [R+4] 2º Trimestre
     *  [R+5] 3º Trimestre
     *  [R+6] TOTAL GERAL
     *  [R+8] Legenda
     */
    private function adicionarEstatisticasNaMesmaFolha(
        Worksheet $sheet,
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        EloquentCollection $alunos,
        Collection $disciplinas,
        int $secStartRow,
    ): void {
        $estatisticas = $this->calcularEstatisticasPorTrimestre($turma, $anoLetivo, $alunos, $disciplinas);

        $R = $secStartRow;
        $this->estatTitulo($sheet, $R);
        $this->estatGrupoHeaders($sheet, $R + 1);
        $this->estatSubHeaders($sheet, $R + 2);

        $dataRows = [];
        foreach ([1 => '1º Trimestre', 2 => '2º Trimestre', 3 => '3º Trimestre'] as $t => $label) {
            $row = $R + 2 + $t;
            $bg  = ($t % 2 !== 0) ? self::ESTAT_ALT_BG : 'FFFFFF';
            $this->estatLinhaData($sheet, $row, $label, $estatisticas[$t], $bg);
            $dataRows[$t] = $row;
        }

        $this->estatLinhaTotais($sheet, $R + 6, $dataRows);
        $this->estatLegenda($sheet, $R + 8);
    }

    /**
     * Carrega todas as notas do ano letivo e calcula, por trimestre:
     * aval, aval_m, aval_f, pos_m, neg_m, pos_f, neg_f.
     */
    private function calcularEstatisticasPorTrimestre(
        Turma $turma,
        ?AnoLetivo $anoLetivo,
        EloquentCollection $alunos,
        Collection $disciplinas,
        int $notaMinPositiva = 10,
    ): array {
        $anoLetivoId = $anoLetivo?->id ?? $turma->ano_letivo_id;

        $todasNotas = $turma->notas()
            ->where('ano_letivo_id', $anoLetivoId)
            ->with(['aluno', 'disciplina'])
            ->get();

        $notasPorTrimestre = [
            1 => $todasNotas->filter(fn ($n) => $n->mt1 !== null),
            2 => $todasNotas->filter(fn ($n) => $n->mt2 !== null),
            3 => $todasNotas->filter(fn ($n) => $n->mt3 !== null),
        ];

        $result = [];

        foreach ([1, 2, 3] as $t) {
            $notasIndex = $this->indexarNotas($notasPorTrimestre[$t]);
            $campo = "mt{$t}";
            $avalM = $avalF = $posM = $negM = $posF = $negF = 0;

            foreach ($alunos as $aluno) {
                $genero     = Str::upper(trim((string) ($aluno->genero ?? '')));
                $notasAluno = $notasIndex[$aluno->id] ?? [];

                $medias = collect($disciplinas)
                    ->map(fn ($d) => $notasAluno[$d->id]?->{$campo} ?? null)
                    ->filter(fn ($v) => $v !== null && is_numeric($v))
                    ->map(fn ($v) => (float) $v);

                if ($medias->isEmpty()) {
                    continue;
                }

                $isPositivo = $medias->average() >= $notaMinPositiva;

                if ($genero === 'M') {
                    $avalM++;
                    $isPositivo ? $posM++ : $negM++;
                } elseif ($genero === 'F') {
                    $avalF++;
                    $isPositivo ? $posF++ : $negF++;
                }
            }

            $result[$t] = compact('avalM', 'avalF', 'posM', 'negM', 'posF', 'negF') + [
                'aval' => $avalM + $avalF,
                'aval_m' => $avalM,
                'aval_f' => $avalF,
                'pos_m' => $posM,
                'neg_m' => $negM,
                'pos_f' => $posF,
                'neg_f' => $negF,
            ];
        }

        return $result;
    }

    // ── Renderização ──────────────────────────────────────────────────────────

    private function estatTitulo(Worksheet $sheet, int $row): void
    {
        $last = Coordinate::stringFromColumnIndex(max(array_column(self::ESTAT_SLOTS, 1)));
        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", 'ESTATÍSTICAS / CLASSIFICAÇÃO');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['name'=>'Arial','bold'=>true,'size'=>10,'color'=>['argb'=>'FFFFFFFF']],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FF'.self::ESTAT_TITLE_BG]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            'borders'   => $this->bordaMediumArray(),
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function estatGrupoHeaders(Worksheet $sheet, int $row): void
    {
        $groups = [
            [self::ESTAT_SLOTS['aval'][0],      self::ESTAT_SLOTS['aval_f'][1],     'TOTAL AVALIADOS'],
            [self::ESTAT_SLOTS['pos_m'][0],     self::ESTAT_SLOTS['neg_m'][1],      'MASCULINO'],
            [self::ESTAT_SLOTS['pos_f'][0],     self::ESTAT_SLOTS['neg_f'][1],      'FEMININO'],
            [self::ESTAT_SLOTS['pct_pos_m'][0], self::ESTAT_SLOTS['pct_neg_f'][1],  '% POR SEXO'],
            [self::ESTAT_SLOTS['pct_pos'][0],   self::ESTAT_SLOTS['pct_neg'][1],    '% TOTAL'],
        ];

        // Label slot vazio
        [$ls, $le] = self::ESTAT_SLOTS['label'];
        $this->estatMesclaCelula($sheet, $ls, $le, $row, '', self::ESTAT_GRP_BG, true);

        foreach ($groups as [$s, $e, $label]) {
            $this->estatMesclaCelula($sheet, $s, $e, $row, $label, self::ESTAT_GRP_BG, true);
        }
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function estatSubHeaders(Worksheet $sheet, int $row): void
    {
        $labels = [
            'label'     => 'TRIMESTRE',  'aval'      => 'AVAL',
            'aval_m'    => 'M',          'aval_f'    => 'F',
            'pos_m'     => 'N. POS (M)', 'neg_m'     => 'N. NEG (M)',
            'pos_f'     => 'N. POS (F)', 'neg_f'     => 'N. NEG (F)',
            'pct_pos_m' => '% POS (M)',  'pct_neg_m' => '% NEG (M)',
            'pct_pos_f' => '% POS (F)', 'pct_neg_f' => '% NEG (F)',
            'pct_pos'   => '% POS',     'pct_neg'   => '% NEG',
        ];

        foreach ($labels as $key => $label) {
            [$s, $e] = self::ESTAT_SLOTS[$key];
            $this->estatMesclaCelula($sheet, $s, $e, $row, $label, self::ESTAT_SUB_BG, true, 7, true, true);
        }
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function estatLinhaData(Worksheet $sheet, int $row, string $label, array $stats, string $bg): void
    {
        $sheet->getRowDimension($row)->setRowHeight(16);

        [$ls, $le] = self::ESTAT_SLOTS['label'];
        $this->estatMesclaCelula($sheet, $ls, $le, $row, $label, $bg, true, 8, false, false, 'left');

        foreach (['aval','aval_m','aval_f','pos_m','neg_m','pos_f','neg_f'] as $key) {
            [$s, $e] = self::ESTAT_SLOTS[$key];
            $sc = Coordinate::stringFromColumnIndex($s);
            if ($s !== $e) {
                $sheet->mergeCells("{$sc}{$row}:".Coordinate::stringFromColumnIndex($e)."{$row}");
            }
            $sheet->setCellValue("{$sc}{$row}", $stats[$key]);
            $this->aplicarEstiloEstat($sheet->getStyle("{$sc}{$row}"), $bg);
            $this->propagarBordaThin($sheet, $s, $e, $row);
        }

        // Fórmulas de percentual
        $av  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['aval'][0]).$row;
        $avm = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['aval_m'][0]).$row;
        $avf = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['aval_f'][0]).$row;
        $pm  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['pos_m'][0]).$row;
        $nm  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['neg_m'][0]).$row;
        $pf  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['pos_f'][0]).$row;
        $nf  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['neg_f'][0]).$row;

        foreach ([
            'pct_pos_m' => "=IFERROR({$pm}/{$avm},0)",
            'pct_neg_m' => "=IFERROR({$nm}/{$avm},0)",
            'pct_pos_f' => "=IFERROR({$pf}/{$avf},0)",
            'pct_neg_f' => "=IFERROR({$nf}/{$avf},0)",
            'pct_pos'   => "=IFERROR(({$pm}+{$pf})/{$av},0)",
            'pct_neg'   => "=IFERROR(({$nm}+{$nf})/{$av},0)",
        ] as $key => $formula) {
            [$s, $e] = self::ESTAT_SLOTS[$key];
            $sc = Coordinate::stringFromColumnIndex($s);
            if ($s !== $e) {
                $sheet->mergeCells("{$sc}{$row}:".Coordinate::stringFromColumnIndex($e)."{$row}");
            }
            $sheet->setCellValue("{$sc}{$row}", $formula);
            $style = $sheet->getStyle("{$sc}{$row}");
            $this->aplicarEstiloEstat($style, $bg);
            $style->getNumberFormat()->setFormatCode('0.0%');
            $this->propagarBordaThin($sheet, $s, $e, $row);
        }
    }

    private function estatLinhaTotais(Worksheet $sheet, int $totalRow, array $dataRows): void
    {
        $dr1 = min($dataRows);
        $dr3 = max($dataRows);
        $sheet->getRowDimension($totalRow)->setRowHeight(18);

        [$ls, $le] = self::ESTAT_SLOTS['label'];
        $this->estatMesclaCelula($sheet, $ls, $le, $totalRow, 'TOTAL GERAL', self::ESTAT_TOTAL_BG, true, 8, true, false, 'left');

        foreach (['aval','aval_m','aval_f','pos_m','neg_m','pos_f','neg_f'] as $key) {
            [$s, $e] = self::ESTAT_SLOTS[$key];
            $sc = Coordinate::stringFromColumnIndex($s);
            if ($s !== $e) {
                $sheet->mergeCells("{$sc}{$totalRow}:".Coordinate::stringFromColumnIndex($e)."{$totalRow}");
            }
            $sheet->setCellValue("{$sc}{$totalRow}", "=SUM({$sc}{$dr1}:{$sc}{$dr3})");
            $this->aplicarEstiloEstat($sheet->getStyle("{$sc}{$totalRow}"), self::ESTAT_TOTAL_BG, true);
            $this->propagarBordaThin($sheet, $s, $e, $totalRow);
        }

        $av  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['aval'][0]).$totalRow;
        $avm = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['aval_m'][0]).$totalRow;
        $avf = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['aval_f'][0]).$totalRow;
        $pm  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['pos_m'][0]).$totalRow;
        $nm  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['neg_m'][0]).$totalRow;
        $pf  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['pos_f'][0]).$totalRow;
        $nf  = Coordinate::stringFromColumnIndex(self::ESTAT_SLOTS['neg_f'][0]).$totalRow;

        foreach ([
            'pct_pos_m' => "=IFERROR({$pm}/{$avm},0)",
            'pct_neg_m' => "=IFERROR({$nm}/{$avm},0)",
            'pct_pos_f' => "=IFERROR({$pf}/{$avf},0)",
            'pct_neg_f' => "=IFERROR({$nf}/{$avf},0)",
            'pct_pos'   => "=IFERROR(({$pm}+{$pf})/{$av},0)",
            'pct_neg'   => "=IFERROR(({$nm}+{$nf})/{$av},0)",
        ] as $key => $formula) {
            [$s, $e] = self::ESTAT_SLOTS[$key];
            $sc = Coordinate::stringFromColumnIndex($s);
            if ($s !== $e) {
                $sheet->mergeCells("{$sc}{$totalRow}:".Coordinate::stringFromColumnIndex($e)."{$totalRow}");
            }
            $sheet->setCellValue("{$sc}{$totalRow}", $formula);
            $style = $sheet->getStyle("{$sc}{$totalRow}");
            $this->aplicarEstiloEstat($style, self::ESTAT_TOTAL_BG, true);
            $style->getNumberFormat()->setFormatCode('0.0%');
            $this->propagarBordaThin($sheet, $s, $e, $totalRow);
        }
    }

    private function estatLegenda(Worksheet $sheet, int $row): void
    {
        $last = Coordinate::stringFromColumnIndex(max(array_column(self::ESTAT_SLOTS, 1)));
        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $cell = $sheet->getCell("A{$row}");
        $cell->setValue(
            'AVAL = Total de alunos avaliados  |  M = Masculino  |  F = Feminino  |  '
            .'N. POS = Avaliações positivas (média ≥ 10)  |  N. NEG = Avaliações negativas (média < 10)  |  '
            .'% POR SEXO = sobre o total do sexo  |  % TOTAL = sobre o total geral avaliado'
        );
        $cell->getStyle()->getFont()->setName('Arial')->setSize(7)->setItalic(true)
            ->setColor(new Color('FF'.self::ESTAT_NOTE_CLR));
        $cell->getStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    // ── Helpers de estilo ─────────────────────────────────────────────────────

    /**
     * Mescla colunas $s..$e na linha $row, escreve $value e aplica estilo.
     */
    private function estatMesclaCelula(
        Worksheet $sheet,
        int $s, int $e, int $row,
        string $value,
        string $bg,
        bool $bold = false,
        int $size = 8,
        bool $mediumBottom = false,
        bool $wrap = false,
        string $halign = 'center',
    ): void {
        $sc = Coordinate::stringFromColumnIndex($s);
        $ec = Coordinate::stringFromColumnIndex($e);
        if ($s !== $e) {
            $sheet->mergeCells("{$sc}{$row}:{$ec}{$row}");
        }
        $sheet->setCellValue("{$sc}{$row}", $value);
        $style = $sheet->getStyle("{$sc}{$row}");
        $this->aplicarEstiloEstat($style, $bg, $bold, $size, $mediumBottom, $wrap, $halign);
        $this->propagarBordaThin($sheet, $s, $e, $row, $mediumBottom);
    }

    private function aplicarEstiloEstat(
        Style $style,
        string $bg,
        bool $bold = false,
        int $size = 8,
        bool $mediumBottom = false,
        bool $wrap = false,
        string $halign = 'center',
    ): void {
        $style->applyFromArray([
            'font'      => ['name'=>'Arial','bold'=>$bold,'size'=>$size],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>"FF{$bg}"]],
            'alignment' => ['horizontal'=>$halign,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>$wrap],
            'borders'   => $mediumBottom ? $this->bordaMediumBottomArray() : $this->bordaThinArray(),
        ]);
    }

    private function propagarBordaThin(Worksheet $sheet, int $s, int $e, int $row, bool $medium = false): void
    {
        $arr = $medium ? $this->bordaMediumBottomArray() : $this->bordaThinArray();
        for ($col = $s; $col <= $e; $col++) {
            $sheet->getStyle(Coordinate::stringFromColumnIndex($col).$row)
                ->applyFromArray(['borders' => $arr]);
        }
    }

    private function bordaThinArray(): array
    {
        $t = ['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FF000000']];
        return ['top'=>$t,'bottom'=>$t,'left'=>$t,'right'=>$t];
    }

    private function bordaMediumBottomArray(): array
    {
        $t = ['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FF000000']];
        $m = ['borderStyle'=>Border::BORDER_MEDIUM,'color'=>['argb'=>'FF000000']];
        return ['top'=>$t,'bottom'=>$m,'left'=>$t,'right'=>$t];
    }

    private function bordaMediumArray(): array
    {
        $m = ['borderStyle'=>Border::BORDER_MEDIUM,'color'=>['argb'=>'FF000000']];
        return ['top'=>$m,'bottom'=>$m,'left'=>$m,'right'=>$m];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Código original (inalterado)
    // ══════════════════════════════════════════════════════════════════════════

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
            'instituicao'      => clone $sheet->getStyle('X5'),
            'titulo'           => clone $sheet->getStyle('X6'),
            'info_direita'     => clone $sheet->getStyle('P8'),
            'assinatura_rotulo'=> clone $sheet->getStyle('I58'),
            'assinatura_linha' => clone $sheet->getStyle('AG59'),
            'assinatura_nome'  => clone $sheet->getStyle('H60'),
        ];
    }

    private function ajustarEstruturaVertical(Worksheet $sheet, int $totalAlunos): int
    {
        $capacidadeBase = self::DATA_END - self::DATA_START + 1;
        $extraRows      = max(0, $totalAlunos - $capacidadeBase);
        $rowsToRemove   = max(0, $capacidadeBase - $totalAlunos);

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
            $unusedBlocks   = array_slice(self::DISCIPLINE_BLOCKS, $disciplinasCount);
            $firstUnusedCol = $unusedBlocks[0][0];
            $colsToRemove   = collect($unusedBlocks)
                ->sum(fn (array $block) => $this->columnsCount($block[0], $block[1]));
            $sheet->removeColumn($firstUnusedCol, $colsToRemove);
        }

        $layout          = $this->calcularLayoutHorizontal($disciplinasCount);
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $lastColIndex    = Coordinate::columnIndexFromString($layout['lastCol']);

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
        $blocks        = array_slice(self::DISCIPLINE_BLOCKS, 0, $disciplinasCount);
        $firstDiscIndex= Coordinate::columnIndexFromString(self::COL_FIRST_DISC);
        $usedWidth     = collect($blocks)->sum(fn (array $block) => $this->columnsCount($block[0], $block[1]));
        $obsIndex      = $firstDiscIndex + $usedWidth;
        $resultIndex   = $obsIndex + 1;

        return [
            'disciplineBlocks' => $blocks,
            'obsCol'    => Coordinate::stringFromColumnIndex($obsIndex),
            'resultCol' => Coordinate::stringFromColumnIndex($resultIndex),
            'lastCol'   => Coordinate::stringFromColumnIndex($resultIndex),
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
            ->getFill()->setFillType(Fill::FILL_NONE);
    }

    private function atualizarCabecalho(
        Worksheet $sheet, Turma $turma, ?AnoLetivo $anoLetivo,
        array $config, string $lastCol, array $styles
    ): void {
        $anoNome      = $anoLetivo?->nome ?? ($turma->anoLetivo?->nome ?? date('Y').'/'.(date('Y') + 1));
        $lastColIndex = Coordinate::columnIndexFromString($lastCol);

        $this->desfazerMesclasNasLinhas($sheet, [self::ROW_DIRECTOR, self::ROW_ASSIN, self::ROW_INFO]);

        $sheet->setCellValue('C'.self::ROW_DIRECTOR, 'O DIRECTOR');
        $sheet->setCellValue('C'.self::ROW_ASSIN, '______________________________');
        $sheet->setCellValue('I'.self::ROW_ASSIN, 'Ano Lectivo');
        $sheet->setCellValue('M'.self::ROW_ASSIN, ':'.$anoNome);
        $sheet->setCellValue('C'.self::ROW_NOME_DIR, $this->nomeDirector());
        $sheet->setCellValue('C'.self::ROW_INFO, 'Data: _____/_____/_________');
        $sheet->setCellValue('F'.self::ROW_INFO, $this->infoTurmaLinhaEsquerda($turma));

        [$instStart, $instEnd]   = $this->resolverFaixaTemplate(self::HEADER_TEMPLATE_RANGES['instituicao'], $lastColIndex, 6);
        [$titleStart, $titleEnd] = $this->resolverFaixaTemplate(self::HEADER_TEMPLATE_RANGES['titulo'], $lastColIndex, 6);
        [$infoStart, $infoEnd]   = $this->resolverFaixaTemplate(self::HEADER_TEMPLATE_RANGES['info_direita'], $lastColIndex, 5);

        $this->preencherFaixaMesclada($sheet, $instStart, $instEnd, self::ROW_DIRECTOR, $this->nomeInstituicao(), $styles['instituicao']);
        $this->preencherFaixaMesclada($sheet, $titleStart, $titleEnd, self::ROW_ASSIN, $config['titulo'], $styles['titulo']);

        if ($infoStart > Coordinate::columnIndexFromString('F')) {
            $this->preencherFaixaMesclada($sheet, $infoStart, $infoEnd, self::ROW_INFO, $this->infoTurmaLinhaDireita($turma), $styles['info_direita']);
        }
    }

    private function atualizarCabecalhosDeDisciplinas(
        Worksheet $sheet, Collection $disciplinas, array $config, array $layout
    ): void {
        $sheet->setCellValue($layout['obsCol'].self::ROW_HDR2, 'OBSERV.');
        $sheet->setCellValue($layout['resultCol'].self::ROW_HDR2, 'RESULTADO');

        foreach ($layout['disciplineBlocks'] as $idx => [$startCol, $endCol]) {
            $disciplina = $disciplinas->get($idx);
            $cols       = $this->columnsInRange($startCol, $endCol);
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
        Worksheet $sheet, Turma $turma, EloquentCollection $alunos, Collection $disciplinas,
        array $notasIndex, array $config, array $layout
    ): void {
        foreach ($alunos as $offset => $aluno) {
            $row        = self::DATA_START + $offset;
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
                    $cell  = $cols[$pos].$row;
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

            [$obs, $resultado] = $this->resolverResultadoAluno($turma, $disciplinas, $notasAluno, $config['mostrarResultado'] ?? false);
            $sheet->setCellValueExplicit($layout['obsCol'].$row, $obs, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($layout['resultCol'].$row, $resultado, DataType::TYPE_STRING);
        }
    }

    private function atualizarRodape(
        Worksheet $sheet, Turma $turma, Collection $disciplinas,
        Collection $atribuicoes, int $footerStart, array $layout, array $styles
    ): void {
        $sheet->setCellValue('A'.$footerStart, 'Data do Conselho de Turma');
        $sheet->setCellValue('A'.($footerStart + 1), '_____/______/________');

        foreach ($layout['disciplineBlocks'] as [$startCol]) {
            $sheet->setCellValue($startCol.$footerStart, null);
        }

        foreach ($disciplinas->values() as $idx => $disciplina) {
            [$startCol] = $layout['disciplineBlocks'][$idx];
            $professor  = $atribuicoes->get($disciplina->id)?->professor?->name ?? '';
            $sheet->setCellValue($startCol.$footerStart, $professor);
        }

        $signatureRows = [
            'label' => $footerStart + 7,
            'line'  => $footerStart + 8,
            'name'  => $footerStart + 9,
        ];

        $this->desfazerMesclasNasLinhas($sheet, array_values($signatureRows));
        foreach ($signatureRows as $row) {
            $sheet->fromArray(array_fill(0, Coordinate::columnIndexFromString($layout['lastCol']), null), null, 'A'.$row);
        }

        $assinaturas = [
            'director'    => ['label' => 'O DIRECTOR DE TURMA',     'name' => $this->nomeDirectorTurma($turma)],
            'coordenador' => ['label' => 'O COORDENADOR DE CURSO',  'name' => $this->nomeCoordenadorCurso($turma)],
            'subdirector' => ['label' => 'O SUBDIRECTOR PEDAGÓGICO','name' => $this->nomeSubdirectorPedagogico()],
        ];

        foreach ($this->resolverFaixasAssinatura(Coordinate::columnIndexFromString($layout['lastCol'])) as $key => [$start, $end]) {
            $ass = $assinaturas[$key];
            $this->preencherFaixaMesclada($sheet, $start, $end, $signatureRows['label'], $ass['label'], $styles['assinatura_rotulo']);
            $this->preencherFaixaMesclada($sheet, $start, $end, $signatureRows['line'], '____________________________________', $styles['assinatura_linha']);
            $this->preencherFaixaMesclada($sheet, $start, $end, $signatureRows['name'], $ass['name'], $styles['assinatura_nome']);
        }
    }

    private function ajustarImpressao(Worksheet $sheet, int $lastRow, int $lastDataRow, string $lastCol): void
    {
        $sheet->freezePane('A'.self::DATA_START);
        $sheet->getPageSetup()->setPrintArea('A1:'.$lastCol.$lastRow);
        if ($lastDataRow >= self::DATA_START) {
            $sheet->getStyle('A'.self::DATA_START.':'.$lastCol.$lastDataRow)
                ->getFill()->setFillType(Fill::FILL_NONE);
        }
    }

    private function resolverFaixaTemplate(array $range, int $lastColIndex, int $minWidth): array
    {
        $preferredStart = $range['start'];
        $preferredEnd   = min($range['end'], $lastColIndex);

        if ($lastColIndex >= $preferredStart && ($preferredEnd - $preferredStart + 1) >= $minWidth) {
            return [$preferredStart, $preferredEnd];
        }

        $templateLastIndex = Coordinate::columnIndexFromString(self::TEMPLATE_LAST_VISIBLE_COL);
        $scaledStart = max(1, (int) round(($range['start'] / $templateLastIndex) * $lastColIndex));
        $scaledEnd   = max($scaledStart, (int) round(($range['end'] / $templateLastIndex) * $lastColIndex));

        if (($scaledEnd - $scaledStart + 1) < $minWidth) {
            $scaledStart = max(1, $lastColIndex - $minWidth + 1);
            $scaledEnd   = $lastColIndex;
        }

        return [$scaledStart, min($scaledEnd, $lastColIndex)];
    }

    private function preencherFaixaMesclada(
        Worksheet $sheet, int $startIndex, int $endIndex,
        int $row, string $value, Style $style
    ): void {
        $startCol = Coordinate::stringFromColumnIndex($startIndex);
        $endCol   = Coordinate::stringFromColumnIndex($endIndex);
        $range    = $startCol.$row.':'.$endCol.$row;

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
        $baseWidth   = intdiv($usableWidth, 3);
        $remainder   = $usableWidth % 3;
        $cursor      = $usableStart;
        $keys        = array_keys(self::SIGNATURE_TEMPLATE_RANGES);
        $ranges      = [];

        foreach ($keys as $idx => $key) {
            $width = $baseWidth + ($idx < $remainder ? 1 : 0);
            $end   = $idx === array_key_last($keys)
                ? $lastColIndex
                : max($cursor, $cursor + $width - 1);
            $ranges[$key] = [$cursor, $end];
            $cursor       = $end + 1;
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
            $endRow   = (int) $matches[4];
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
        return $this->cfg('nome_instituicao', 'INSTITUTO POLITECNICO INDUSTRIAL DO KILAMBA KIAXI N 8056 "NOVA VIDA"');
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
        return $turma->coordenador?->name ?? $this->cfg('director_turma_fallback', 'Benjamim Mboloquele');
    }

    private function nomeCoordenadorCurso(Turma $turma): string
    {
        return $turma->curso?->coordenador?->name ?? $this->cfg('coordenador_curso_fallback', 'Manuel Goncalves Victor');
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
        $sufixo  = Str::upper((string) ($turma->nome ?? ''));
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
                'labels' => ['F.J','F.I','MfT2','MAC1','NPT1','NPT1','MT1'],
                'campos' => ['faltas_j','faltas_i','mft2','mac1','pp1','pt1','mt1'],
                'mostrarResultado' => false,
            ],
            '2' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIÂº TRIMESTRE',
                'labels' => ['F.J','F.I','MfT2','MAC2','NPT2','NPT2','MT2'],
                'campos' => ['faltas_j','faltas_i','mft2','mac2','pp2','pt2','mt2'],
                'mostrarResultado' => false,
            ],
            '3' => [
                'titulo' => 'PAUTA DE APROVEITAMENTO - IIIÂº TRIMESTRE',
                'labels' => ['F.J','F.I','MfT2','MAC3','NPT2','PG','MT3'],
                'campos' => ['faltas_j','faltas_i','mft2','mac3','pp3','pg','mt3'],
                'mostrarResultado' => false,
            ],
            default => [
                'titulo' => 'PAUTA GERAL DO ANO LETIVO',
                'labels' => ['F.J','F.I','CF','MT1','MT2','PG','CFD'],
                'campos' => ['faltas_j','faltas_i','cf','mt1','mt2','pg','cfd'],
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

    private function resolverResultadoAluno(Turma $turma, Collection $disciplinas, array $notasAluno, bool $mostrarResultado): array
    {
        if (! $mostrarResultado) {
            return ['', ''];
        }

        $resultado = app(ResultadoAlunoTurmaService::class)->avaliar($turma, $disciplinas, $notasAluno);

        return [$resultado['observacao'], $resultado['resultado']];
    }

    private function ordenarDisciplinas(Collection $disciplinas): Collection
    {
        $ordem = [
            'LINGUA PORTUGUESA'=>10,'PORTUGUES'=>10,'INGLES'=>20,'FILOSOFIA'=>30,
            'HISTORIA'=>40,'GEOGRAFIA'=>50,'EDUCACAO FISICA'=>60,'MATEMATICA'=>70,
            'FISICA'=>80,'QUIMICA'=>90,'BIOLOGIA'=>100,'EMPREENDEDORISMO'=>110,
            'TIC'=>120,'INFORMATICA'=>120,'ELECTROTECNIA'=>130,'TLP'=>140,'SEAC'=>150,'FAI'=>160,
        ];

        return $disciplinas->sortBy(function (Disciplina $disciplina) use ($ordem) {
            $nome = $this->normalize($disciplina->nome);
            return str_pad((string) ($ordem[$nome] ?? 999), 4, '0', STR_PAD_LEFT).'-'.$nome;
        });
    }

    private function abreviarDisciplina(Disciplina $disciplina): string
    {
        $aliases = [
            'LINGUA PORTUGUESA'=>'L. PORTUGUESA','PORTUGUES'=>'L. PORTUGUESA',
            'INGLES'=>'INGLÊS','EDUCACAO FISICA'=>'ED. FÍSICA','MATEMATICA'=>'MATEMAT.',
            'FISICA'=>'FÍSICA','QUIMICA'=>'QUÍMICA','ELECTROTECNIA'=>'ELECTROTECNIA',
            'EMPREENDEDORISMO'=>'EMPREEND.','INFORMATICA'=>'TIC','TECNOLOGIAS INFORMACAO'=>'TIC',
            'FORMACAO DE ATITUDES INTEGRADORAS'=>'F.A.I','FAI'=>'F.A.I',
            'TECNICAS DE LINGUAGEM DE PROGRAMACAO'=>'T.L.P','TLP'=>'T.L.P',
            'SISTEMAS DE EXPLORACAO E APLICACOES'=>'SEAC',
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
        for ($column = $start; $column <= $end; $column++) {
            $cols[] = Coordinate::stringFromColumnIndex($column);
        }
        return $cols;
    }
}
