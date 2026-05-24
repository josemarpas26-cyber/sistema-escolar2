<?php
namespace App\Exports;

use App\Models\Nota;
use App\Models\Turma;
use App\Services\ClassificacaoEnsinoMedioService;
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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class BoletimMassaExport implements FromCollection, WithEvents, WithTitle
{
// ── Layout ───────────────────────────────────────────────────────────────
private const BLOCK_HEIGHT = 26;
private const FIRST_ROW = 3;
private const COL_LEFT = 'A';
private const COL_RIGHT = 'G';

// Offsets dentro de cada bloco (0-based)
private const OFF_LOGO      = -1; // 🔹 Logo fica 1 linha acima do conteúdo
private const OFF_ESCOLA    = 0;
private const OFF_AREA      = 1;
private const OFF_CURSO     = 2;
private const OFF_TITULO    = 3;
private const OFF_PERIODO   = 4;
private const OFF_NOME      = 5;
private const OFF_CLASSE    = 6;
private const OFF_HEADER    = 7;
private const OFF_DISC_INI  = 8;
private const OFF_DIRETOR_L = 20;
private const OFF_DIRETOR_N = 21;

// Cores (ARGB)
private const COR_VERDE     = 'FF00B050';
private const COR_VERMELHO  = 'FFFF0000';
private const COR_AZUL_ESC  = 'FF002060';
private const COR_PRETO     = 'FF000000';
private const COR_BORDA     = 'FFD9D9D9';

// 🔹 Configurações do Logo
private const LOGO_HEIGHT   = 40;    // Altura do logo em pixels
private const LOGO_MAX_WIDTH= 100;   // Largura máxima do logo
private const LOGO_OFFSET_X = 48;    // Centralização horizontal (ajuste fino)
private const LOGO_OFFSET_Y = 3;     // Ajuste vertical do logo

// ── Dados ─────────────────────────────────────────────────────────────────
private Turma      $turma;
private Collection $notasPorAluno;
private string     $trimestre;
private Collection $alunos;
private ?string    $nomeEscola;
private ?string    $areaFormacao;
private Collection $classificacoesEnsinoMedio;
private ?string    $caminhoLogo; // 🔹 Caminho configurável do logo

public function __construct(
    Turma      $turma,
    Collection $notasPorAluno,
    string     $trimestre     = '2',
    ?string    $nomeEscola    = null,
    ?string    $areaFormacao  = null,
    ?string    $caminhoLogo   = null, // 🔹 Novo parâmetro
) {
    $this->turma         = $turma;
    $this->notasPorAluno = $notasPorAluno;
    $this->trimestre     = $trimestre;
    $this->nomeEscola    = $nomeEscola   ?? config('app.nome_escola',   'INST. POLITÉCN. INDUSTRIAL Nº 8050 LDA - "NOVA VIDA" - KILAMBA KIAXI');
    $this->areaFormacao  = $areaFormacao ?? config('app.area_formacao', 'ÁREA DE FORMAÇÃO DE INFORMÁTICA');
    $this->caminhoLogo = $caminhoLogo 
    ?? config('app.caminho_logo') 
    ?? public_path('images/logo1.png'); // 🔹 Logo configurável

    $this->turma->loadMissing(['anoLetivo.configuracaoAvaliacao.provas', 'curso.areaFormacao', 'coordenador']);
    $this->areaFormacao = $areaFormacao ?? $this->turma->area_formacao_nome ?? $this->areaFormacao;

    $this->alunos = $turma->alunos()
        ->wherePivotIn('status', ['matriculado', 'recurso', 'aprovado', 'reprovado', 'concluido'])
        ->orderBy('name')
        ->get();

    $this->classificacoesEnsinoMedio = $this->isDecimaTerceiraFinal()
        ? app(ClassificacaoEnsinoMedioService::class)
            ->montarResumoDaTurma($this->turma, $this->notasPorAluno->flatten(1))
            ->keyBy(fn (array $item) => $item['aluno']->id)
        : collect();
}

public function collection(): Collection
{
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
            // 🔹 Removido inserirLogo global - agora cada boletim tem seu próprio logo
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
        $alunoDrt = $alunos->get($par * 2 + 1);

        $this->configurarAlturas($sheet, $linhaInicio);

        if ($alunoEsq) {
            $this->escreverBoletim($sheet, $linhaInicio, self::COL_LEFT, $alunoEsq, $par * 2 + 1);
        }
        if ($alunoDrt) {
            $this->escreverBoletim($sheet, $linhaInicio, self::COL_RIGHT, $alunoDrt, $par * 2 + 2);
        }
    }
}

