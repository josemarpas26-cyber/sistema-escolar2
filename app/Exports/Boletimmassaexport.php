<?php

namespace App\Exports;

use App\Models\Nota;
use App\Models\Turma;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta boletins em massa, 2 por linha, no formato exacto do template.
 *
 * Cada bloco de 26 linhas contém um par de boletins lado a lado:
 *   - Esquerda: colunas A–D (aluno ímpar)
 *   - Direita:  colunas G–J (aluno par)
 *
 * Uso:
 *   $turma    = Turma::findOrFail($turmaId)->load(['curso', 'alunos']);
 *   $notas    = Nota::where('turma_id', $turma->id)->with('disciplina')->get()->groupBy('aluno_id');
 *   $trimestre = '2'; // '1', '2', '3' ou 'final'
 *   return Excel::download(new BoletimMassaExport($turma, $notas, $trimestre), 'boletins.xlsx');
 */
class BoletimMassaExport implements FromCollection, WithEvents, WithTitle
{
    // ── Layout ───────────────────────────────────────────────────────────────
    private const BLOCK_HEIGHT  = 26;   // linhas por par de boletins
    private const FIRST_ROW     = 3;    // primeira linha de dados
    private const COL_LEFT      = 'A';  // coluna de início do boletim esquerdo
    private const COL_RIGHT     = 'G';  // coluna de início do boletim direito

    // Offsets dentro de cada bloco (0-based)
    private const OFF_ESCOLA    = 0;
    private const OFF_AREA      = 1;
    private const OFF_CURSO     = 2;
    private const OFF_TITULO    = 3;
    private const OFF_PERIODO   = 4;
    private const OFF_NOME      = 5;
    private const OFF_CLASSE    = 6;
    private const OFF_HEADER    = 7;
    private const OFF_DISC_INI  = 8;    // primeira linha de disciplina
    private const OFF_DIRETOR_L = 20;
    private const OFF_DIRETOR_N = 21;

    // Cores (ARGB)
    private const COR_VERDE     = 'FF00B050';
    private const COR_VERMELHO  = 'FFFF0000';
    private const COR_AZUL_ESC  = 'FF002060';
    private const COR_PRETO     = 'FF000000';

    // ── Dados ─────────────────────────────────────────────────────────────────
    private Turma      $turma;
    private Collection $notasPorAluno;  // keyBy('aluno_id')
    private string     $trimestre;
    private Collection $alunos;
    private ?string    $nomeEscola;
    private ?string    $areaFormacao;

    public function __construct(
        Turma      $turma,
        Collection $notasPorAluno,
        string     $trimestre     = '2',
        ?string    $nomeEscola    = null,
        ?string    $areaFormacao  = null,
    ) {
        $this->turma         = $turma;
        $this->notasPorAluno = $notasPorAluno;
        $this->trimestre     = $trimestre;
        $this->nomeEscola    = $nomeEscola   ?? config('app.nome_escola',   'INST. POLITÉCN. INDUSTRIAL Nº 8050 LDA - "NOVA VIDA" - KILAMBA KIAXI');
        $this->areaFormacao  = $areaFormacao ?? config('app.area_formacao', 'ÁREA DE FORMAÇÃO DE INFORMÁTICA');

        // Alunos matriculados ordenados por nome
        $this->alunos = $turma->alunos()
            ->wherePivot('status', 'matriculado')
            ->orderBy('name')
            ->get();
    }

    public function collection(): Collection
    {
        // WithEvents gere tudo; a collection apenas existe para cumprir a interface
        return collect();
    }

