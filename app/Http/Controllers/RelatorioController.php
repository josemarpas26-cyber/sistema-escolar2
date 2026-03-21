<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\Disciplina;
use App\Models\User;
use App\Models\AnoLetivo;
use App\Models\HistoricoAcademico;
use App\Models\ProfessorTurmaDisciplina;
use App\Exports\BoletimExport;
use App\Exports\PautaExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class RelatorioController extends Controller
{
    public function index()
    {
        $this->checkPermission('relatorios.boletins');

        $user = auth()->user();
        $anoLetivoAtivo = AnoLetivo::ativo()->first();

        $anosLetivos = AnoLetivo::orderByDesc('id')->get();
        $turmas = Turma::with(['curso', 'anoLetivo'])->orderBy('classe')->get();
        $disciplinas = Disciplina::ativos()->orderBy('nome')->get();
        $alunos = User::alunos()->orderBy('name')->get();
        $professores = User::professores()->orderBy('name')->get();

                if ($this->isProfessorComRestricao($user)) {
            $anosLetivos = $anoLetivoAtivo ? collect([$anoLetivoAtivo]) : collect();

                   $anoLetivoAtivoId = $anoLetivoAtivo?->id;

            $atribuicoes = $user->atribuicoes()
                ->when($anoLetivoAtivoId, fn($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
                ->get(['turma_id', 'disciplina_id']);

            $turmaIdsPermitidas = $atribuicoes->pluck('turma_id')->unique()->values();
            $disciplinaIdsProfessor = $atribuicoes->pluck('disciplina_id')->unique()->values();

            if ($this->isCoordenadorTurma($user)) {
                $turmaCoord = Turma::where('coordenador_turma_id', $user->id)
                    ->when($anoLetivoAtivoId, fn($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
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
                        ->when($anoLetivoAtivoId, fn($q) => $q->where('ano_letivo_id', $anoLetivoAtivoId))
                        ->pluck('id');

                    $turmaIdsPermitidas = $turmaIdsPermitidas
                        ->merge($turmaIdsCurso)
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
                                ->when($anoLetivoAtivoId, fn($qf) => $qf->where('ano_letivo_id', $anoLetivoAtivoId));
                        });
                    }
                })
                ->orderBy('nome')
                ->get();

            $alunos = User::alunos()
                ->whereHas('turmas', fn($q) => $q->whereIn('turmas.id', $turmaIdsPermitidas))
                ->orderBy('name')
                ->get();
        }

        $anoLetivo = $anoLetivoAtivo;

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
        $user = auth()->user();

        if (!$aluno && $request->filled('aluno_id')) {
            $aluno = User::alunos()->findOrFail($request->aluno_id);
        }

        if (!$aluno) {
            if ($user->isAluno()) {
                $aluno = $user;
                $this->checkPermission('notas.view_own');
            } else {
                return back()->with('error', 'Selecione um aluno para gerar o boletim.');
            }
        }

        if (!$user->isAluno() || $user->id !== $aluno->id) {
            $this->checkPermission('relatorios.boletins');
        }

        $anoLetivoAtivo = AnoLetivo::ativo()->first();
        $anoLetivoId = $request->ano_letivo_id ?? $anoLetivoAtivo?->id;

        if (!$anoLetivoId) {
            return back()->with('error', 'Nenhum ano letivo ativo encontrado!');
        }

        $anoLetivo = AnoLetivo::findOrFail($anoLetivoId);
        $turma = $aluno->turmas()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->first();

        if (!$turma) {
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
            ->map(fn($nota) => $this->valorPeriodo($nota, $trimestre))
            ->filter(fn($valor) => $valor !== null);

        $mediaGeral = $valoresPeriodo->avg();
        $aprovacoes = $valoresPeriodo->filter(fn($v) => $v >= 10)->count();
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

        if (!$disciplina) {
            $query = Nota::where('turma_id', $turma->id)
                ->where('ano_letivo_id', $anoLetivoId)
                    ->with(['aluno', 'disciplina']);

            if ($aplicarRestricaoProfessor) {
                $query->whereIn('disciplina_id', $disciplinasPermitidas);
            }

            $notas = $query->get()
                ->groupBy('disciplina_id');

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
            ->map(fn($nota) => $this->valorPeriodo($nota, $trimestre))
            ->filter(fn($valor) => $valor !== null);

        $mediaGeral = $valoresPeriodo->avg();
        $aprovacoes = $valoresPeriodo->filter(fn($v) => $v >= 10)->count();
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
        if (!$aluno && $request->filled('aluno_id')) {
            $aluno = User::alunos()->findOrFail($request->aluno_id);
        }

        if (!$aluno) {
            $aluno = auth()->user();
        }

        $this->checkPermission('relatorios.historico');

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

        if (!$professor && $request->filled('professor_id')) {
            $professor = User::professores()->findOrFail($request->professor_id);
        }

        if (!$professor) {
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

            return $pdf->download('historico-professor-' . $professor->id . '.pdf');
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
            ->setPaper('a4', 'portrait');

        return $pdf->download('boletim-' . $dados['aluno']->numero_processo . '.pdf');
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
            'boletim-' . $dados['aluno']->numero_processo . '.xlsx'
        );
    }

    private function gerarPautaDisciplinaPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.pauta', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->download(
            'pauta-' . $dados['turma']->nome . '-' . $dados['disciplina']->codigo . '.pdf'
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
            'pauta-' . $dados['turma']->nome . '-' . $dados['disciplina']->codigo . '.xlsx'
        );
    }

    private function gerarHistoricoPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.historico', $dados)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'    => false, // bloqueia requests HTTP externos
                'isHtml5ParserEnabled' => true,
                'defaultFont'        => 'Arial',
                'chroot'             => storage_path('app/public'), // restringe acesso ao disco
            ]);

        return $pdf->download('historico-' . $dados['aluno']->numero_processo . '.pdf');
    }

    private function gerarPautaGeralPDF(array $dados)
    {
        $pdf = Pdf::loadView('relatorios.pdf.pauta-geral', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->download('pauta-geral-' . $dados['turma']->nome . '.pdf');
    }

    public function consolidadoTurma(Request $request, Turma $turma)
{
    $this->checkPermission('relatorios.pautas');

    $user = auth()->user();
    $anoLetivoAtivo = AnoLetivo::ativo()->first();

    $anoLetivoId = $request->ano_letivo_id ?? $turma->ano_letivo_id;
    $trimestre = $request->trimestre ?? 'final';

    $this->regrasAcessoPauta($user, $turma, null, $anoLetivoId, $anoLetivoAtivo);
    $anoLetivo = AnoLetivo::findOrFail($anoLetivoId);

    $notas = Nota::where('turma_id', $turma->id)
        ->where('ano_letivo_id', $anoLetivoId)
        ->with(['aluno', 'disciplina'])
        ->get();

    // Agrupa por aluno
    $notasPorAluno = $notas->groupBy('aluno_id');

    $dadosAlunos = $notasPorAluno->map(function ($notasAluno) use ($trimestre) {

        $valores = $notasAluno
            ->map(fn($nota) => $this->valorPeriodo($nota, $trimestre))
            ->filter(fn($valor) => $valor !== null);

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

        return $pdf->download('consolidado-' . $turma->nome . '.pdf');
    }

    return view('relatorios.consolidado-turma', $dados);
}
 private function isProfessorComRestricao(User $user): bool
    {
        return $user->isProfessor() && !$user->isAdmin() && !$user->isSecretaria();
    }

    private function isCoordenadorTurma(User $user): bool
    {
        return $user->isProfessor() && $user->isCoordenadorTurma();
    }

    private function isCoordenadorCurso(User $user): bool
    {
        return $user->isProfessor() && $user->isCoordenadorCurso();
    }

    private function regrasAcessoBoletim(
        User $user,
        Turma $turma,
        AnoLetivo $anoLetivo,
        ?int $disciplinaId,
        ?AnoLetivo $anoLetivoAtivo
    ): array {
        if (!$this->isProfessorComRestricao($user)) {
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

        if (!$disciplinaId) {
            abort(403, 'Professor deve selecionar uma disciplina específica.');
        }

        if (!$anoLetivoAtivo || $anoLetivo->id !== $anoLetivoAtivo->id) {
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

        if ($disciplinaId && !in_array((int) $disciplinaId, $disciplinasPermitidas, true)) {
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
        if (!$this->isProfessorComRestricao($user)) {
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

        if (!$disciplina) {
            abort(403, 'Professor deve selecionar uma disciplina específica.');
        }

        if (!$anoLetivoAtivo || $anoLetivoId !== $anoLetivoAtivo->id) {
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

        if ($disciplina && !in_array($disciplina->id, $disciplinasPermitidas, true)) {
            abort(403, 'Sem permissão para visualizar pauta desta disciplina.');
        }

        return [true, $disciplinasPermitidas];
    }
}