private function configurarAlturas(Worksheet $sheet, int $linhaInicio): void
{
    // ✅ Linha do nome da escola alta o suficiente para o logo
    $sheet->getRowDimension($linhaInicio + self::OFF_ESCOLA)->setRowHeight(45);

    for ($i = 0; $i < self::BLOCK_HEIGHT; $i++) {
        $linha = $linhaInicio + $i;
        // Não sobrescrever a linha do logo
        if ($i !== self::OFF_ESCOLA) {
            $sheet->getRowDimension($linha)->setRowHeight(15.75);
        }
    }
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
    
    // 🔹 Inserir logo centralizado no topo deste boletim
    $this->inserirLogoBoletim($sheet, $linhaInicio, $colInicio, $cols);
    
    // 🔹 Obter configuração dinâmica das colunas de notas
    $configNotas = $this->getConfiguracaoNotas();

    if ($this->isDecimaTerceiraFinal()) {
        $this->escreverBoletimDecimaTerceira($sheet, $linhaInicio, $cols, $aluno, $numeroOrdem, $notas);

        return;
    }

    // ── Cabeçalho ────────────────────────────────────────────────────────
    $this->linha($sheet, $linhaInicio + self::OFF_ESCOLA, $cols['inicio'], $cols['fim'],
        $this->nomeEscola, ['bold' => true, 'size' => 8, 'align' => 'center']);

    $this->linha($sheet, $linhaInicio + self::OFF_AREA, $cols['inicio'], $cols['fim'],
        $this->areaFormacao ? 'AREA DE FORMACAO DE '.strtoupper($this->areaFormacao) : '', ['size' => 8, 'align' => 'center']);

    $this->linha($sheet, $linhaInicio + self::OFF_CURSO, $cols['inicio'], $cols['fim'],
        'CURSO DE '.strtoupper($curso), ['bold' => true, 'size' => 8, 'align' => 'center']);

    $this->linha($sheet, $linhaInicio + self::OFF_TITULO, $cols['inicio'], $cols['fim'],
        'BOLETIM DE NOTAS ', ['bold' => true, 'size' => 8, 'align' => 'center', 'cor' => self::COR_VERDE]);

    $periodoLabel = $this->labelPeriodo();
    $this->linha($sheet, $linhaInicio + self::OFF_PERIODO, $cols['inicio'], $cols['fim'],
        "ANO LECTIVO: {$this->turma->anoLetivo?->nome}               {$periodoLabel}",
        ['size' => 8, 'align' => 'center']);

    $this->celula($sheet, $cols['inicio'] . ($linhaInicio + self::OFF_NOME),
        strtoupper($aluno->name), ['bold' => true, 'size' => 9, 'cor' => self::COR_VERMELHO]);

    $salaNumero = $this->turma->sala ?? '—';
    $this->celula($sheet, $cols['inicio'] . ($linhaInicio + self::OFF_CLASSE),
        "     {$classe}.ª CLASSE      Nº {$numeroOrdem}       TURMA: {$this->turma->nome}       SALA Nº {$salaNumero} ",
        ['size' => 7, 'cor' => self::COR_AZUL_ESC]);

    // ── Cabeçalho de disciplinas (COM BORDAS) ───────────────────────────
    $rowHeader = $linhaInicio + self::OFF_HEADER;
    
    $this->celulaComBorda($sheet, $cols['inicio'] . $rowHeader, 'DISCIPLINA ',
        ['bold' => true, 'size' => 9, 'cor' => self::COR_VERDE, 'align' => 'center'], true);
    
    foreach ($configNotas as $idx => $config) {
        $colNota = $cols['notas'][$idx] ?? null;
        if ($colNota) {
            $this->celulaComBorda($sheet, $colNota . $rowHeader, $config['label'],
                ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center'], true);
        }
    }

    // ── Disciplinas (COM BORDAS) ─────────────────────────────────────────
    $disciplinasOrdenadas = $notas->sortBy(fn ($n) => $n->disciplina?->nome)->values();
    $maxDiscs = 12;

    foreach ($disciplinasOrdenadas->take($maxDiscs) as $idx => $nota) {
        $rowDisc = $linhaInicio + self::OFF_DISC_INI + $idx;
        $disc    = $nota->disciplina?->nome ?? '—';
        $valores = $this->valoresPeriodo($nota);

        $this->celulaComBorda($sheet, $cols['inicio'] . $rowDisc, $disc, ['size' => 8], true);
        
        foreach ($configNotas as $idxNota => $config) {
            $colNota = $cols['notas'][$idxNota] ?? null;
            $valor = $valores[$config['key']] ?? '';
            if ($colNota) {
                $this->celulaComBorda($sheet, $colNota . $rowDisc, $valor,
                    ['size' => 8, 'align' => 'center'], true);
            }
        }
    }

    // ── Director de turma ─────────────────────────────────────────────────
    $rowDirL = $linhaInicio + self::OFF_DIRETOR_L;
    $rowDirN = $linhaInicio + self::OFF_DIRETOR_N;

    $this->celula($sheet, $cols['inicio'] . $rowDirL, 'O DIRECTOR DE TURMA: ', ['size' => 8]);
    $diretor = $this->turma->coordenador?->name ?? '';
    $this->celula($sheet, $cols['inicio'] . $rowDirN, $diretor, ['size' => 11]);

    // ── Bordas externas do boletim ────────────────────────────────────────
    $rangeBoletim = $cols['inicio'] . ($linhaInicio + self::OFF_ESCOLA)
        . ':' . $cols['fim'] . ($linhaInicio + self::OFF_DIRETOR_N);

    $sheet->getStyle($rangeBoletim)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['argb' => self::COR_BORDA],
            ],
        ],
    ]);
    
    $this->aplicarBordasTabela($sheet, $cols, $linhaInicio, count($configNotas));
}