    public function title(): string
    {
        return 'BOLETINS';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->configurarColunas($sheet);
                $this->escreverTodosOsBoletins($sheet);
                $this->ocultarLinhasDeGrade($sheet);
            },
        ];
    }

    // ── Configuração geral da folha ───────────────────────────────────────────

    private function configurarColunas(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(27.57);
        $sheet->getColumnDimension('B')->setWidth(10.14);
        $sheet->getColumnDimension('C')->setWidth(9.43);
        $sheet->getColumnDimension('D')->setWidth(9.14);
        $sheet->getColumnDimension('E')->setWidth(2.0);
        $sheet->getColumnDimension('F')->setWidth(1.71);
        $sheet->getColumnDimension('G')->setWidth(27.29);
        $sheet->getColumnDimension('H')->setWidth(10.14);
        $sheet->getColumnDimension('I')->setWidth(9.43);
        $sheet->getColumnDimension('J')->setWidth(9.57);
        $sheet->getColumnDimension('K')->setWidth(2.86);
    }

    private function ocultarLinhasDeGrade(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(false);
    }

    // ── Geração de todos os boletins ─────────────────────────────────────────

    private function escreverTodosOsBoletins(Worksheet $sheet): void
    {
        $alunos = $this->alunos->values();
        $total  = $alunos->count();
        $pares  = (int) ceil($total / 2);

        for ($par = 0; $par < $pares; $par++) {
            $linhaInicio = self::FIRST_ROW + ($par * self::BLOCK_HEIGHT);

            $alunoEsq = $alunos->get($par * 2);
            $alunoDrt = $alunos->get($par * 2 + 1); // null se número ímpar de alunos

            // Configurar alturas das linhas deste bloco
            $this->configurarAlturas($sheet, $linhaInicio);

            // Escrever boletim esquerdo
            if ($alunoEsq) {
                $this->escreverBoletim(
                    $sheet,
                    $linhaInicio,
                    self::COL_LEFT,
                    $alunoEsq,
                    $par * 2 + 1,  // número de ordem
                );
            }

            // Escrever boletim direito
            if ($alunoDrt) {
                $this->escreverBoletim(
                    $sheet,
                    $linhaInicio,
                    self::COL_RIGHT,
                    $alunoDrt,
                    $par * 2 + 2,
                );
            }

            // Linha divisória entre pares (leve separação visual)
            // (não existe no template original, mas deixamos o espaço das linhas 22-25)
        }
    }

    private function configurarAlturas(Worksheet $sheet, int $linhaInicio): void
    {
        for ($i = 0; $i < self::BLOCK_HEIGHT; $i++) {
            $linha = $linhaInicio + $i;
            $sheet->getRowDimension($linha)->setRowHeight(15.75);
        }
        // A linha do último item (TIC) tem altura ligeiramente maior no original
        $sheet->getRowDimension($linhaInicio + self::OFF_DISC_INI + 11)->setRowHeight(16.5);
    }

    // ── Boletim individual ────────────────────────────────────────────────────

    private function escreverBoletim(
        Worksheet $sheet,
        int       $linhaInicio,
        string    $colInicio,
        object    $aluno,
        int       $numeroOrdem,
    ): void {
        $cols   = $this->colunas($colInicio);
        $notas  = $this->notasPorAluno->get($aluno->id, collect());
        $classe = $this->turma->classe;
        $curso  = $this->turma->curso?->nome ?? 'CURSO';

        // ── Cabeçalho ────────────────────────────────────────────────────────
        $this->linha($sheet, $linhaInicio + self::OFF_ESCOLA, $cols['inicio'], $cols['fim'],
            $this->nomeEscola,
            ['bold' => true, 'size' => 8, 'align' => 'center']
        );

        $this->linha($sheet, $linhaInicio + self::OFF_AREA, $cols['inicio'], $cols['fim'],
            strtoupper($this->areaFormacao),
            ['size' => 8, 'align' => 'center']
        );

        $this->linha($sheet, $linhaInicio + self::OFF_CURSO, $cols['inicio'], $cols['fim'],
            'CURSO DE '.strtoupper($curso),
            ['bold' => true, 'size' => 8, 'align' => 'center']
        );

        $this->linha($sheet, $linhaInicio + self::OFF_TITULO, $cols['inicio'], $cols['fim'],
            'BOLETIM DE NOTAS ',
            ['bold' => true, 'size' => 8, 'align' => 'center', 'cor' => self::COR_VERDE]
        );

        $periodoLabel = $this->labelPeriodo();
        $this->linha($sheet, $linhaInicio + self::OFF_PERIODO, $cols['inicio'], $cols['fim'],
            "ANO LECTIVO: {$this->turma->anoLetivo?->nome}               {$periodoLabel}",
            ['size' => 8, 'align' => 'center']
        );

        // Nome do aluno (vermelho, bold)
        $this->celula($sheet, $cols['inicio'] . ($linhaInicio + self::OFF_NOME),
            strtoupper($aluno->name),
            ['bold' => true, 'size' => 9, 'cor' => self::COR_VERMELHO]
        );

        // Classe / Nº / Turma / Sala
        $salaNumero = $this->turma->sala ?? '—';
        $this->celula($sheet, $cols['inicio'] . ($linhaInicio + self::OFF_CLASSE),
            "     {$classe}.ª CLASSE      Nº {$numeroOrdem}       TURMA: {$this->turma->nome}       SALA Nº {$salaNumero} ",
            ['size' => 7, 'cor' => self::COR_AZUL_ESC]
        );

        // ── Cabeçalho de disciplinas ─────────────────────────────────────────
        $rowHeader = $linhaInicio + self::OFF_HEADER;
        $this->celula($sheet, $cols['inicio'] . $rowHeader, 'DISCIPLINA ',
            ['bold' => true, 'size' => 9, 'cor' => self::COR_VERDE, 'align' => 'center']);
        $this->celula($sheet, $cols['mt1'] . $rowHeader, $this->labelMT1(),
            ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center']);
        $this->celula($sheet, $cols['mt2'] . $rowHeader, $this->labelMT2(),
            ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center']);
        $this->celula($sheet, $cols['mft'] . $rowHeader, $this->labelMFT(),
            ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center']);

        // ── Disciplinas ───────────────────────────────────────────────────────
        $disciplinasOrdenadas = $notas->sortBy(fn ($n) => $n->disciplina?->nome)->values();
        $maxDiscs = 12; // máximo de disciplinas por boletim (como no template)

        foreach ($disciplinasOrdenadas->take($maxDiscs) as $idx => $nota) {
            $rowDisc = $linhaInicio + self::OFF_DISC_INI + $idx;
            $disc    = $nota->disciplina?->nome ?? '—';

            [$v1, $v2, $v3] = $this->valoresPeriodo($nota);

            $this->celula($sheet, $cols['inicio'] . $rowDisc, $disc,
                ['size' => 8]);
            $this->celula($sheet, $cols['mt1'] . $rowDisc, $v1,
                ['size' => 8, 'align' => 'center']);
            $this->celula($sheet, $cols['mt2'] . $rowDisc, $v2,
                ['size' => 8, 'align' => 'center']);
            $this->celula($sheet, $cols['mft'] . $rowDisc, $v3,
                ['size' => 8, 'align' => 'center']);
        }

        // ── Director de turma ─────────────────────────────────────────────────
        $rowDirL = $linhaInicio + self::OFF_DIRETOR_L;
        $rowDirN = $linhaInicio + self::OFF_DIRETOR_N;

        $this->celula($sheet, $cols['inicio'] . $rowDirL, 'O DIRECTOR DE TURMA: ',
            ['size' => 8]);

        $diretor = $this->turma->coordenadorTurma?->name ?? '';
        $this->celula($sheet, $cols['inicio'] . $rowDirN, $diretor,
            ['size' => 11]);

        // ── Bordas externas do boletim ────────────────────────────────────────
        $rangeBoletim = $cols['inicio'] . ($linhaInicio + self::OFF_ESCOLA)
            . ':' . $cols['fim'] . ($linhaInicio + self::OFF_DIRETOR_N);

        $sheet->getStyle($rangeBoletim)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFD9D9D9'],
                ],
            ],
        ]);
    }

    // ── Helpers de escrita ────────────────────────────────────────────────────

    /**
     * Escreve numa célula simples (sem merge).
     */
    private function celula(Worksheet $sheet, string $ref, mixed $valor, array $opts = []): void
    {
        $cell = $sheet->getCell($ref);
        $cell->setValue($valor !== null ? (string) $valor : '');

        $font = $cell->getStyle()->getFont();
        $font->setSize($opts['size'] ?? 8);

        if ($opts['bold'] ?? false) {
            $font->setBold(true);
        }
        if (isset($opts['cor'])) {
            $font->getColor()->setARGB($opts['cor']);
        }
        if (isset($opts['align'])) {
            $cell->getStyle()->getAlignment()
                ->setHorizontal($opts['align'] === 'center'
                    ? Alignment::HORIZONTAL_CENTER
                    : Alignment::HORIZONTAL_LEFT);
        }
    }

    /**
     * Escreve num intervalo mesclado (A:D ou G:J).
     */
    private function linha(Worksheet $sheet, int $row, string $colIni, string $colFim, string $valor, array $opts = []): void
    {
        $range = "{$colIni}{$row}:{$colFim}{$row}";
        $sheet->mergeCells($range);
        $this->celula($sheet, "{$colIni}{$row}", $valor, $opts);
    }

    /**
     * Retorna as colunas para um boletim baseado na coluna de início.
     */
    private function colunas(string $colInicio): array
    {
        if ($colInicio === 'A') {
            return ['inicio' => 'A', 'mt1' => 'B', 'mt2' => 'C', 'mft' => 'D', 'fim' => 'D'];
        }
        return ['inicio' => 'G', 'mt1' => 'H', 'mt2' => 'I', 'mft' => 'J', 'fim' => 'J'];
    }

    // ── Lógica de notas ───────────────────────────────────────────────────────

    /**
     * Retorna [coluna1, coluna2, coluna3] consoante o trimestre seleccionado.
     * Para '1': MT1, —, —
     * Para '2': MT1, MT2, MFT2
     * Para '3': MT1, MT2, MT3
     * Para 'final': —, —, CFD
     */
    private function valoresPeriodo(Nota $nota): array
    {
        $fmt = fn ($v) => $v !== null ? number_format((float) $v, 2, ',', '') : '';

        return match ($this->trimestre) {
            '1'     => [$fmt($nota->mt1), '',              ''             ],
            '2'     => [$fmt($nota->mt1), $fmt($nota->mt2), $fmt($nota->mft2)],
            '3'     => [$fmt($nota->mt1), $fmt($nota->mt2), $fmt($nota->mt3) ],
            default => ['',              '',              $fmt($nota->cfd) ],
        };
    }

    private function labelPeriodo(): string
    {
        return match ($this->trimestre) {
            '1'     => 'Iº TRIMESTRE',
            '2'     => 'IIº TRIMESTRE',
            '3'     => 'IIIº TRIMESTRE',
            default => 'CLASSIFICAÇÃO FINAL',
        };
    }

    private function labelMT1(): string
    {
        return match ($this->trimestre) {
            '1'     => 'MT1',
            '2', '3' => 'MT1',
            default => '—',
        };
    }

    private function labelMT2(): string
    {
        return match ($this->trimestre) {
            '2'     => 'MT2',
            '3'     => 'MT2',
            default => '',
        };
    }

    private function labelMFT(): string
    {
        return match ($this->trimestre) {
            '1'     => 'MT1',
            '2'     => 'MFT2',
            '3'     => 'MT3',
            default => 'CFD',
        };
    }
}