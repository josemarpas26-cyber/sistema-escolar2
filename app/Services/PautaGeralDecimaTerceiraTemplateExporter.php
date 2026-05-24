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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PautaGeralDecimaTerceiraTemplateExporter
{
    private const TEMPLATE_PATH = 'resources/templates/pauta-geral-13-template.xlsx';
    private const DATA_START = 15;
    private const DATA_END = 44;
    private const FOOTER_START = 45;
    private const LAST_COL = 'AK';

    private const REGULAR_DISC_COLS = [
        'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
    ];

    private const PROJECT_COLS = [
        'ca_12' => 'W',
        'mft2' => 'X',
        'mac3' => 'Y',
        'pp3' => 'Z',
        'mt3' => 'AA',
        'ca' => 'AB',
        'cfd' => 'AC',
    ];

    public function build(array $dados): Spreadsheet
    {
        /** @var Turma $turma */
        $turma = $dados['turma'];
        $anoLetivo = $dados['anoLetivo'] ?? null;

        $turma->loadMissing(['curso.coordenador', 'coordenador', 'disciplinas.cursos', 'anoLetivo']);
        $anoLetivo ??= $turma->anoLetivo;

        $alunos = $turma->alunos()
            ->wherePivotIn('status', ['matriculado', 'recurso', 'aprovado', 'reprovado', 'concluido'])
            ->orderBy('name')
            ->get();

        $disciplinas = $this->ordenarDisciplinas($turma->disciplinas)->values();
        [$disciplinasRegulares, $disciplinaProjecto] = $this->separarDisciplinas($disciplinas);

        if ($disciplinasRegulares->count() > count(self::REGULAR_DISC_COLS)) {
            throw new \RuntimeException(
                'O template da 13ª classe suporta no máximo '.count(self::REGULAR_DISC_COLS).' disciplinas regulares.'
            );
        }

        $notas = $this->resolveNotas($turma, $anoLetivo, $dados);
        $notasIndex = $this->indexarNotas($notas);
        $resumoClassificacoes = app(ClassificacaoEnsinoMedioService::class)
            ->montarResumoDaTurma($turma, $notas)
            ->keyBy(fn (array $item) => $item['aluno']->id);

        $atribuicoes = $turma->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id ?? $turma->ano_letivo_id)
            ->with(['professor', 'disciplina.cursos'])
            ->get();

        $spreadsheet = $this->loadTemplateSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $footerStart = $this->ajustarEstruturaVertical($sheet, $alunos->count());
        $lastDataRow = max(self::DATA_START, $footerStart - 1);
        $lastRow = $footerStart + 14;

        $this->limparAreaDeDados($sheet, $lastDataRow);
        $this->atualizarCabecalho($sheet, $turma, $anoLetivo);
        $this->atualizarCabecalhosDeDisciplinas($sheet, $disciplinasRegulares, $disciplinaProjecto);
        $this->preencherLinhasDosAlunos(
            $sheet,
            $alunos,
            $disciplinasRegulares,
            $disciplinaProjecto,
            $notasIndex,
            $resumoClassificacoes
        );
        $this->atualizarRodape($sheet, $turma, $atribuicoes, $footerStart);
        $this->ajustarImpressao($sheet, $lastRow);

        return $spreadsheet;
    }

    private function loadTemplateSpreadsheet(): Spreadsheet
    {
        $templatePath = base_path(self::TEMPLATE_PATH);

        if (! file_exists($templatePath)) {
            throw new \RuntimeException('Template da pauta geral da 13ª classe não encontrado em '.$templatePath);
        }

        return IOFactory::load($templatePath);
    }

    private function ajustarEstruturaVertical(Worksheet $sheet, int $totalAlunos): int
    {
        $capacidadeBase = self::DATA_END - self::DATA_START + 1;
        $extraRows = max(0, $totalAlunos - $capacidadeBase);
        $rowsToRemove = max(0, $capacidadeBase - $totalAlunos);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(self::FOOTER_START, $extraRows);
            $modeloEstilo = $sheet->getStyle('A'.self::DATA_END.':'.self::LAST_COL.self::DATA_END);
            $alturaModelo = $sheet->getRowDimension(self::DATA_END)->getRowHeight();

            for ($row = self::DATA_END + 1; $row <= self::DATA_END + $extraRows; $row++) {
                $sheet->duplicateStyle($modeloEstilo, 'A'.$row.':'.self::LAST_COL.$row);
                $sheet->getRowDimension($row)->setRowHeight($alturaModelo);
            }
        } elseif ($rowsToRemove > 0) {
            $sheet->removeRow(self::DATA_START + $totalAlunos, $rowsToRemove);
        }

        return self::DATA_START + $totalAlunos;
    }

    private function limparAreaDeDados(Worksheet $sheet, int $lastDataRow): void
    {
        if ($lastDataRow < self::DATA_START) {
            return;
        }

        $lastColIndex = Coordinate::columnIndexFromString(self::LAST_COL);

        for ($row = self::DATA_START; $row <= $lastDataRow; $row++) {
            for ($colIndex = 1; $colIndex <= $lastColIndex; $colIndex++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex).$row, null);
            }
        }
    }

    private function atualizarCabecalho(Worksheet $sheet, Turma $turma, ?AnoLetivo $anoLetivo): void
    {
        $anoNome = $anoLetivo?->nome ?? ($turma->anoLetivo?->nome ?? date('Y').'/'.(date('Y') + 1));
        $cursoNome = Str::upper((string) ($turma->curso?->nome ?? ''));
        $areaNome = Str::upper((string) data_get($turma, 'curso.areaFormacao.nome', $turma->curso?->nome ?? ''));

        $sheet->setCellValue('A7', $this->nomeDirector());
        $sheet->setCellValue('D5', $this->nomeInstituicao());
        $sheet->setCellValue('D6', 'ÁREA DE FORMAÇÃO DE '.$areaNome);
        $sheet->setCellValue('F7', 'CURSO DE '.$cursoNome);
        $sheet->setCellValue('D9', 'PAUTA FINAL DA 13ª CLASSE');
        $sheet->setCellValue('C10', 'ANO LECTIVO: '.$anoNome);
        $sheet->setCellValue('AB10', 'TURMA: '.$this->codigoTurma($turma));
    }

    private function atualizarCabecalhosDeDisciplinas(
        Worksheet $sheet,
        Collection $disciplinasRegulares,
        ?Disciplina $disciplinaProjecto
    ): void {
        foreach (self::REGULAR_DISC_COLS as $col) {
            $sheet->setCellValue($col.'12', null);
        }

        foreach ($disciplinasRegulares->values() as $index => $disciplina) {
            $sheet->setCellValue(
                self::REGULAR_DISC_COLS[$index].'12',
                $this->abreviarDisciplina($disciplina).' - CFD'
            );
        }

        if ($disciplinaProjecto) {
            $sheet->setCellValue('U12', Str::upper(Str::ascii($disciplinaProjecto->nome)));
        }
    }

    private function preencherLinhasDosAlunos(
        Worksheet $sheet,
        EloquentCollection $alunos,
        Collection $disciplinasRegulares,
        ?Disciplina $disciplinaProjecto,
        array $notasIndex,
        Collection $resumoClassificacoes
    ): void {
        foreach ($alunos as $offset => $aluno) {
            $row = self::DATA_START + $offset;
            $notasAluno = $notasIndex[$aluno->id] ?? [];
            $resumo = $resumoClassificacoes->get($aluno->id);
            $classificacao = $resumo['classificacao'] ?? null;

            $sheet->setCellValueExplicit('A'.$row, (string) ($offset + 1), DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B'.$row, (string) ($aluno->numero_processo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$row, (string) $aluno->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D'.$row, Str::upper((string) ($aluno->genero ?? '')), DataType::TYPE_STRING);

            foreach ($disciplinasRegulares->values() as $index => $disciplina) {
                $nota = $notasAluno[$disciplina->id] ?? null;
                $this->preencherCelula($sheet, self::REGULAR_DISC_COLS[$index].$row, $nota?->cfd_efetiva);
            }

            $notaProjecto = $disciplinaProjecto ? ($notasAluno[$disciplinaProjecto->id] ?? null) : null;

            $this->preencherCelula($sheet, self::PROJECT_COLS['ca_12'].$row, $notaProjecto?->ca_12);
            $this->preencherCelula($sheet, self::PROJECT_COLS['mft2'].$row, $notaProjecto?->mft2);
            $this->preencherCelula($sheet, self::PROJECT_COLS['mac3'].$row, $notaProjecto?->mac3);
            $this->preencherCelula($sheet, self::PROJECT_COLS['pp3'].$row, $notaProjecto?->pp3);
            $this->preencherCelula($sheet, self::PROJECT_COLS['mt3'].$row, $notaProjecto?->mt3);
            $this->preencherCelula($sheet, self::PROJECT_COLS['ca'].$row, $notaProjecto?->ca);
            $this->preencherCelula($sheet, self::PROJECT_COLS['cfd'].$row, $notaProjecto?->cfd_efetiva);

            $this->preencherCelula($sheet, 'AF'.$row, $classificacao?->ecs);
            $this->preencherCelula($sheet, 'AG'.$row, $classificacao?->pap);
            $this->preencherCelula($sheet, 'AH'.$row, $resumo['pc'] ?? null);
            $this->preencherCelula($sheet, 'AI'.$row, $resumo['media_final'] ?? null);

            $sheet->setCellValueExplicit('AJ'.$row, (string) ($classificacao?->observacoes ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(
                'AK'.$row,
                Str::upper((string) ($resumo['resultado'] ?? 'Pendente')),
                DataType::TYPE_STRING
            );
        }
    }

    private function atualizarRodape(Worksheet $sheet, Turma $turma, Collection $atribuicoes, int $footerStart): void
    {
        $sheet->setCellValue('A'.$footerStart, 'DATA DO CONSELHO DE TURMA');
        $sheet->setCellValue('A'.($footerStart + 1), '_______/_______/_______');
        $sheet->setCellValue('A'.($footerStart + 3), 'OBSERVAÇÃO:');

        $professoresTerminais = $atribuicoes
            ->filter(fn ($atribuicao) => $atribuicao->disciplina?->ehTerminalNaTurma($turma))
            ->values();

        $sheet->setCellValue('U'.$footerStart, $professoresTerminais->get(0)?->professor?->name ?? '');
        $sheet->setCellValue('AD'.$footerStart, $professoresTerminais->get(1)?->professor?->name ?? '');

        $sheet->setCellValue('A'.($footerStart + 10), $this->nomeDirectorTurma($turma));
        $sheet->setCellValue('F'.($footerStart + 10), $this->nomeCoordenadorCurso($turma));
        $sheet->setCellValue('X'.($footerStart + 10), $this->nomeSubdirectorPedagogico());
    }

    private function ajustarImpressao(Worksheet $sheet, int $lastRow): void
    {
        $sheet->freezePane('A'.self::DATA_START);
        $sheet->getPageSetup()->setPrintArea('A1:'.self::LAST_COL.$lastRow);
    }

    private function separarDisciplinas(Collection $disciplinas): array
    {
        $disciplinaProjecto = $disciplinas->first(function (Disciplina $disciplina) {
            $nome = $this->normalize($disciplina->nome);

            return str_contains($nome, 'PROJECTO') || str_contains($nome, 'PROJETO');
        });

        if (! $disciplinaProjecto && $disciplinas->count() > count(self::REGULAR_DISC_COLS)) {
            $disciplinaProjecto = $disciplinas->last();
        }

        $disciplinasRegulares = $disciplinas
            ->when($disciplinaProjecto, fn (Collection $items) => $items->reject(fn (Disciplina $disciplina) => $disciplina->id === $disciplinaProjecto->id))
            ->values();

        return [$disciplinasRegulares, $disciplinaProjecto];
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

    private function preencherCelula(Worksheet $sheet, string $cell, mixed $valor): void
    {
        if ($valor === null || $valor === '') {
            $sheet->setCellValue($cell, null);

            return;
        }

        if (is_numeric($valor)) {
            $sheet->setCellValueExplicit($cell, (string) $valor, DataType::TYPE_NUMERIC);

            return;
        }

        $sheet->setCellValueExplicit($cell, (string) $valor, DataType::TYPE_STRING);
    }

    private function nomeInstituicao(): string
    {
        return config('escola.nome_instituicao', 'INSTITUTO POLITECNICO INDUSTRIAL Nº 8050 LDA - "NOVA VIDA" - KILAMBA KIAXI');
    }

    private function nomeDirector(): string
    {
        return config('escola.nome_director', 'Ferreira Manuel Fragoso, Ph,D');
    }

    private function nomeSubdirectorPedagogico(): string
    {
        return config('escola.nome_subdirector_pedagogico', 'Carlos Alberto Brito Teixeira da Silva');
    }

    private function nomeDirectorTurma(Turma $turma): string
    {
        return $turma->coordenador?->name ?? config('escola.director_turma_fallback', '');
    }

    private function nomeCoordenadorCurso(Turma $turma): string
    {
        return $turma->curso?->coordenador?->name ?? config('escola.coordenador_curso_fallback', '');
    }

    private function codigoTurma(Turma $turma): string
    {
        $prefixo = Str::upper((string) ($turma->curso?->codigo ?? ''));
        $sufixo = Str::upper((string) ($turma->nome ?? ''));

        return trim($prefixo.$turma->classe.$sufixo);
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
            'ORGANIZACAO E GESTAO INDUSTRIAL' => 135,
            'OGI' => 135,
            'DESENHO TECNICO' => 136,
            'TECNICAS DE LINGUAGEM DE PROGRAMACAO' => 140,
            'TLP' => 140,
            'TECNICAS DE REDES ESTRUTURADAS DE INFORMATICA' => 145,
            'TREI' => 145,
            'SISTEMAS DE EXPLORACAO E APLICACOES' => 150,
            'SEAC' => 150,
            'FORMACAO DE ATITUDES INTEGRADORAS' => 160,
            'FAI' => 160,
            'INGLES TECNICO' => 170,
            'PROJECTO TECNOLOGICO' => 999,
            'PROJETO TECNOLOGICO' => 999,
        ];

        return $disciplinas->sortBy(function (Disciplina $disciplina) use ($ordem) {
            $nome = $this->normalize($disciplina->nome);

            return str_pad((string) ($ordem[$nome] ?? 900), 4, '0', STR_PAD_LEFT).'-'.$nome;
        });
    }

    private function abreviarDisciplina(Disciplina $disciplina): string
    {
        $aliases = [
            'LINGUA PORTUGUESA' => 'L. PORTUGUESA',
            'PORTUGUES' => 'PORTUGUÊS',
            'INGLES' => 'INGLÊS',
            'EDUCACAO FISICA' => 'ED. FÍS',
            'MATEMATICA' => 'MATEMÁTICA',
            'FISICA' => 'FÍSICA',
            'QUIMICA' => 'QUÍMICA',
            'ELECTROTECNIA' => 'ELECTROTECNIA',
            'EMPREENDEDORISMO' => 'EMPREE',
            'INFORMATICA' => 'T.I.C',
            'TIC' => 'T.I.C',
            'FORMACAO DE ATITUDES INTEGRADORAS' => 'F.A.I',
            'FAI' => 'F.A.I',
            'TECNICAS DE LINGUAGEM DE PROGRAMACAO' => 'T.L.P',
            'TLP' => 'T.L.P',
            'SISTEMAS DE EXPLORACAO E APLICACOES' => 'S.E.A.C',
            'SEAC' => 'S.E.A.C',
            'ORGANIZACAO E GESTAO INDUSTRIAL' => 'O.G.I',
            'OGI' => 'O.G.I',
            'TECNICAS DE REDES ESTRUTURADAS DE INFORMATICA' => 'T.R.E.I',
            'TREI' => 'T.R.E.I',
            'DESENHO TECNICO' => 'DESEN. TÉCN',
            'INGLES TECNICO' => 'INGLÊS TECN.',
        ];

        $normalized = $this->normalize($disciplina->nome);

        return $aliases[$normalized] ?? Str::upper(Str::limit(Str::ascii($disciplina->nome), 16, ''));
    }

    private function normalize(string $value): string
    {
        return Str::upper(Str::ascii($value));
    }
}