// ── 🔹 Novo: Inserção de Logo por Boletim ─────────────────────────────────

/**
 * Insere o logo centralizado no topo de um boletim específico.
 */
private function inserirLogoBoletim(Worksheet $sheet, int $linhaInicio, string $colInicio, array $cols): void
{
    if (!file_exists($this->caminhoLogo)) {
        return;
    }

    $drawing = new Drawing();
    $drawing->setName('Logo Escola');
    $drawing->setDescription('Logo do Boletim');
    $drawing->setPath($this->caminhoLogo);

    // ✅ Definir proporção ANTES de definir dimensões
    $drawing->setResizeProportional(true);
    $drawing->setHeight(self::LOGO_HEIGHT); // largura ajusta automaticamente

    // ✅ Âncora na linha do nome da escola (offset 0), não em -1
    $linhaAncora = max(1, $linhaInicio + self::OFF_ESCOLA);
    $drawing->setCoordinates($cols['inicio'] . $linhaAncora);

    // Largura aproximada das colunas (em pixels)
$larguras = [
    'A' => 27.57, 'B' => 10.14, 'C' => 9.43, 'D' => 9.14,
    'G' => 27.29, 'H' => 10.14, 'I' => 9.43, 'J' => 9.57,
];

// Converter largura Excel → pixels (aprox. 1 unidade ≈ 7px)
$toPx = fn($v) => $v * 7;

// Somar largura do bloco atual
$colunas = $colInicio === 'A'
    ? ['A','B','C','D']
    : ['G','H','I','J'];

$larguraTotal = array_sum(array_map(fn($c) => $toPx($larguras[$c]), $colunas));

// Largura estimada do logo
$larguraLogo = self::LOGO_HEIGHT * 2; // ajuste conforme proporção da imagem

// Centralizar
$offsetX = ($larguraTotal - $larguraLogo) / 2;

$drawing->setOffsetX((int) max(0, $offsetX));
    $drawing->setOffsetY(self::LOGO_OFFSET_Y);

    $drawing->setWorksheet($sheet);
}

