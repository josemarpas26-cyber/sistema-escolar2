<?php

namespace App\Http\Controllers;

use App\Exports\BoletimExport;
use App\Exports\PautaExport;
use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\HistoricoAcademico;
use App\Models\Nota;
use App\Models\ProfessorTurmaDisciplina;
use App\Models\Turma;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BoletimMassaExport;

class RelatorioController extends Controller
{
    public function index(Request $request)
    {
        $this->checkPermission('relatorios.boletins');

        $user = auth()->user();
        $anoLetivoAtivo = AnoLetivo::ativo()->first();
        $anoLetivoSelecionadoId = $request->integer('ano_letivo_id') ?: $anoLetivoAtivo?->id;

        $anosLetivos = AnoLetivo::orderByDesc('id')->get();
        $turmas = Turma::with(['curso', 'anoLetivo'])
            ->when($anoLetivoSelecionadoId, fn ($q) => $q->where('ano_letivo_id', $anoLetivoSelecionadoId))
            ->orderBy('classe')
            ->get();

        $disciplinas = Disciplina::ativos()
            ->when($anoLetivoSelecionadoId, fn ($q) => $q->whereHas('notas', fn ($qq) => $qq
                ->where('ano_letivo_id', $anoLetivoSelecionadoId)))
            ->orderBy('nome')
            ->get();

        $alunos = User::alunos()
            ->when($anoLetivoSelecionadoId, fn ($q) => $q->whereHas('turmas', fn ($qq) => $qq
                ->where('ano_letivo_id', $anoLetivoSelecionadoId)
                ->where('turma_aluno.status', 'matriculado')))
            ->orderBy('name')
            ->get();
        $professores = User::professores()->orderBy('name')->get();

        if ($this->isProfessorComRestricao($user)) {
            $anosLetivos = $anoLetivoAtivo ? collect([$anoLetivoAtivo]) : collect();

            $anoLetivoAtivoId = $anoLetivoAtivo?->id;

            $atribuicoes = $user->atribuicoes()
                ->when($anoLetivoAtivoId, fn ($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
                ->get(['turma_id', 'disciplina_id']);

            $turmaIdsPermitidas = $atribuicoes->pluck('turma_id')->unique()->values();
            $disciplinaIdsProfessor = $atribuicoes->pluck('disciplina_id')->unique()->values();

            if ($this->isCoordenadorTurma($user)) {
                $turmaCoord = Turma::where('coordenador_turma_id', $user->id)
                    ->when($anoLetivoAtivoId, fn ($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
                    ->first();

                if ($turmaCoord) {
                    $turmaIdsPermitidas = $turmaIdsPermitidas->push($turmaCoord->id)->unique()->values();
                }
            }

            if ($this->isCoordenadorCurso($user)) {
                $cursoId = $user->cursoCoordenado?->id;

                if ($cursoId) {
                    $turmaIdsCurso = Turma::query()
                        ->where('curso_id', $cursoId)
                        ->when($anoLetivoAtivoId, fn ($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
                        ->pluck('id');

                    $turmaIdsPermitidas = $turmaIdsPermitidas
                        ->merge($turmaIdsCurso)
                        ->unique()
                        ->values();
                }
            }

            if ($this->isCoordenadorDisciplina($user)) {
                $disciplinaCoordenada = $user->disciplinaCoordenada()->first();

                if ($disciplinaCoordenada) {
                    $disciplinaIdsProfessor = $disciplinaIdsProfessor
                        ->push($disciplinaCoordenada->id)
                        ->unique()
                        ->values();

                    $turmaIdsDisciplina = $disciplinaCoordenada->turmas()
                        ->when($anoLetivoAtivoId, fn ($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
                        ->pluck('turmas.id');

                    $turmaIdsPermitidas = $turmaIdsPermitidas
                        ->merge($turmaIdsDisciplina)
                        ->unique()
                        ->values();
                }
            }
            $turmas = Turma::with(['curso', 'anoLetivo'])
                ->whereIn('id', $turmaIdsPermitidas)
                ->orderBy('classe')
                ->get();

            $disciplinas = Disciplina::ativos()
                ->where(function ($q) use ($user, $disciplinaIdsProfessor, $turmaIdsPermitidas, $anoLetivoAtivoId) {
                    $q->whereIn('id', $disciplinaIdsProfessor);

                    if ($this->isCoordenadorCurso($user) || $this->isCoordenadorTurma($user)) {
                        $q->orWhereHas('notas', function ($qq) use ($turmaIdsPermitidas, $anoLetivoAtivoId) {
                            $qq->whereIn('turma_id', $turmaIdsPermitidas)
                                ->when($anoLetivoAtivoId, fn ($qf) => $qf->where('ano_letivo_id', $anoLetivoAtivoId));
                        });
                    }
                })
                ->orderBy('nome')
                ->get();

            $alunos = User::alunos()
                ->whereHas('turmas', fn ($q) => $q->whereIn('turmas.id', $turmaIdsPermitidas))
                ->orderBy('name')
                ->get();
        }

        $anoLetivo = $anoLetivoSelecionadoId
            ? $anosLetivos->firstWhere('id', $anoLetivoSelecionadoId)
            : $anoLetivoAtivo;

        return view('relatorios.index', compact(
            'anoLetivo',
            'anosLetivos',
            'turmas',
            'disciplinas',
            'alunos',
            'professores'
        ));
    }

    public function boletimAluno(Request $request, ?User $aluno = null)
    {
        $this->checkPermission('relatorios.boletins'); // no início do método

        $user = auth()->user();

        if (! $aluno && $request->filled('aluno_id')) {
            $aluno = User::alunos()->findOrFail($request->aluno_id);
        }

        if (! $aluno) {
            if ($user->isAluno()) {
                $aluno = $user;
                $this->checkPermission('notas.view_own');
            } else {
                return back()->with('error', 'Selecione um aluno para gerar o boletim.');
            }
        }

        if (! $user->isAluno() || $user->id !== $aluno->id) {
            $this->checkPermission('relatorios.boletins');
        }

        $anoLetivoAtivo = AnoLetivo::ativo()->first();
        $anoLetivoId = $request->ano_letivo_id ?? $anoLetivoAtivo?->id;

        if (! $anoLetivoId) {
            return back()->with('error', 'Nenhum ano letivo ativo encontrado!');
        }

        $anoLetivo = AnoLetivo::findOrFail($anoLetivoId);
        $turma = $aluno->turmas()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->first();

        if (! $turma) {
            return back()->with('error', 'Aluno não possui turma no ano letivo selecionado!');
        }
        $disciplinaId = $request->filled('disciplina_id') ? (int) $request->disciplina_id : null;
        $trimestre = $request->trimestre ?? 'final';

        [$aplicarRestricaoProfessor, $disciplinasPermitidas] = $this->regrasAcessoBoletim(
            $user,
            $turma,
            $anoLetivo,
            $disciplinaId,
            $anoLetivoAtivo
        );
        $notasQuery = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with('disciplina');

        if ($aplicarRestricaoProfessor) {
            $notasQuery->whereIn('disciplina_id', $disciplinasPermitidas);
        }

        if ($disciplinaId) {
            $notasQuery->where('disciplina_id', $disciplinaId);
        }

        $notas = $notasQuery->get();

        $valoresPeriodo = $notas
            ->map(fn ($nota) => $this->valorPeriodo($nota, $trimestre))
            ->filter(fn ($valor) => $valor !== null);

        $mediaGeral = $valoresPeriodo->avg();
        $aprovacoes = $valoresPeriodo->filter(fn ($v) => $v >= 10)->count();
        $reprovacoes = $valoresPeriodo->count() - $aprovacoes;

        $dados = [
            'aluno' => $aluno,
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'notas' => $notas,
            'mediaGeral' => round($mediaGeral ?? 0, 2),
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'trimestre' => $trimestre,
            'disciplinaSelecionada' => $disciplinaId
                ? Disciplina::find($disciplinaId)
                : null,
        ];

        if ($request->formato === 'pdf') {
            return $this->gerarBoletimPDF($dados);
        }

        if ($request->formato === 'excel') {
            return $this->gerarBoletimExcel($dados);
        }

        return view('relatorios.boletim-aluno', $dados);
    }

    public function pautaTurma(Request $request, Turma $turma, ?Disciplina $disciplina = null)
    {
        $this->checkPermission('relatorios.pautas');

        $user = auth()->user();
        $anoLetivoAtivo = AnoLetivo::ativo()->first();
        $anoLetivoId = $request->ano_letivo_id ?? $turma->ano_letivo_id;
        $trimestre = $request->trimestre ?? 'final';

        [$aplicarRestricaoProfessor, $disciplinasPermitidas] = $this->regrasAcessoPauta(
            $user,
            $turma,
            $disciplina,
            $anoLetivoId,
            $anoLetivoAtivo
        );

        if (! $disciplina) {
            $query = Nota::where('turma_id', $turma->id)
                ->where('ano_letivo_id', $anoLetivoId)
                ->with(['aluno', 'disciplina']);

            if ($aplicarRestricaoProfessor) {
                $query->whereIn('disciplina_id', $disciplinasPermitidas);
            }

            $notasColecao = $query->get();
            $notas = $notasColecao->groupBy('disciplina_id');
            $disciplinasVisiveis = $turma->disciplinas()
                ->when($aplicarRestricaoProfessor, fn ($q) => $q->whereIn('disciplinas.id', $disciplinasPermitidas))
                ->get();

            $turma->setRelation('disciplinas', $disciplinasVisiveis);
            $turma->setRelation('notas', $notasColecao);

            $dados = [
                'turma' => $turma,
                'notasPorDisciplina' => $notas,
                'trimestre' => $trimestre,
                'anoLetivo' => AnoLetivo::find($anoLetivoId),
            ];

            if ($request->formato === 'pdf') {
                return $this->gerarPautaGeralPDF($dados);
            }

            return view('relatorios.pauta-geral', $dados);
        }

        $notas = Nota::where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('ano_letivo_id', $anoLetivoId)
            ->with('aluno')
            ->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'notas.aluno_id')
            )
            ->get();

        $valoresPeriodo = $notas
            ->map(fn ($nota) => $this->valorPeriodo($nota, $trimestre))
            ->filter(fn ($valor) => $valor !== null);

        $mediaGeral = $valoresPeriodo->avg();
        $aprovacoes = $valoresPeriodo->filter(fn ($v) => $v >= 10)->count();
        $reprovacoes = $valoresPeriodo->count() - $aprovacoes;

        $dados = [
            'turma' => $turma,
            'disciplina' => $disciplina,
            'notas' => $notas,
            'mediaGeral' => round($mediaGeral ?? 0, 2),
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'totalAlunos' => $notas->count(),
            'trimestre' => $trimestre,
            'anoLetivo' => AnoLetivo::find($anoLetivoId),
        ];

        if ($request->formato === 'pdf') {
            return $this->gerarPautaDisciplinaPDF($dados);
        }

        if ($request->formato === 'excel') {
            return $this->gerarPautaExcel($dados);
        }

        return view('relatorios.pauta-disciplina', $dados);
    }

    public function historicoAcademico(Request $request, ?User $aluno = null)
    {
        $this->checkPermission('relatorios.historico');
        if (! $aluno && $request->filled('aluno_id')) {
            $aluno = User::alunos()->findOrFail($request->aluno_id);
        }

        if (! $aluno) {
            $aluno = auth()->user();
        }

        if ($aluno && auth()->user()->isAluno() && $aluno->id !== auth()->id()) {
            abort(403, 'Não tem permissão para ver o histórico de outro aluno.');
        }

        if (! $aluno) {
            abort(404, 'Aluno não encontrado.');
        }

        $historico = HistoricoAcademico::porAluno($aluno->id)
            ->with(['disciplina', 'turma', 'anoLetivo'])
            ->get()
            ->groupBy('ano_letivo_id');

        $dados = [
            'aluno' => $aluno,
            'historico' => $historico,
        ];

        if ($request->formato === 'pdf') {
            return $this->gerarHistoricoPDF($dados);
        }

        return view('relatorios.historico-academico', $dados);
    }

    public function historicoProfessor(Request $request, ?User $professor = null)
    {
        $this->checkPermission('relatorios.historico');

        if (! $professor && $request->filled('professor_id')) {
            $professor = User::professores()->findOrFail($request->professor_id);
        }

        if (! $professor) {
            return back()->with('error', 'Selecione um professor para ver o histórico.');
        }

        $atribuicoes = ProfessorTurmaDisciplina::where('professor_id', $professor->id)
            ->with(['turma.curso', 'disciplina', 'anoLetivo'])
            ->orderBy('ano_letivo_id')
            ->get()
            ->groupBy('ano_letivo_id');

        $dados = [
            'professor' => $professor,
            'atribuicoes' => $atribuicoes,
        ];

        if ($request->formato === 'pdf') {
            $pdf = Pdf::loadView('relatorios.pdf.historico-professor', $dados)
                ->setPaper('a4', 'portrait');

            return $pdf->download('historico-professor-'.$professor->id.'.pdf');
        }

        return view('relatorios.historico-professor', $dados);
    }

    private function valorPeriodo(Nota $nota, string $trimestre): ?float
    {
        return match ($trimestre) {
            '1' => $nota->mt1,
            '2' => $nota->mt2,
            '3' => $nota->mt3,
            default => $nota->cfd,
        };
    }

    private function gerarBoletimPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.boletim', $dados)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'Arial',
                'chroot' => base_path(),
            ]);

        return $pdf->download('boletim-'.$dados['aluno']->numero_processo.'.pdf');
    }

    private function gerarBoletimExcel(array $dados)
    {
        return Excel::download(
            new BoletimExport(
                $dados['aluno'],
                $dados['turma'],
                $dados['notas'],
                $dados['mediaGeral']
            ),
            'boletim-'.$dados['aluno']->numero_processo.'.xlsx'
        );
    }

    private function gerarPautaDisciplinaPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.pauta', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->download(
            'pauta-'.$dados['turma']->nome.'-'.$dados['disciplina']->codigo.'.pdf'
        );
    }

    private function gerarPautaExcel(array $dados)
    {
        return Excel::download(
            new PautaExport(
                $dados['turma'],
                $dados['disciplina'],
                $dados['notas'],
                $dados
            ),
            'pauta-'.$dados['turma']->nome.'-'.$dados['disciplina']->codigo.'.xlsx'
        );
    }

    private function gerarHistoricoPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.historico', $dados)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => false, // bloqueia requests HTTP externos
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'Arial',
                'chroot' => base_path(),
            ]);

        return $pdf->download('historico-'.$dados['aluno']->numero_processo.'.pdf');
    }

    private function gerarPautaGeralPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.pauta-geral', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->download('pauta-geral-'.$dados['turma']->nome.'.pdf');
    }

    public function consolidadoTurma(Request $request, Turma $turma)
    {
        $this->checkPermission('relatorios.pautas');

        $user = auth()->user();
        $anoLetivoAtivo = AnoLetivo::ativo()->first();

        $anoLetivoId = $request->ano_letivo_id ?? $turma->ano_letivo_id;
        $trimestre = $request->trimestre ?? 'final';

        [$aplicarRestricaoProfessor, $disciplinasPermitidas] = $this->regrasAcessoPauta(
            $user,
            $turma,
            null,
            $anoLetivoId,
            $anoLetivoAtivo
        );
        $anoLetivo = AnoLetivo::findOrFail($anoLetivoId);

        $notasQuery = Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivoId)
            ->with(['aluno', 'disciplina']);

        if ($aplicarRestricaoProfessor) {
            $notasQuery->whereIn('disciplina_id', $disciplinasPermitidas);
        }

        $notas = $notasQuery->get();

        // Agrupa por aluno
        $notasPorAluno = $notas->groupBy('aluno_id');

        $dadosAlunos = $notasPorAluno->map(function ($notasAluno) use ($trimestre) {

            $valores = $notasAluno
                ->map(fn ($nota) => $this->valorPeriodo($nota, $trimestre))
                ->filter(fn ($valor) => $valor !== null);

            $media = $valores->avg();

            return [
                'aluno' => $notasAluno->first()->aluno,
                'media' => round($media ?? 0, 2),
                'aprovado' => $media !== null && $media >= 10,
            ];
        });

        $mediaGeralTurma = round(
            $dadosAlunos->avg('media') ?? 0,
            2
        );

        $totalAprovados = $dadosAlunos->where('aprovado', true)->count();
        $totalReprovados = $dadosAlunos->count() - $totalAprovados;

        $dados = [
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'trimestre' => $trimestre,
            'dadosAlunos' => $dadosAlunos,
            'mediaGeralTurma' => $mediaGeralTurma,
            'totalAprovados' => $totalAprovados,
            'totalReprovados' => $totalReprovados,
        ];

        if ($request->formato === 'pdf') {
            $pdf = Pdf::loadView('relatorios.pdf.consolidado-turma', $dados)
                ->setPaper('a4', 'landscape');

            return $pdf->download('consolidado-'.$turma->nome.'.pdf');
        }

        return view('relatorios.consolidado-turma', $dados);
    }

    private function isProfessorComRestricao(User $user): bool
    {
        return $user->isProfessor() && ! $user->isAdmin() && ! $user->isSecretaria();
    }

    private function isCoordenadorTurma(User $user): bool
    {
        return $user->isProfessor() && $user->isCoordenadorTurma();
    }

    private function isCoordenadorCurso(User $user): bool
    {
        return $user->isProfessor() && $user->isCoordenadorCurso();
    }

    private function isCoordenadorDisciplina(User $user): bool
    {
        return $user->isProfessor() && $user->isCoordenadorDisciplina();
    }

    private function disciplinaCoordenadaNaTurma(User $user, Turma $turma, int $anoLetivoId): ?Disciplina
    {
        if (! $this->isCoordenadorDisciplina($user) || $turma->ano_letivo_id !== $anoLetivoId) {
            return null;
        }

        $disciplina = $user->disciplinaCoordenada()->first();

        if (! $disciplina) {
            return null;
        }

        $pertenceTurma = $turma->disciplinas()
            ->where('disciplinas.id', $disciplina->id)
            ->exists();

        return $pertenceTurma ? $disciplina : null;
    }

    private function regrasAcessoBoletim(
        User $user,
        Turma $turma,
        AnoLetivo $anoLetivo,
        ?int $disciplinaId,
        ?AnoLetivo $anoLetivoAtivo
    ): array {
        if (! $this->isProfessorComRestricao($user)) {
            return [false, []];
        }

        $podeComoCoordenadorCurso = $this->isCoordenadorCurso($user)
        && $turma->curso_id === $user->cursoCoordenado?->id
        && $turma->ano_letivo_id === $anoLetivo->id;

        $podeComoCoordenadorTurma = $this->isCoordenadorTurma($user)
          && $turma->coordenador_turma_id === $user->id
          && $turma->ano_letivo_id === $anoLetivo->id;

        if ($podeComoCoordenadorCurso || $podeComoCoordenadorTurma) {

            return [false, []];
        }

        $disciplinaCoordenada = $this->disciplinaCoordenadaNaTurma($user, $turma, $anoLetivo->id);

        if ($disciplinaCoordenada && (! $disciplinaId || $disciplinaId === $disciplinaCoordenada->id)) {
            return [true, [$disciplinaCoordenada->id]];
        }

        if (! $disciplinaId) {
            abort(403, 'Professor deve selecionar uma disciplina específica.');
        }

        if (! $anoLetivoAtivo || $anoLetivo->id !== $anoLetivoAtivo->id) {
            abort(403, 'Professor só pode visualizar dados do ano letivo corrente.');
        }

        $atribuicoes = $user->atribuicoes()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->get(['disciplina_id']);

        if ($atribuicoes->isEmpty()) {
            abort(403, 'Sem permissão para visualizar boletim desta turma.');
        }

        $disciplinasPermitidas = $atribuicoes->pluck('disciplina_id')->unique()->values()->all();

        if ($disciplinaId && ! in_array((int) $disciplinaId, $disciplinasPermitidas, true)) {
            abort(403, 'Sem permissão para visualizar boletim desta disciplina.');
        }

        return [true, $disciplinasPermitidas];
    }

    private function regrasAcessoPauta(
        User $user,
        Turma $turma,
        ?Disciplina $disciplina,
        int|string $anoLetivoId,
        ?AnoLetivo $anoLetivoAtivo
    ): array {
        if (! $this->isProfessorComRestricao($user)) {
            return [false, []];
        }

        $anoLetivoId = (int) $anoLetivoId;

        $podeComoCoordenadorCurso = $this->isCoordenadorCurso($user)
            && $turma->curso_id === $user->cursoCoordenado?->id
            && $turma->ano_letivo_id === $anoLetivoId;

        $podeComoCoordenadorTurma = $this->isCoordenadorTurma($user)
            && $turma->coordenador_turma_id === $user->id
            && $turma->ano_letivo_id === $anoLetivoId;

        if ($podeComoCoordenadorCurso || $podeComoCoordenadorTurma) {

            return [false, []];
        }

        $disciplinaCoordenada = $this->disciplinaCoordenadaNaTurma($user, $turma, $anoLetivoId);

        if ($disciplinaCoordenada && (! $disciplina || $disciplina->id === $disciplinaCoordenada->id)) {
            return [true, [$disciplinaCoordenada->id]];
        }

        if (! $disciplina) {
            abort(403, 'Professor deve selecionar uma disciplina específica.');
        }

        if (! $anoLetivoAtivo || $anoLetivoId !== $anoLetivoAtivo->id) {
            abort(403, 'Professor só pode visualizar dados do ano letivo corrente.');
        }

        $atribuicoes = $user->atribuicoes()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivoId)
            ->get(['disciplina_id']);

        if ($atribuicoes->isEmpty()) {
            abort(403, 'Sem permissão para visualizar pauta desta turma.');
        }

        $disciplinasPermitidas = $atribuicoes->pluck('disciplina_id')->unique()->values()->all();

        if ($disciplina && ! in_array($disciplina->id, $disciplinasPermitidas, true)) {
            abort(403, 'Sem permissão para visualizar pauta desta disciplina.');
        }

        return [true, $disciplinasPermitidas];
    }

    public function pautaGeral(Request $request, Turma $turma)
    {
        $this->checkPermission('relatorios.pautas');

        $user = auth()->user();
        $anoLetivoAtivo = AnoLetivo::ativo()->first();
        $anoLetivoId = $request->ano_letivo_id ?? $turma->ano_letivo_id;
        $trimestre = $request->trimestre ?? 'final';

        [$aplicarRestricaoProfessor, $disciplinasPermitidas] = $this->regrasAcessoPauta(
            $user,
            $turma,
            null,
            $anoLetivoId,
            $anoLetivoAtivo
        );

        $anoLetivo = AnoLetivo::find($anoLetivoId);

        $notasQuery = Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivoId)
            ->with(['aluno', 'disciplina']);

        if ($aplicarRestricaoProfessor) {
            $notasQuery->whereIn('disciplina_id', $disciplinasPermitidas);
        }

        $notasColecao = $notasQuery->get();
        $notas = $notasColecao->groupBy('disciplina_id');
        $disciplinasVisiveis = $turma->disciplinas()
            ->when($aplicarRestricaoProfessor, fn ($q) => $q->whereIn('disciplinas.id', $disciplinasPermitidas))
            ->get();

        $turma->setRelation('disciplinas', $disciplinasVisiveis);
        $turma->setRelation('notas', $notasColecao);

        $dados = [
            'turma' => $turma,
            'notasPorDisciplina' => $notas,
            'trimestre' => $trimestre,
            'anoLetivo' => $anoLetivo,
        ];

        if ($request->formato === 'xlsx') {
            return $this->gerarPautaGeralXlsx($dados);
        }

        if ($request->formato === 'pdf') {
            return $this->gerarPautaGeralPDF($dados);
        }

        return view('relatorios.pauta-geral', $dados);
    }

    private function gerarPautaGeralXlsx(array $dados): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return app(\App\Services\PautaGeralTemplateExporter::class)->download($dados);
    }



    private function gerarBoletimMassaPDF(array $dados, string $sufixoAluno = '')
    {
        $pdf = Pdf::loadView('relatorios.pdf.boletins-massa', [
            'turma' => $dados['turma'],
            'trimestre' => $dados['trimestre'],
            'periodoLabel' => $this->labelPeriodoBoletimMassa($dados['trimestre']),
            'configNotas' => $this->configuracaoNotasBoletimMassa($dados['trimestre']),
            'notasPorAluno' => $dados['notasPorAluno'],
        ])->setPaper('a4', 'portrait');

        $nomeArquivo = 'boletins-'
            . str($dados['turma']->nome)->slug()
            . '-t'.$dados['trimestre']
            . $sufixoAluno
            . '.pdf';

        return $pdf->download($nomeArquivo);
    }

    private function configuracaoNotasBoletimMassa(string $trimestre): array
    {
        return match ($trimestre) {
            '1' => [
                ['key' => 'mt1', 'label' => 'MT1'],
            ],
            '2' => [
                ['key' => 'mt1', 'label' => 'MT1'],
                ['key' => 'mt2', 'label' => 'MT2'],
                ['key' => 'mft2', 'label' => 'MFT2'],
            ],
            '3' => [
                ['key' => 'mt1', 'label' => 'MT1'],
                ['key' => 'mt2', 'label' => 'MT2'],
                ['key' => 'mt3', 'label' => 'MT3'],
            ],
            default => [
                ['key' => 'cfd', 'label' => 'CFD'],
            ],
        };
    }

    private function labelPeriodoBoletimMassa(string $trimestre): string
    {
        return match ($trimestre) {
            '1' => 'Iº TRIMESTRE',
            '2' => 'IIº TRIMESTRE',
            '3' => 'IIIº TRIMESTRE',
            default => 'CLASSIFICAÇÃO FINAL',
        };
    }