// ── Helpers de escrita ────────────────────────────────────────────────────

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

private function celulaComBorda(Worksheet $sheet, string $ref, mixed $valor, array $opts = [], bool $comBorda = false): void
{
    $this->celula($sheet, $ref, $valor, $opts);
    
    if ($comBorda) {
        $sheet->getStyle($ref)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => self::COR_BORDA],
                ],
            ],
        ]);
    }
}

private function linha(Worksheet $sheet, int $row, string $colIni, string $colFim, string $valor, array $opts = []): void
{
    $range = "{$colIni}{$row}:{$colFim}{$row}";
    $sheet->mergeCells($range);
    $this->celula($sheet, "{$colIni}{$row}", $valor, $opts);
}

private function colunas(string $colInicio): array
{
    if ($colInicio === 'A') {
        return [
            'inicio' => 'A', 
            'notas'  => ['B', 'C', 'D'],
            'fim'    => 'D'
        ];
    }
    return [
        'inicio' => 'G', 
        'notas'  => ['H', 'I', 'J'], 
        'fim'    => 'J'
    ];
}

// ── Lógica dinâmica de notas ───────────────────────────────────────────

private function getConfiguracaoNotas(): array
{
    if ($this->isDecimaTerceiraFinal()) {
        return [
            ['key' => 'cfd_efetiva', 'label' => 'CFD'],
            ['key' => 'media_final', 'label' => 'MF'],
            ['key' => 'resultado', 'label' => 'RESULT'],
        ];
    }

    $config = $this->turma->anoLetivo?->configuracaoAvaliacao;

    if (! $config || ! in_array($this->trimestre, ['1', '2', '3'], true)) {
        return [['key' => 'cfd_efetiva', 'label' => 'CFD']];
    }

    return $config->provas
        ->where('periodo', (int) $this->trimestre)
        ->where('ativo', true)
        ->sortBy('ordem')
        ->map(fn ($prova) => [
            'key' => $prova->codigo,
            'label' => strtoupper($prova->codigo),
        ])
        ->values()
        ->all();
}

private function valoresPeriodo(Nota $nota): array
{
    $fmt = fn ($v) => $v !== null ? number_format((float) $v, 2, ',', '') : '';
    $valores = [];

    if ($this->isDecimaTerceiraFinal()) {
        $resumo = $this->classificacoesEnsinoMedio->get($nota->aluno_id);

        return [
            'cfd_efetiva' => $fmt($nota->cfd_efetiva),
            'media_final' => $fmt(data_get($resumo, 'media_final')),
            'resultado' => (string) data_get($resumo, 'resultado', ''),
        ];
    }

    foreach ($this->getConfiguracaoNotas() as $config) {
        $chave = $config['key'];
        $valores[$chave] = $fmt($nota->{$chave} ?? null);
    }

    return $valores;
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

private function escreverBoletimDecimaTerceira(
    Worksheet $sheet,
    int $linhaInicio,
    array $cols,
    object $aluno,
    int $numeroOrdem,
    Collection $notas
): void {
    $classe = $this->turma->classe;
    $curso  = $this->turma->curso?->nome ?? 'CURSO';
    $resumo = $this->classificacoesEnsinoMedio->get($aluno->id);
    $notaProjecto = $notas->sortBy(fn ($nota) => $nota->disciplina?->nome)->first();

    $this->linha($sheet, $linhaInicio + self::OFF_ESCOLA, $cols['inicio'], $cols['fim'],
        $this->nomeEscola, ['bold' => true, 'size' => 8, 'align' => 'center']);
    $this->linha($sheet, $linhaInicio + self::OFF_AREA, $cols['inicio'], $cols['fim'],
        $this->areaFormacao ? 'AREA DE FORMACAO DE '.strtoupper($this->areaFormacao) : '', ['size' => 8, 'align' => 'center']);
    $this->linha($sheet, $linhaInicio + self::OFF_CURSO, $cols['inicio'], $cols['fim'],
        'CURSO DE '.strtoupper($curso), ['bold' => true, 'size' => 8, 'align' => 'center']);
    $this->linha($sheet, $linhaInicio + self::OFF_TITULO, $cols['inicio'], $cols['fim'],
        'BOLETIM DE NOTAS ', ['bold' => true, 'size' => 8, 'align' => 'center', 'cor' => self::COR_VERDE]);
    $this->linha($sheet, $linhaInicio + self::OFF_PERIODO, $cols['inicio'], $cols['fim'],
        "ANO LECTIVO: {$this->turma->anoLetivo?->nome}               {$this->labelPeriodo()}",
        ['size' => 8, 'align' => 'center']);

    $this->celula($sheet, $cols['inicio'] . ($linhaInicio + self::OFF_NOME),
        strtoupper($aluno->name), ['bold' => true, 'size' => 9, 'cor' => self::COR_VERMELHO]);

    $salaNumero = $this->turma->sala ?? 'â€”';
    $this->celula($sheet, $cols['inicio'] . ($linhaInicio + self::OFF_CLASSE),
        "     {$classe}.Âª CLASSE      NÂº {$numeroOrdem}       TURMA: {$this->turma->nome}       SALA NÂº {$salaNumero} ",
        ['size' => 7, 'cor' => self::COR_AZUL_ESC]);

    $rowHeader = $linhaInicio + self::OFF_HEADER;
    $this->celulaComBorda($sheet, $cols['inicio'] . $rowHeader, 'DISCIPLINA',
        ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center'], true);
    $this->celulaComBorda($sheet, $cols['notas'][0] . $rowHeader, 'CFD',
        ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center'], true);
    $this->celulaComBorda($sheet, $cols['notas'][1] . $rowHeader, 'MF',
        ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center'], true);
    $this->celulaComBorda($sheet, $cols['notas'][2] . $rowHeader, 'RESULT',
        ['bold' => true, 'size' => 8, 'cor' => self::COR_VERDE, 'align' => 'center'], true);

    $rowProjeto = $linhaInicio + self::OFF_DISC_INI;
    $this->celulaComBorda($sheet, $cols['inicio'] . $rowProjeto, (string) ($notaProjecto?->disciplina?->nome ?? 'Projecto Tecnologico'), ['size' => 8], true);
    $this->celulaComBorda($sheet, $cols['notas'][0] . $rowProjeto, $this->formatarNumeroResumo13($notaProjecto?->cfd_efetiva), ['size' => 8, 'align' => 'center'], true);
    $this->celulaComBorda($sheet, $cols['notas'][1] . $rowProjeto, $this->formatarNumeroResumo13(data_get($resumo, 'media_final')), ['size' => 8, 'align' => 'center'], true);
    $this->celulaComBorda($sheet, $cols['notas'][2] . $rowProjeto, (string) data_get($resumo, 'resultado', ''), ['size' => 8, 'align' => 'center'], true);

    $this->escreverLinhaResumo13($sheet, $cols, $linhaInicio + self::OFF_DISC_INI + 1, 'PC', $this->formatarNumeroResumo13(data_get($resumo, 'pc')));
    $this->escreverLinhaResumo13($sheet, $cols, $linhaInicio + self::OFF_DISC_INI + 2, 'E.C.S', $this->formatarNumeroResumo13(data_get($resumo, 'classificacao.ecs')));
    $this->escreverLinhaResumo13($sheet, $cols, $linhaInicio + self::OFF_DISC_INI + 3, 'PAP', $this->formatarNumeroResumo13(data_get($resumo, 'classificacao.pap')));
    $this->escreverLinhaResumo13($sheet, $cols, $linhaInicio + self::OFF_DISC_INI + 4, 'OBS.', (string) data_get($resumo, 'classificacao.observacoes', ''));

    $rowDirL = $linhaInicio + self::OFF_DIRETOR_L;
    $rowDirN = $linhaInicio + self::OFF_DIRETOR_N;
    $this->celula($sheet, $cols['inicio'] . $rowDirL, 'O DIRECTOR DE TURMA: ', ['size' => 8]);
    $this->celula($sheet, $cols['inicio'] . $rowDirN, $this->turma->coordenador?->name ?? '', ['size' => 11]);

    $rangeBoletim = $cols['inicio'] . ($linhaInicio + self::OFF_ESCOLA)
        . ':' . $cols['fim'] . ($linhaInicio + self::OFF_DIRETOR_N);

    $sheet->getStyle($rangeBoletim)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['argb' => self::COR_BORDA],
            ],
        ],
    ]);

    $this->aplicarBordasTabela($sheet, $cols, $linhaInicio, 3);
}