/**
     * Gera boletins em massa para todos os alunos de uma turma.
     * Formato: 2 boletins por linha, idêntico ao template Excel.
     *
     * GET/POST  /relatorios/boletins-massa
     * Parâmetros: turma_id, trimestre (1|2|3|final), ano_letivo_id (opcional)
     */
    public function boletimMassa(Request $request)
    {
        $this->checkPermission('relatorios.boletins');

        $validated = $request->validate([
            'turma_id'      => 'required|exists:turmas,id',
            'aluno_id'      => 'nullable|exists:users,id',
            'formato'       => 'nullable|in:xlsx,pdf',
            'trimestre'     => 'nullable|in:1,2,3,final',
            'ano_letivo_id' => 'nullable|exists:anos_letivos,id',
        ]);

        $turma = Turma::with([
            'curso',
            'anoLetivo',
            'coordenador',
            'alunos' => fn ($q) => $q->wherePivot('status', 'matriculado')->orderBy('name'),
        ])->findOrFail($validated['turma_id']);

        // Usar o ano lectivo da turma ou o pedido; fallback para o activo
        $anoLetivoId = $validated['ano_letivo_id']
            ?? $turma->ano_letivo_id
            ?? AnoLetivo::ativo()->value('id');

        if (! $anoLetivoId) {
            return back()->with('error', 'Nenhum ano lectivo encontrado.');
        }

        $alunoSelecionado = null;

        if (! empty($validated['aluno_id'])) {
            $alunoSelecionado = $turma->alunos->firstWhere('id', (int) $validated['aluno_id']);

            if (! $alunoSelecionado) {
                return back()->with('error', 'O aluno selecionado não pertence à turma escolhida.');
            }

            $turma->setRelation('alunos', collect([$alunoSelecionado]));
        }

        $notas = Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivoId)
            ->when($alunoSelecionado, fn ($query) => $query->where('aluno_id', $alunoSelecionado->id))
            ->with(['disciplina:id,nome,codigo'])
            ->get()
            ->groupBy('aluno_id');

        $trimestre = $validated['trimestre'] ?? '2';
        $formato = $validated['formato'] ?? 'xlsx';

        $sufixoAluno = $alunoSelecionado
            ? '-'.str($alunoSelecionado->numero_processo ?? $alunoSelecionado->id)->slug()
            : '';

        if ($formato === 'pdf') {
            return $this->gerarBoletimMassaPDF([
                'turma' => $turma,
                'notasPorAluno' => $notas,
                'trimestre' => $trimestre,
            ], $sufixoAluno);
        }

        $filename = 'boletins-'
            . str($turma->nome)->slug()
            . '-t' . $trimestre
            . $sufixoAluno
            . '.xlsx';

        return Excel::download(
            new BoletimMassaExport($turma, $notas, $trimestre),
            $filename
        );
    }

}