private function escreverLinhaResumo13(Worksheet $sheet, array $cols, int $row, string $label, string $valor): void
{
    $this->celulaComBorda($sheet, $cols['inicio'].$row, $label, ['size' => 8], true);
    $this->celulaComBorda($sheet, $cols['notas'][0].$row, $valor, ['size' => 8, 'align' => 'center'], true);
    $this->celulaComBorda($sheet, $cols['notas'][1].$row, '', ['size' => 8], true);
    $this->celulaComBorda($sheet, $cols['notas'][2].$row, '', ['size' => 8], true);
}

private function isDecimaTerceiraFinal(): bool
{
    return (int) $this->turma->classe === 13 && $this->trimestre === 'final';
}

private function formatarNumeroResumo13(mixed $valor): string
{
    return is_numeric($valor) ? number_format((float) $valor, 2, ',', '') : '';
}

private function aplicarBordasTabela(Worksheet $sheet, array $cols, int $linhaInicio, int $qtdColunasNotas): void
{
    $linhaInicioDisc = $linhaInicio + self::OFF_HEADER;
    $linhaFimDisc    = $linhaInicio + self::OFF_DISC_INI + 11;
    
    $colunasTabela = [$cols['inicio'], ...array_slice($cols['notas'], 0, $qtdColunasNotas)];
    $colInicioTabela = $colunasTabela[0];
    $colFimTabela = end($colunasTabela);
    
    $rangeTabela = "{$colInicioTabela}{$linhaInicioDisc}:{$colFimTabela}{$linhaFimDisc}";
    
    $sheet->getStyle($rangeTabela)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['argb' => self::COR_BORDA],
            ],
        ],
    ]);
    
    $this->ocultarBordasColunasInativas($sheet, $cols, $linhaInicioDisc, $linhaFimDisc, $qtdColunasNotas);
}

private function ocultarBordasColunasInativas(
    Worksheet $sheet, 
    array $cols, 
    int $linhaInicio, 
    int $linhaFim, 
    int $qtdColunasAtivas
): void {
    for ($i = $qtdColunasAtivas; $i < 3; $i++) {
        $col = $cols['notas'][$i] ?? null;
        if ($col) {
            for ($linha = $linhaInicio; $linha <= $linhaFim; $linha++) {
                $ref = "{$col}{$linha}";
                $sheet->getStyle($ref)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                    ],
                ]);
                if ($linha === $linhaInicio) {
                    $sheet->getColumnDimension($col)->setVisible(false);
                }
            }
        }
    }
}
}
