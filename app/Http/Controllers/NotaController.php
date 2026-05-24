<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\AvaliacaoContinua;
use App\Models\ClassificacaoEnsinoMedio;
use App\Models\Disciplina;
use App\Models\DivisaoAritmeticaSolicitacao;
use App\Models\HistoricoAcademico;
use App\Models\Nota;
use App\Models\NotaLog;
use App\Models\ProfessorTurmaDisciplina;
use App\Models\Turma;
use App\Models\User;
use App\Notifications\PautaFinalizadaNotification;
use App\Notifications\PautaDesbloqueadaNotification;
use App\Services\EstadoMatriculaService;
use App\Services\EstatisticasAcademicasService;
use App\Services\NotaService;
use App\Support\IdMask;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotaController extends Controller
{
    // Mapeamento dos campos por trimestre — única fonte de verdade
    private const CAMPOS_TRIMESTRE = [
        '1' => ['mac1', 'pp1', 'pt1'],
        '2' => ['mac2', 'pp2', 'pt2'],
        '3' => ['mac3', 'pp3', 'pg'],
    ];

    private const CAMPOS_EDITAVEIS_POR_TRIMESTRE = [
        '1' => ['pp1', 'pt1'],
        '2' => ['pp2', 'pt2'],
        '3' => ['pp3', 'pg'],
    ];

    private const BLOQUEIO_POR_CAMPO = [
        'pp1' => 'bloqueado_pp1',
        'pt1' => 'bloqueado_pt1',
        'pp2' => 'bloqueado_pp2',
        'pt2' => 'bloqueado_pt2',
        'pp3' => 'bloqueado_pp3',
        'pg' => 'bloqueado_pg',
    ];

    private const CAMPO_OPERACAO_POR_TRIMESTRE = [
        '1' => ['pp' => 'pp1', 'pt' => 'pt1'],
        '2' => ['pp' => 'pp2', 'pt' => 'pt2'],
        '3' => ['pp' => 'pp3', 'pg' => 'pg'],
    ];

    public function __construct(
        private readonly NotaService $notaService,
        private readonly EstatisticasAcademicasService $estatisticasAcademicas,
        private readonly EstadoMatriculaService $estadoMatriculaService
    ) {}

    // -------------------------------------------------------------------------
    // Index / visualização
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $user = auth()->user();

        return match (true) {
            $user->isProfessor() => $this->professorIndex($request),
            $user->isSecretaria() || $user->isAdmin() => $this->secretariaIndex($request),
            $user->isAluno() => $this->alunoIndex(),
            default => abort(403),
        };
    }

    public function professorIndex(Request $request)
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $atribuicoes = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['turma', 'disciplina'])
            ->get();

        $turmaToken = $request->query('turma_id');
        $disciplinaToken = $request->query('disciplina_id');
        $turmaId = IdMask::decode($turmaToken);
        $disciplinaId = IdMask::decode($disciplinaToken);

        if (($turmaToken && $turmaId === null) || ($disciplinaToken && $disciplinaId === null)) {
            abort(404);
        }
        $notas = null;
        $turma = null;
        $disciplina = null;
        $estatisticasPauta = null;
        $notificacoesDesbloqueio = $professor->unreadNotifications()
            ->whereIn('type', $this->tiposNotificacaoPauta())
            ->latest()
            ->take(5)
            ->get();

        if ($turmaId && $disciplinaId) {
            $temAtribuicao = $atribuicoes
                ->where('turma_id', $turmaId)
                ->where('disciplina_id', $disciplinaId)
                ->isNotEmpty();

            if (! $temAtribuicao) {
                return back()->with('error', 'Voce nao leciona esta disciplina nesta turma.');
            }

            $turma = Turma::findOrFail($turmaId);
            $disciplina = Disciplina::findOrFail($disciplinaId);
            $alunos = $turma->alunos()
                ->wherePivotIn('status', ['matriculado', 'recurso'])
                ->orderBy('users.name')
                ->get();

            $notasPorAluno = Nota::where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['aluno.turmas', 'anoLetivo', 'avaliacoesContinuas'])
                ->get()
                ->keyBy('aluno_id');

            $notas = $alunos
                ->map(function ($aluno) use ($notasPorAluno, $turma, $disciplina, $anoLetivo) {
                    $nota = $notasPorAluno->get($aluno->id);

                    if ($nota) {
                        $nota->aluno = $aluno;
                        $nota->setRelation('turma', $turma);
                        $nota->setRelation('disciplina', $disciplina);
                        $nota->setRelation('anoLetivo', $anoLetivo);
                    }

                    return $nota;
                })
                ->filter()
                ->values();

            $estatisticasPauta = $this->estatisticasAcademicas->resumoPauta($notas);
        }

        return view('notas.professor', compact(
            'atribuicoes',
            'notas',
            'turma',
            'disciplina',
            'estatisticasPauta',
            'notificacoesDesbloqueio',
            'turmaToken',
            'disciplinaToken'
        ));
    }

    public function secretariaIndex(Request $request)
    {
        $this->checkPermission('notas.view_all');

        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turmas = Turma::anoAtivo()->with('curso')->get();
        $disciplinas = collect();

        $turmaToken = $request->query('turma_id');
        $disciplinaToken = $request->query('disciplina_id');
        $turmaId = IdMask::decode($turmaToken);
        $disciplinaId = IdMask::decode($disciplinaToken);

        if (($turmaToken && $turmaId === null) || ($disciplinaToken && $disciplinaId === null)) {
            abort(404);
        }

        $request->merge([
            'turma_id' => $turmaId,
            'disciplina_id' => $disciplinaId,
        ]);

        if (($turmaToken && $turmaId === null) || ($disciplinaToken && $disciplinaId === null)) {
            abort(404);
        }

        $request->merge([
            'turma_id' => $turmaId,
            'disciplina_id' => $disciplinaId,
        ]);

        
        $notas = null;
        $notasAgrupadas = null;
        $turmaSelecionada = null;
        $disciplinaSelecionada = null;
        $estatisticasPauta = null;
        $classificacoesEnsinoMedio = collect();

        if ($turmaId) {
            $turmaSelecionada = Turma::findOrFail($turmaId);
            $disciplinas = $turmaSelecionada->disciplinas()->orderBy('nome')->get();

            $query = Nota::where('turma_id', $turmaSelecionada->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['aluno', 'turma', 'disciplina.cursos']);

            if ($disciplinaId) {
                $disciplinaSelecionada = Disciplina::findOrFail($disciplinaId);
                $query->where('disciplina_id', $disciplinaSelecionada->id);
            }

            if ($request->filled('aluno')) {
                $query->whereHas('aluno', fn ($q) => $q
                    ->where('name', 'like', "%{$request->aluno}%")
                    ->orWhere('numero_processo', 'like', "%{$request->aluno}%"));
            }

            $notas = $query->get();

            if ($disciplinaSelecionada) {
                $estatisticasPauta = $this->estatisticasAcademicas->resumoPauta($notas);
            }

            if (! $disciplinaSelecionada) {
                $notasAgrupadas = $notas
                    ->groupBy('aluno_id')
                    ->map(fn ($grupo) => [
                        'aluno' => $grupo->first()->aluno,
                        'notas' => $grupo->keyBy('disciplina_id'),
                    ]);

                if ((int) $turmaSelecionada->classe === 13) {
                    $classificacoesEnsinoMedio = app(\App\Services\ClassificacaoEnsinoMedioService::class)
                        ->montarResumoDaTurma($turmaSelecionada, $notas);
                }
            }
        }

        return view('notas.secretaria', compact(
            'turmas',
            'disciplinas',
            'notas',
            'notasAgrupadas',
            'turmaSelecionada',
            'disciplinaSelecionada',
            'estatisticasPauta',
            'classificacoesEnsinoMedio'
        ));
    }

    public function alunoIndex()
    {
        $this->checkPermission('notas.view_own');

        $aluno = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplina.coordenador', 'disciplina.cursos', 'turma', 'avaliacoesContinuas'])
            ->get();

        $turmaAtual = $notas->first()?->turma;
        $notasComCfd = $notas->filter(fn (Nota $nota) => $nota->cfd_efetiva !== null);
        $mediaGeral = $notasComCfd->isNotEmpty()
            ? round($notasComCfd->avg('cfd_efetiva'), 2)
            : 0;

        $aprovacoes = $notasComCfd->filter(fn (Nota $nota) => $nota->isAprovado())->count();
        $reprovacoes = $notasComCfd->count() - $aprovacoes;

        $atribuicoesPorDisciplina = $turmaAtual
            ? ProfessorTurmaDisciplina::query()
                ->where('turma_id', $turmaAtual->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with('professor')
                ->get()
                ->keyBy('disciplina_id')
            : collect();

        $disciplinasDetalhadas = $notas->map(function (Nota $nota) use ($atribuicoesPorDisciplina) {
            $indicador = null;
            $label = 'Sem lançamento';

            foreach ([
                'cfd_efetiva' => $nota->recursoMelhoraClassificacaoFinal() ? 'CFD (Recurso)' : 'CFD',
                'ca' => 'CA',
                'cf' => 'CF',
                'mt3' => 'MT3',
                'mft2' => 'MFT2',
                'mt2' => 'MT2',
                'mt1' => 'MT1',
            ] as $campo => $titulo) {
                if ($nota->{$campo} !== null) {
                    $indicador = (float) $nota->{$campo};
                    $label = $titulo;
                    break;
                }
            }

            return [
                'disciplina' => $nota->disciplina,
                'nota' => $nota,
                'professor' => $atribuicoesPorDisciplina->get($nota->disciplina_id)?->professor,
                'coordenador' => $nota->disciplina?->coordenador,
                'indicador' => [
                    'label' => $label,
                    'valor' => $indicador,
                ],
            ];
        });

        $classificacaoEnsinoMedioAtual = null;

        if ((int) ($turmaAtual?->classe ?? 0) === 13) {
            $classificacaoEnsinoMedioAtual = app(\App\Services\ClassificacaoEnsinoMedioService::class)
                ->montarResumoDaTurma($turmaAtual, $notas)
                ->firstWhere('aluno.id', $aluno->id);
        }

        return view('notas.aluno', compact(
            'notas',
            'turmaAtual',
            'mediaGeral',
            'aprovacoes',
            'reprovacoes',
            'disciplinasDetalhadas',
            'classificacaoEnsinoMedioAtual'
        ));
    }

    public function avaliacoesContinuasIndex(Request $request)
    {
        $user = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        if ($user->isAluno()) {
            $this->checkPermission('notas.view_own');

            $notas = Nota::query()
                ->where('aluno_id', $user->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['disciplina', 'avaliacoesContinuas' => fn ($query) => $query->orderBy('trimestre')->orderBy('data_avaliacao')])
                ->orderBy(Disciplina::select('nome')->whereColumn('disciplinas.id', 'notas.disciplina_id'))
                ->get();

            return view('notas.avaliacoes-continuas-aluno', compact('notas', 'anoLetivo'));
        }

        $turmas = collect();
        $disciplinas = collect();
        $atribuicoes = collect();

        if ($user->isProfessor()) {
            $this->checkPermission('avaliacoes_continuas.view');
            $atribuicoes = $user->atribuicoes()
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['turma', 'disciplina'])
                ->get();

            $turmas = $atribuicoes->pluck('turma')->unique('id')->sortBy('nome')->values();
        } else {
            $this->checkPermission('avaliacoes_continuas.view');
            $turmas = Turma::anoAtivo()->with('curso')->orderBy('classe')->get();
        }

        $turmaId = $request->integer('turma_id');
        $disciplinaId = $request->integer('disciplina_id');
        $notas = collect();
        $trimestreCorrente = $this->resolverTrimestreCorrente($anoLetivo);

        if ($turmaId) {
            if ($user->isProfessor()) {
                $disciplinas = $atribuicoes->where('turma_id', $turmaId)
                    ->pluck('disciplina')
                    ->unique('id')
                    ->sortBy('nome')
                    ->values();
            } else {
                $turmaSelecionada = Turma::findOrFail($turmaId);
                $disciplinas = $turmaSelecionada->disciplinas()->orderBy('nome')->get();
            }
        }

        if ($turmaId && $disciplinaId) {
            if ($user->isProfessor()) {
                $temAtribuicao = $atribuicoes
                    ->where('turma_id', $turmaId)
                    ->where('disciplina_id', $disciplinaId)
                    ->isNotEmpty();

                if (! $temAtribuicao) {
                    abort(403, 'Você não leciona esta disciplina nesta turma.');
                }
            }

            $notas = Nota::query()
                ->where('turma_id', $turmaId)
                ->where('disciplina_id', $disciplinaId)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['aluno', 'avaliacoesContinuas.professor'])
                ->orderBy(User::select('name')->whereColumn('users.id', 'notas.aluno_id'))
                ->get();
        }

        return view('notas.avaliacoes-continuas', compact(
            'turmas',
            'disciplinas',
            'notas',
            'turmaId',
            'disciplinaId',
            'anoLetivo',
            'trimestreCorrente'
        ));
    }

    private function buildFeedbackLancamento(
        string $trimestre,
        int $salvas,
        int $bloqueadas,
        int $naoAplicaveis,
        int $semAlteracao,
        array $naoEncontradas
    ): array {
        $partes = [];

        if ($salvas > 0) {
            $partes[] = "{$salvas} nota(s) lançada(s) com sucesso";
        }

        if ($semAlteracao > 0) {
            $partes[] = "{$semAlteracao} nota(s) sem alteração";
        }

        if ($bloqueadas > 0) {
            $partes[] = "{$bloqueadas} nota(s) ignorada(s) por estarem bloqueadas";
        }

        if ($naoAplicaveis > 0) {
            $partes[] = "{$naoAplicaveis} nota(s) ignorada(s) por nao se aplicarem a este trimestre";
        }

        if (! empty($naoEncontradas)) {
            $ids = implode(', ', $naoEncontradas);
            $partes[] = count($naoEncontradas)." nota(s) não encontrada(s) (IDs: {$ids})";
        }

        if (empty($partes)) {
            return ['warning', "Nenhuma nota do {$trimestre}º trimestre foi processada."];
        }

        $mensagem = "{$trimestre}º trimestre: ".implode('. ', $partes).'.';

        // success só se houve pelo menos 1 salva e nenhum problema
        if ($salvas > 0 && $bloqueadas === 0 && $naoAplicaveis === 0 && empty($naoEncontradas)) {
            $tipo = 'success';
        } elseif ($salvas === 0 && $semAlteracao > 0 && $bloqueadas === 0 && $naoAplicaveis === 0) {
            $tipo = 'info';
        } else {
            $tipo = 'warning';
        }

        return [$tipo, $mensagem];
    }

    // -------------------------------------------------------------------------
    // Lançamento de notas — método unificado
    // -------------------------------------------------------------------------

    /**
     * Lança as notas de um trimestre específico (1, 2 ou 3).
     * Substitui lancarTrimestre1, lancarTrimestre2 e lancarTrimestre3.
     *
     * Rota sugerida: POST /notas/trimestre/{trimestre}
     */
    public function lancarTrimestre(Request $request, string $trimestre)
    {
        if (! array_key_exists($trimestre, self::CAMPOS_TRIMESTRE)) {
            abort(404, 'Trimestre inválido.');
        }

        $user = auth()->user();

        $user->isProfessor()
            ? $this->checkPermission('notas.lancar')
            : $this->checkPermission('notas.editar');

        $campos = self::CAMPOS_EDITAVEIS_POR_TRIMESTRE[$trimestre];
        $rules = ['notas' => 'required|array', 'notas.*.id' => 'required|exists:notas,id'];

        foreach ($campos as $campo) {
            $rules["notas.*.{$campo}"] = 'nullable|numeric|min:-1|max:20';
        }

        $validated = $request->validate($rules);

        $ids = collect($validated['notas'])->pluck('id');
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['aluno.turmas', 'anoLetivo', 'turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        $salvas = 0;
        $bloqueadas = 0;
        $naoAplicaveis = 0;
        $semAlteracao = 0;
        $naoEncontradas = [];
        $alunosAlterados = [];

        DB::transaction(function () use (
            $validated, $notasMap, $campos, $trimestre, $user,
            &$salvas, &$bloqueadas, &$naoAplicaveis, &$semAlteracao, &$naoEncontradas, &$alunosAlterados
        ) {
            foreach ($validated['notas'] as $notaData) {
                $nota = $notasMap->get($notaData['id']);

                if (! $nota) {
                    $naoEncontradas[] = $notaData['id'];

                    continue;
                }

                if ($user->isProfessor()) {
                    $this->authorize('update', $nota);
                }

                if (! $nota->trimestreEstaDisponivel((int) $trimestre)) {
                    $naoAplicaveis++;

                    continue;
                }

                if ($this->notaBloqueadaParaEdicao($nota, $trimestre)) {
                    $bloqueadas++;

                    continue;
                }

                $teveCampoEditavel = false;
                $teveCampoBloqueado = false;

                // Atribui apenas campos editáveis (PP/PT/PG). MAC é sempre calculado.
                foreach ($campos as $campo) {
                    if (array_key_exists($campo, $notaData)) {
                        if ($this->campoEstaBloqueado($nota, $campo)) {
                            $teveCampoBloqueado = true;

                            continue;
                        }

                        $teveCampoEditavel = true;
                        $nota->{$campo} = $notaData[$campo];
                    }
                }

                if (! $teveCampoEditavel) {
                    $teveCampoBloqueado ? $bloqueadas++ : $semAlteracao++;

                    continue;
                }

                if (! $nota->isDirty($campos)) {
                    $semAlteracao++;

                    continue;
                }

                \App\Observers\NotaObserver::$suprimirLogs = true;
                $this->notaService->recalcularNota($nota);
                \App\Observers\NotaObserver::$suprimirLogs = false;
                $nota->save();
                HistoricoAcademico::updateOrCreate(
                    [
                        'aluno_id' => $nota->aluno_id,
                        'turma_id' => $nota->turma_id,
                        'disciplina_id' => $nota->disciplina_id,
                        'ano_letivo_id' => $nota->ano_letivo_id,
                    ],
                    [
                        'classe' => (string) ($nota->turma?->classe ?? ''),
                        'classificacao_final' => (float) ($nota->cfd_efetiva ?? $nota->cfd ?? 0),
                        'resultado' => $nota->recursoMelhoraClassificacaoFinal() ? 'aprovado' : 'recurso',
                        'observacoes' => 'Registo atualizado automaticamente após lançamento de recurso.',
                        'data_conclusao' => now(),
                    ]
                );
                $salvas++;
                $alunosAlterados[$nota->turma_id.'-'.$nota->aluno_id] = [
                    'turma_id' => (int) $nota->turma_id,
                    'aluno_id' => (int) $nota->aluno_id,
                ];
            }
        });

        foreach ($alunosAlterados as $dadosAluno) {
            $this->estadoMatriculaService->sincronizarAlunoNaTurma($dadosAluno['turma_id'], $dadosAluno['aluno_id']);
        }
        
        [$tipo, $mensagem] = $this->buildFeedbackLancamento(
            $trimestre, $salvas, $bloqueadas, $naoAplicaveis, $semAlteracao, $naoEncontradas
        );

        return back()->with($tipo, $mensagem);
    }

    public function lancarRecurso(Request $request)
    {
        $user = auth()->user();

        $user->isProfessor()
            ? $this->checkPermission('notas.lancar')
            : $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        $ids = collect($validated['notas'])->pluck('id');
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['aluno.turmas', 'anoLetivo', 'turma.curso', 'disciplina.cursos'])
            ->get()
            ->keyBy('id');

        $salvas = 0;
        $inelegiveis = 0;
        $semAlteracao = 0;
        $naoEncontradas = [];
        $alunosAlterados = [];

        DB::transaction(function () use (
            $validated,
            $notasMap,
            $user,
            &$salvas,
            &$inelegiveis,
            &$semAlteracao,
            &$naoEncontradas,
            &$alunosAlterados
        ) {
            foreach ($validated['notas'] as $notaData) {
                $nota = $notasMap->get($notaData['id']);

                if (! $nota) {
                    $naoEncontradas[] = $notaData['id'];

                    continue;
                }

                if ($user->isProfessor()) {
                    $this->authorize('update', $nota);
                }

                if (! $nota->elegivelParaRecurso()) {
                    $inelegiveis++;

                    continue;
                }

                if (! array_key_exists('nota_recurso', $notaData) || $notaData['nota_recurso'] === null || $notaData['nota_recurso'] === '') {
                    $semAlteracao++;

                    continue;
                }

                $nota->nota_recurso = round((float) $notaData['nota_recurso'], 2);

                if (! $nota->isDirty('nota_recurso')) {
                    $semAlteracao++;

                    continue;
                }

                $nota->save();

                HistoricoAcademico::updateOrCreate(
                    [
                        'aluno_id' => $nota->aluno_id,
                        'turma_id' => $nota->turma_id,
                        'disciplina_id' => $nota->disciplina_id,
                        'ano_letivo_id' => $nota->ano_letivo_id,
                    ],
                    [
                        'classe' => (string) ($nota->turma?->classe ?? ''),
                        'classificacao_final' => (float) ($nota->cfd_efetiva ?? $nota->cfd ?? 0),
                        'resultado' => $nota->recursoMelhoraClassificacaoFinal() ? 'aprovado' : 'recurso',
                        'observacoes' => 'Registo atualizado automaticamente após lançamento de recurso.',
                        'data_conclusao' => now(),
                    ]
                );
                $salvas++;
                $alunosAlterados[$nota->turma_id.'-'.$nota->aluno_id] = [
                    'turma_id' => (int) $nota->turma_id,
                    'aluno_id' => (int) $nota->aluno_id,
                ];
            }
        });

        foreach ($alunosAlterados as $dadosAluno) {
            $this->estadoMatriculaService->sincronizarAlunoNaTurma($dadosAluno['turma_id'], $dadosAluno['aluno_id']);
        }

        $partes = [];

        if ($salvas > 0) {
            $partes[] = "{$salvas} recurso(s) registado(s)";
        }

        if ($semAlteracao > 0) {
            $partes[] = "{$semAlteracao} sem alteraçao";
        }

        if ($inelegiveis > 0) {
            $partes[] = "{$inelegiveis} inelegivel(is)";
        }

        if (! empty($naoEncontradas)) {
            $partes[] = count($naoEncontradas).' registo(s) nao encontrado(s)';
        }

        $mensagem = empty($partes)
            ? 'Nenhum recurso foi processado.'
            : 'Recurso: '.implode('. ', $partes).'.';

        $tipo = $salvas > 0 ? 'success' : (! empty($partes) ? 'warning' : 'info');

        return back()->with($tipo, $mensagem);
    }

    // -------------------------------------------------------------------------
    // Edição individual
    // -------------------------------------------------------------------------

    public function edit(Nota $nota)
    {
        $this->authorize('update', $nota);

        $this->validarBloqueioFinalizacao($nota);

        $nota->load(['aluno.turmas', 'anoLetivo', 'turma', 'disciplina']);

        $camposCaSomenteLeitura = $this->resolverCamposCaSomenteLeitura($nota);

        return view('notas.edit', compact('nota', 'camposCaSomenteLeitura'));
    }

    public function update(Request $request, Nota $nota)
    {
        $this->authorize('update', $nota);

        $this->validarBloqueioFinalizacao($nota);

        $user = auth()->user();

        // Permissão funcional (separada da Policy)
        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        // Validação
        $allCampos = array_merge(...array_values(self::CAMPOS_TRIMESTRE));

        $rules = array_fill_keys(
            $allCampos,
            'nullable|numeric|min:-1|max:20'
        );

        $rules['ca_10'] = 'nullable|numeric|min:-1|max:20';
        $rules['ca_11'] = 'nullable|numeric|min:-1|max:20';
        $rules['ca_12'] = 'nullable|numeric|min:-1|max:20';
        $rules['nota_recurso'] = 'nullable|numeric|min:0|max:20';
        $rules['observacoes'] = 'nullable|string';

        $validated = $request->validate($rules);

        // 🔍 Detectar quais campos estão sendo alterados
        $nota->loadMissing(['aluno.turmas', 'anoLetivo']);

        $camposCaSomenteLeitura = $this->resolverCamposCaSomenteLeitura($nota);
        $mensagensCamposBloqueados = [];

        foreach ($camposCaSomenteLeitura as $campo => $somenteLeitura) {
            if (! $somenteLeitura || ! $request->has($campo)) {
                continue;
            }

            $mensagensCamposBloqueados[$campo] = 'Este campo é somente leitura porque o aluno já tem turma associada na classe anterior.';
        }

        if (! empty($mensagensCamposBloqueados)) {
            throw ValidationException::withMessages($mensagensCamposBloqueados);
        }

        if ($request->filled('nota_recurso') && ! $nota->elegivelParaRecurso()) {
            throw ValidationException::withMessages([
                'nota_recurso' => 'O recurso só pode ser lançado em disciplinas terminais com CFD negativa igual ou superior a 7.',
            ]);
        }

        $camposAlterados = array_keys(array_filter(
            $validated,
            fn ($value) => ! is_null($value)
        ));

        // MAC só pode vir da média de avaliações contínuas.
        if (! $user->isProfessor()) {
            unset($validated['mac1'], $validated['mac2'], $validated['mac3']);
            $camposAlterados = array_values(array_diff($camposAlterados, ['mac1', 'mac2', 'mac3']));
        }

        // 🧠 Mapear campos → trimestre
        foreach (self::CAMPOS_TRIMESTRE as $trimestre => $campos) {
            $intersect = array_intersect($camposAlterados, $campos);

            if (! empty($intersect)) {
                // 🔒 Validar bloqueio específico do trimestre
                $this->validarBloqueioFinalizacao($nota, $trimestre);

                if (! $nota->trimestreEstaDisponivel((int) $trimestre)) {
                    throw ValidationException::withMessages([
                        reset($intersect) => $this->mensagemTrimestreNaoAplicavel($nota, (int) $trimestre),
                    ]);
                }

                foreach ($intersect as $campo) {
                    if ($this->campoEstaBloqueado($nota, $campo)) {
                        throw ValidationException::withMessages([
                            $campo => 'Este campo está bloqueado para edição. Reabra apenas este campo na secretaria para voltar a editar.',
                        ]);
                    }
                }
            }
        }
        $nota->fill($validated);
        \App\Observers\NotaObserver::$suprimirLogs = true;
        $this->notaService->recalcularNota($nota);
        \App\Observers\NotaObserver::$suprimirLogs = false;
        $nota->save();
        $this->estadoMatriculaService->sincronizarAlunoNaTurma($nota->turma_id, $nota->aluno_id);

        return back()->with('success', 'Nota atualizada com sucesso.');
    }

    public function salvarClassificacoesEnsinoMedio(Request $request)
    {
        $this->checkPermission('notas.editar');

        $dados = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'classificacoes' => 'required|array',
            'classificacoes.*.aluno_id' => 'required|exists:users,id',
            'classificacoes.*.pap' => 'nullable|numeric|min:0|max:20',
            'classificacoes.*.ecs' => 'nullable|numeric|min:0|max:20',
            'classificacoes.*.observacoes' => 'nullable|string|max:1000',
        ]);

        $turma = Turma::with('alunos')->findOrFail($dados['turma_id']);

        if ((int) $turma->classe !== 13) {
            return back()->with('error', 'A classificação final do ensino médio só se aplica à 13ª classe.');
        }

        $alunosPermitidos = $turma->alunos->pluck('id')->all();

        DB::transaction(function () use ($dados, $turma, $alunosPermitidos) {
            foreach ($dados['classificacoes'] as $item) {
                $alunoId = (int) $item['aluno_id'];

                if (! in_array($alunoId, $alunosPermitidos, true)) {
                    continue;
                }

                $payload = [
                    'pap' => $this->normalizarNotaOpcional($item['pap'] ?? null),
                    'ecs' => $this->normalizarNotaOpcional($item['ecs'] ?? null),
                    'observacoes' => filled($item['observacoes'] ?? null)
                        ? trim((string) $item['observacoes'])
                        : null,
                ];

                $semDados = $payload['pap'] === null
                    && $payload['ecs'] === null
                    && $payload['observacoes'] === null;

                if ($semDados) {
                    ClassificacaoEnsinoMedio::query()
                        ->where('aluno_id', $alunoId)
                        ->where('turma_id', $turma->id)
                        ->where('ano_letivo_id', $turma->ano_letivo_id)
                        ->delete();

                    continue;
                }

                ClassificacaoEnsinoMedio::updateOrCreate(
                    [
                        'aluno_id' => $alunoId,
                        'turma_id' => $turma->id,
                        'ano_letivo_id' => $turma->ano_letivo_id,
                    ],
                    $payload
                );
            }
        });

        return back()->with('success', 'Classificações finais do ensino médio actualizadas com sucesso.');
    }

    // -------------------------------------------------------------------------
    // Operações de pauta
    // -------------------------------------------------------------------------

    public function inicializarPauta(Request $request)
    {
        $user = auth()->user();

        $user->isProfessor()
            ? $this->checkPermission('notas.lancar')
            : $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turma = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        if ($user->isProfessor()) {
            $temAtribuicao = $user->atribuicoes()
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->exists();

            if (! $temAtribuicao) {
                abort(403, 'Voce nao tem permissao para inicializar esta pauta.');
            }
        }

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $criados = $this->notaService->criarNotasParaTurma($turma, $disciplina);

        return redirect()
            ->route('notas.index', [
                'turma_id' => IdMask::encode((int) $turma->id),
                'disciplina_id' => IdMask::encode((int) $disciplina->id),
            ])
            ->with('success', "Pauta inicializada. {$criados} registros criados.");
    }

    public function importarCAs(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $turma = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        $resultado = $this->notaService->importarCAsParaTurma(
            $turma,
            $disciplina,
            auth()->user()->can('notas.reabrir')
        );

        return $this->redirectParaPauta($turma->id, $disciplina->id)
            ->with('success', $resultado['mensagem']);
    }

    public function finalizar(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id' => 'nullable|exists:turmas,id',
            'disciplina_id' => 'nullable|exists:disciplinas,id',
            'motivo' => 'nullable|string|max:500',
            'trimestre' => 'nullable|in:1,2,3',
            'campo' => 'nullable|in:pp,pt,pg',
            'aluno_id' => 'nullable|exists:users,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turmaId = $validated['turma_id'] ?? null;
        $disciplinaId = $validated['disciplina_id'] ?? null;
        $turma = $turmaId ? Turma::findOrFail($turmaId) : null;

        if ($turma && $turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        if ($disciplinaId && ! $turma) {
            return back()->with('error', 'Selecione uma turma para operar uma disciplina especifica.');
        }

        if ($disciplinaId && $turma && ! $turma->disciplinas()->where('disciplinas.id', $disciplinaId)->exists()) {
            return back()->with('error', 'A disciplina selecionada nao pertence a turma informada.');
        }

        $alunoId = $validated['aluno_id'] ?? null;
        $trimestre = $validated['trimestre'] ?? null;
        $campoOperacao = $validated['campo'] ?? null;
        $campoConfig = $this->resolverCampoOperacao($trimestre, $campoOperacao);

        if ($campoOperacao && ! $campoConfig) {
            return back()->with('error', 'Campo selecionado não é válido para o trimestre informado.');
        }

        $notas = $this->buscarNotasDaPauta($anoLetivo, $turmaId, $disciplinaId, $alunoId);

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para finalizar neste ano letivo.');
        }

        // DEPOIS
        $registrarLogPorNota = $alunoId !== null;

        [$finalizadas, $jaFinalizadas] = DB::transaction(function () use ($notas, $trimestre, $campoOperacao, $campoConfig, $registrarLogPorNota) {
            $finalizadas = 0;
            $jaFinalizadas = 0;
            $notaReferencia = null;

            \App\Observers\NotaObserver::$suprimirLogs = true;

            try {
                foreach ($notas as $nota) {
                    if ($campoOperacao && $campoConfig) {
                        $campoBloqueio = $campoConfig['bloqueio'];

                        if ((bool) $nota->{$campoBloqueio}) {
                            $jaFinalizadas++;

                            continue;
                        }

                        $nota->update([$campoBloqueio => true]);
                    } elseif ($trimestre) {
                        $campo = "bloqueado_t{$trimestre}";

                        if ($nota->{$campo}) {
                            $jaFinalizadas++;

                            continue;
                        }

                        $dadosBloqueio = [$campo => true];
                        foreach (self::CAMPOS_EDITAVEIS_POR_TRIMESTRE[$trimestre] as $campoEditavel) {
                            $dadosBloqueio[self::BLOQUEIO_POR_CAMPO[$campoEditavel]] = true;
                        }

                        $nota->update($dadosBloqueio);
                    } else {
                        if ($nota->status === 'finalizado'
                            && $nota->bloqueado_t1
                            && $nota->bloqueado_t2
                            && $nota->bloqueado_t3
                            && $nota->bloqueado_pp1
                            && $nota->bloqueado_pt1
                            && $nota->bloqueado_pp2
                            && $nota->bloqueado_pt2
                            && $nota->bloqueado_pp3
                            && $nota->bloqueado_pg
                        ) {
                            $jaFinalizadas++;

                            continue;
                        }

                        $nota->update([
                            'status' => 'finalizado',
                            'bloqueado_t1' => true,
                            'bloqueado_t2' => true,
                            'bloqueado_t3' => true,
                            'bloqueado_pp1' => true,
                            'bloqueado_pt1' => true,
                            'bloqueado_pp2' => true,
                            'bloqueado_pt2' => true,
                            'bloqueado_pp3' => true,
                            'bloqueado_pg' => true,
                        ]);
                    }

                    // Um único log por nota — evento de finalização
                    $notaReferencia ??= $nota;

                    if ($registrarLogPorNota) {
                        $this->registrarLogOperacaoPauta($nota, 'finalizacao', $trimestre, false, $campoConfig['bloqueio'] ?? null);
                    }

                    $finalizadas++;
                }
            } finally {
                \App\Observers\NotaObserver::$suprimirLogs = false;
            }

            if (! $registrarLogPorNota && $notaReferencia) {
                $this->registrarLogOperacaoPauta($notaReferencia, 'finalizacao', $trimestre, true, $campoConfig['bloqueio'] ?? null);
            }

            return [$finalizadas, $jaFinalizadas];
        });

        $escopoAluno = $alunoId ? ' para o aluno selecionado' : '';
        $labelCampo = $campoOperacao ? strtoupper($campoOperacao) : null;
        $this->sincronizarEstadoMatriculaAposOperacao($notas, $alunoId);
        $escopoOperacao = $turmaId ? ' da turma selecionada' : ' de todas as turmas do ano letivo';

        $this->notificarProfessoresSobreFinalizacaoEmLote(
            $notas,
            $validated['trimestre'] ?? null,
            $validated['campo'] ?? null,
            $validated['motivo'] ?? null,
            $validated['aluno_id'] ?? null,
            $finalizadas
        );

        return $this->redirectParaPauta($turmaId, $disciplinaId)->with('success', $labelCampo && $trimestre
            ? "Bloqueio de {$labelCampo} no {$trimestre}o trimestre{$escopoAluno}{$escopoOperacao} concluido: {$finalizadas} notas bloqueadas e {$jaFinalizadas} ja estavam bloqueadas."
            : ($trimestre
            ? "Bloqueio do {$trimestre}o trimestre{$escopoAluno}{$escopoOperacao} concluido: {$finalizadas} notas bloqueadas e {$jaFinalizadas} ja estavam bloqueadas."
            : "Finalizacao geral{$escopoAluno}{$escopoOperacao} concluida: {$finalizadas} notas finalizadas e {$jaFinalizadas} ja estavam finalizadas."));
    }

    public function reabrir(Request $request)
    {
        $this->checkPermission('notas.reabrir');

        $validated = $request->validate([
            'turma_id' => 'nullable|exists:turmas,id',
            'disciplina_id' => 'nullable|exists:disciplinas,id',
            'motivo' => 'nullable|string|max:500',
            'trimestre' => 'nullable|in:1,2,3',
            'campo' => 'nullable|in:pp,pt,pg',
            'aluno_id' => 'nullable|exists:users,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turmaId = $validated['turma_id'] ?? null;
        $disciplinaId = $validated['disciplina_id'] ?? null;
        $turma = $turmaId ? Turma::findOrFail($turmaId) : null;
        $disciplina = $disciplinaId ? Disciplina::findOrFail($disciplinaId) : null;

        if ($turma && $turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        if ($disciplinaId && ! $turma) {
            return back()->with('error', 'Selecione uma turma para operar uma disciplina especifica.');
        }

        if ($disciplinaId && $turma && ! $turma->disciplinas()->where('disciplinas.id', $disciplinaId)->exists()) {
            return back()->with('error', 'A disciplina selecionada nao pertence a turma informada.');
        }

        $alunoId = $validated['aluno_id'] ?? null;
        $trimestre = $validated['trimestre'] ?? null;
        $campoOperacao = $validated['campo'] ?? null;
        $campoConfig = $this->resolverCampoOperacao($trimestre, $campoOperacao);

        if ($campoOperacao && ! $campoConfig) {
            return back()->with('error', 'Campo selecionado não é válido para o trimestre informado.');
        }

        $notas = $this->buscarNotasDaPauta($anoLetivo, $turmaId, $disciplinaId, $alunoId);

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para reabrir neste ano letivo.');
        }

        $registrarLogPorNota = $alunoId !== null;

        [$reabertas, $jaAbertas] = DB::transaction(function () use ($notas, $trimestre, $campoOperacao, $campoConfig, $registrarLogPorNota) {
            $reabertas = 0;
            $jaAbertas = 0;
            $notaReferencia = null;

            \App\Observers\NotaObserver::$suprimirLogs = true;

            try {
                foreach ($notas as $nota) {
                    if ($campoOperacao && $campoConfig) {
                        $campoBloqueio = $campoConfig['bloqueio'];
                        $precisaDesbloquear = (bool) $nota->{$campoBloqueio};
                        $precisaReabrirStatus = $nota->status === 'finalizado';

                        if (! $precisaDesbloquear && ! $precisaReabrirStatus) {
                            $jaAbertas++;

                            continue;
                        }

                        $dadosAtualizacao = [];

                        if ($precisaDesbloquear) {
                            $dadosAtualizacao[$campoBloqueio] = false;
                        }

                        if ($precisaReabrirStatus) {
                            $dadosAtualizacao['status'] = 'em_lancamento';
                        }

                        $nota->update($dadosAtualizacao);
                    } elseif ($trimestre) {
                        $campo = "bloqueado_t{$trimestre}";
                        $precisaDesbloquear = (bool) $nota->{$campo};
                        $precisaReabrirStatus = $nota->status === 'finalizado';

                        if (! $precisaDesbloquear && ! $precisaReabrirStatus) {
                            $jaAbertas++;

                            continue;
                        }

                        $dadosAtualizacao = [];

                        if ($precisaDesbloquear) {
                            $dadosAtualizacao[$campo] = false;
                            foreach (self::CAMPOS_EDITAVEIS_POR_TRIMESTRE[$trimestre] as $campoEditavel) {
                                $dadosAtualizacao[self::BLOQUEIO_POR_CAMPO[$campoEditavel]] = false;
                            }
                        }

                        if ($precisaReabrirStatus) {
                            $dadosAtualizacao['status'] = 'em_lancamento';
                        }

                        $nota->update($dadosAtualizacao);
                    } else {
                        $jaEstaAberto = $nota->status !== 'finalizado'
                            && ! $nota->bloqueado_t1
                            && ! $nota->bloqueado_t2
                            && ! $nota->bloqueado_t3
                            && ! $nota->bloqueado_pp1
                            && ! $nota->bloqueado_pt1
                            && ! $nota->bloqueado_pp2
                            && ! $nota->bloqueado_pt2
                            && ! $nota->bloqueado_pp3
                            && ! $nota->bloqueado_pg;

                        if ($jaEstaAberto) {
                            $jaAbertas++;

                            continue;
                        }

                        $nota->update([
                            'status' => 'em_lancamento',
                            'bloqueado_t1' => false,
                            'bloqueado_t2' => false,
                            'bloqueado_t3' => false,
                            'bloqueado_pp1' => false,
                            'bloqueado_pt1' => false,
                            'bloqueado_pp2' => false,
                            'bloqueado_pt2' => false,
                            'bloqueado_pp3' => false,
                            'bloqueado_pg' => false,
                        ]);
                    }

                    // Um único log por nota — evento de reabertura
                    $notaReferencia ??= $nota;

                    if ($registrarLogPorNota) {
                        $this->registrarLogOperacaoPauta($nota, 'reabertura', $trimestre, false, $campoConfig['bloqueio'] ?? null);
                    }

                    $reabertas++;
                }
            } finally {
                \App\Observers\NotaObserver::$suprimirLogs = false;
            }

            if (! $registrarLogPorNota && $notaReferencia) {
                $this->registrarLogOperacaoPauta($notaReferencia, 'reabertura', $trimestre, true, $campoConfig['bloqueio'] ?? null);
            }

            return [$reabertas, $jaAbertas];
        });

        $escopoAluno = $alunoId ? ' para o aluno selecionado' : '';
        $this->notificarProfessoresSobreReaberturaEmLote(
            $notas,
            $validated['trimestre'] ?? null,
            $validated['campo'] ?? null,
            $validated['motivo'] ?? null,
            $validated['aluno_id'] ?? null,
            $reabertas
        );

        $labelCampo = $campoOperacao ? strtoupper($campoOperacao) : null;
        $this->sincronizarEstadoMatriculaAposOperacao($notas, $alunoId);
        $escopoOperacao = $turmaId ? ' da turma selecionada' : ' de todas as turmas do ano letivo';

        return $this->redirectParaPauta($turmaId, $disciplinaId)->with('success', $labelCampo && $trimestre
            ? "Reabertura de {$labelCampo} no {$trimestre}o trimestre{$escopoAluno}{$escopoOperacao} concluida: {$reabertas} notas desbloqueadas e {$jaAbertas} ja estavam desbloqueadas."
            : ($trimestre
            ? "Reabertura do {$trimestre}o trimestre{$escopoAluno}{$escopoOperacao} concluida: {$reabertas} notas desbloqueadas e {$jaAbertas} ja estavam desbloqueadas."
            : "Reabertura geral{$escopoAluno}{$escopoOperacao} concluida: {$reabertas} notas reabertas e {$jaAbertas} ja estavam em lancamento."));
    }


    public function marcarNotificacaoComoLida(string $notificationId): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->isProfessor()) {
            abort(403);
        }

        
        $notificacao = $user->notifications()
            ->where('id', $notificationId)
            ->whereIn('type', $this->tiposNotificacaoPauta())
            ->first();

        if ($notificacao instanceof DatabaseNotification) {
            $notificacao->markAsRead();
        }

        return back();
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------
    private function redirectParaPauta(?int $turmaId = null, ?int $disciplinaId = null): RedirectResponse
    {
        $params = [];

        if ($turmaId) {
            $params['turma_id'] = IdMask::encode($turmaId);
        }

        if ($disciplinaId) {
            $params['disciplina_id'] = IdMask::encode($disciplinaId);
        }

        return redirect()->route('notas.index', $params);
    }
    
    private function verificarPermissaoProfessor(Nota $nota): void
    {
        $temAtribuicao = auth()->user()->atribuicoes()
            ->where('turma_id', $nota->turma_id)
            ->where('disciplina_id', $nota->disciplina_id)
            ->where('ano_letivo_id', $nota->ano_letivo_id)
            ->exists();

        if (! $temAtribuicao) {
            abort(403, 'Voce nao tem permissao para editar esta nota.');
        }
    }

    private function notificarProfessoresSobreReabertura(
        Turma $turma,
        Disciplina $disciplina,
        ?string $trimestre,
        ?string $campo,
        ?string $motivo,
        ?int $alunoId,
        int $totalReabertas
    ): void {
        if ($totalReabertas <= 0 || $alunoId !== null) {
            return;
        }

        $autor = auth()->user();

        if (! $autor->isSecretaria() && ! $autor->isAdmin()) {
            return;
        }

        $professores = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'professor'))
            ->whereHas('atribuicoes', fn ($query) => $query
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id))
            ->where('ativo', true)
            ->get();

        if ($professores->isEmpty()) {
            return;
        }

        foreach ($professores as $professor) {
            $professor->notify(new PautaDesbloqueadaNotification(
                turma: $turma,
                disciplina: $disciplina,
                autor: $autor,
                trimestre: $trimestre,
                campo: $campo,
                motivo: $motivo
            ));
        }
    }


    private function notificarProfessoresSobreReaberturaEmLote(
        \Illuminate\Support\Collection $notas,
        ?string $trimestre,
        ?string $campo,
        ?string $motivo,
        ?int $alunoId,
        int $totalReabertas
    ): void {
        if ($totalReabertas <= 0 || $alunoId !== null) {
            return;
        }

        $pares = $this->paresTurmaDisciplinaDasNotas($notas);

        foreach ($pares as $par) {
            $this->notificarProfessoresSobreReabertura(
                $par['turma'],
                $par['disciplina'],
                $trimestre,
                $campo,
                $motivo,
                $alunoId,
                $totalReabertas
            );
        }
    }


    private function notificarProfessoresSobreFinalizacao(
        Turma $turma,
        Disciplina $disciplina,
        ?string $trimestre,
        ?string $campo,
        ?string $motivo,
        ?int $alunoId,
        int $totalFinalizadas
    ): void {
        if ($totalFinalizadas <= 0 || $alunoId !== null) {
            return;
        }

        $autor = auth()->user();

        if (! $autor->isSecretaria() && ! $autor->isAdmin()) {
            return;
        }

        $professores = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'professor'))
            ->whereHas('atribuicoes', fn ($query) => $query
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id))
            ->where('ativo', true)
            ->get();

        if ($professores->isEmpty()) {
            return;
        }

        foreach ($professores as $professor) {
            $professor->notify(new PautaFinalizadaNotification(
                turma: $turma,
                disciplina: $disciplina,
                autor: $autor,
                trimestre: $trimestre,
                campo: $campo,
                motivo: $motivo
            ));
        }
    }

    
    private function notificarProfessoresSobreFinalizacaoEmLote(
        \Illuminate\Support\Collection $notas,
        ?string $trimestre,
        ?string $campo,
        ?string $motivo,
        ?int $alunoId,
        int $totalFinalizadas
    ): void {
        if ($totalFinalizadas <= 0 || $alunoId !== null) {
            return;
        }

        $pares = $this->paresTurmaDisciplinaDasNotas($notas);

        foreach ($pares as $par) {
            $this->notificarProfessoresSobreFinalizacao(
                $par['turma'],
                $par['disciplina'],
                $trimestre,
                $campo,
                $motivo,
                $alunoId,
                $totalFinalizadas
            );
        }
    }

    private function paresTurmaDisciplinaDasNotas(\Illuminate\Support\Collection $notas): array
    {
        $paresIds = $notas
            ->map(fn (Nota $nota) => [
                'turma_id' => $nota->turma_id,
                'disciplina_id' => $nota->disciplina_id,
            ])
            ->unique(fn (array $par) => $par['turma_id'].'-'.$par['disciplina_id'])
            ->values();

        if ($paresIds->isEmpty()) {
            return [];
        }

        $turmas = Turma::query()
            ->whereIn('id', $paresIds->pluck('turma_id')->all())
            ->get()
            ->keyBy('id');

        $disciplinas = Disciplina::query()
            ->whereIn('id', $paresIds->pluck('disciplina_id')->all())
            ->get()
            ->keyBy('id');

        return $paresIds
            ->map(function (array $par) use ($turmas, $disciplinas) {
                $turma = $turmas->get($par['turma_id']);
                $disciplina = $disciplinas->get($par['disciplina_id']);

                if (! $turma || ! $disciplina) {
                    return null;
                }

                return [
                    'turma' => $turma,
                    'disciplina' => $disciplina,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }


    private function tiposNotificacaoPauta(): array
    {
        return [
            PautaDesbloqueadaNotification::class,
            PautaFinalizadaNotification::class,
        ];
    }

    private function notaBloqueadaParaEdicao(Nota $nota, ?string $trimestre = null): bool
    {
        if (auth()->user()->can('notas.reabrir')) {
            return false;
        }

        if ($trimestre && (($nota->{"bloqueado_t{$trimestre}"} ?? false) === true)) {
            return true;
        }

        return $nota->status === 'finalizado';
    }

    private function campoEstaBloqueado(Nota $nota, string $campo): bool
    {
        $campoBloqueio = self::BLOQUEIO_POR_CAMPO[$campo] ?? null;

        if (! $campoBloqueio) {
            return false;
        }

        return (bool) ($nota->{$campoBloqueio} ?? false);
    }

    private function resolverCampoOperacao(?string $trimestre, ?string $campo): ?array
    {
        if (! $trimestre || ! $campo) {
            return null;
        }

        $campoNota = self::CAMPO_OPERACAO_POR_TRIMESTRE[$trimestre][$campo] ?? null;

        if (! $campoNota) {
            return null;
        }

        return [
            'campo' => $campoNota,
            'bloqueio' => self::BLOQUEIO_POR_CAMPO[$campoNota],
        ];
    }

    private function validarBloqueioFinalizacao(Nota $nota, ?string $trimestre = null): void
    {
        if (! $this->notaBloqueadaParaEdicao($nota, $trimestre)) {
            return;
        }

        if ($trimestre && (($nota->{"bloqueado_t{$trimestre}"} ?? false) === true)) {
            abort(403, "Este {$trimestre}o trimestre esta finalizado e bloqueado para edicao.");
        }

        abort(403, 'Esta nota ja foi finalizada e esta bloqueada para edicao.');
    }

    private function registrarLogOperacaoPauta(
        Nota $nota,
        string $acao,
        ?string $trimestre = null,
        bool $acaoGlobal = false,
        ?string $campoBloqueio = null
    ): void {
        $campoAlterado = $campoBloqueio ?? ($trimestre ? "bloqueado_t{$trimestre}" : 'pauta_completa');

        NotaLog::create([
            'nota_id' => $nota->id,
            'usuario_id' => auth()->id(),
            'aluno_id' => $nota->aluno_id,
            'turma_id' => $nota->turma_id,
            'disciplina_id' => $nota->disciplina_id,
            'acao_global' => $acaoGlobal,
            'acao' => $acao,
            'campo_alterado' => $campoAlterado,
            'valor_anterior' => $acao === 'finalizacao' ? 'em_lancamento' : 'finalizado',
            'valor_novo' => $acao === 'finalizacao' ? 'finalizado' : 'em_lancamento',
            'trimestre' => $trimestre,
            'motivo' => request()?->input('motivo'),
            'ip_address' => request()?->ip(),
            'data_alteracao' => now(),
        ]);
    }

    private function redirectSemAnoLetivoAtivo(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->can('anos.create')) {
            return redirect()
                ->route('anos-letivos.index')
                ->with('error', 'Nenhum ano letivo ativo encontrado. Defina um ano letivo ativo para continuar.');
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Nenhum ano letivo ativo encontrado. Entre em contacto com a administracao.');
    }

    private function buscarNotasDaPauta(AnoLetivo $anoLetivo, ?int $turmaId = null, ?int $disciplinaId = null, ?int $alunoId = null)
    {
        return Nota::query()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->when($turmaId, fn ($query, $id) => $query->where('turma_id', $id))
            ->when($disciplinaId, fn ($query, $id) => $query->where('disciplina_id', $id))
            ->when($alunoId, fn ($query, $id) => $query->where('aluno_id', $id))
            ->get();
    }

    private function sincronizarEstadoMatriculaAposOperacao($notas, ?int $alunoId = null): void
    {
        $turmaIds = $notas->pluck('turma_id')->filter()->unique()->values();

        foreach ($turmaIds as $turmaId) {
            if ($alunoId) {
                $this->estadoMatriculaService->sincronizarAlunoNaTurma((int) $turmaId, (int) $alunoId);
            } else {
                $this->estadoMatriculaService->sincronizarTurma((int) $turmaId);
            }
        }
    }

    private function mensagemTrimestreNaoAplicavel(Nota $nota, int $trimestre): string
    {
        $trimestreInicial = $nota->trimestreInicialDisponivel();

        if ($trimestre === 1 && $trimestreInicial === 2) {
            return 'O 1º trimestre nao se aplica a este aluno porque a matricula ocorreu a partir do 2º trimestre.';
        }

        return "O {$trimestre}o trimestre nao se aplica a este aluno porque a matricula ocorreu apenas no {$trimestreInicial}o trimestre.";
    }

    public function solicitarDivisaoPorDois(Request $request, Nota $nota)
    {
        $user = auth()->user();
        $this->authorize('update', $nota);
        $this->checkPermission('notas.lancar');

        $coordenadorId = $nota->turma?->curso?->coordenador_id;
        if (! $coordenadorId) {
            return back()->with('error', 'A turma não possui coordenador de curso definido.');
        }

        $jaPendente = DivisaoAritmeticaSolicitacao::query()
            ->where('nota_id', $nota->id)
            ->where('status', 'pendente')
            ->exists();

        if ($jaPendente) {
            return back()->with('info', 'Já existe uma solicitação pendente para este aluno.');
        }

        $data = $request->validate([
            'mensagem' => 'nullable|string|max:500',
        ]);

        DivisaoAritmeticaSolicitacao::create([
            'nota_id' => $nota->id,
            'professor_id' => $user->id,
            'coordenador_id' => $coordenadorId,
            'mensagem' => $data['mensagem'] ?? null,
            'status' => 'pendente',
        ]);

        return back()->with('success', 'Solicitação enviada ao coordenador do curso.');
    }

    public function responderSolicitacaoDivisao(Request $request, DivisaoAritmeticaSolicitacao $solicitacao)
    {
        $user = auth()->user();
        if (! $user->isCoordenadorCurso() || $solicitacao->coordenador_id !== $user->id) {
            abort(403, 'Apenas o coordenador de curso pode responder esta solicitação.');
        }

        $dados = $request->validate([
            'acao' => 'required|in:aprovar,rejeitar',
            'resposta' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($solicitacao, $dados, $user) {
            $solicitacao->update([
                'status' => $dados['acao'] === 'aprovar' ? 'aprovada' : 'rejeitada',
                'respondida_em' => now(),
                'respondida_por' => $user->id,
                'resposta' => $dados['resposta'] ?? null,
            ]);

            if ($dados['acao'] === 'aprovar') {
                $nota = $solicitacao->nota()->with(['turma', 'disciplina'])->firstOrFail();
                $nota->usar_divisao_aritmetica_por_2 = true;
                $this->notaService->recalcularNota($nota);
                $nota->save();
            }
        });

        return back()->with('success', 'Solicitação processada com sucesso.');
    }

    public function adicionarAvaliacaoContinua(Request $request)
    {
        $user = auth()->user();
        $podeComoProfessor = $user->isProfessor()
            && ($user->hasPermission('avaliacoes_continuas.create') || $user->hasPermission('notas.lancar'));

        $podeComoAdmin = ($user->isAdmin() || $user->isSecretaria())
            && $user->hasPermission('avaliacoes_continuas.create');

        if (! $podeComoProfessor && ! $podeComoAdmin) {
            abort(403, 'Sem permissão para lançar avaliações contínuas.');
        }

        $dados = $request->validate([
            'nota_id' => 'required|exists:notas,id',
            'trimestre' => 'required|in:1,2,3',
            'descricao' => 'nullable|string|max:120',
            'valor' => 'required|numeric|min:0|max:20',
            'data_avaliacao' => 'nullable|date',
        ]);

        $nota = Nota::with(['turma', 'disciplina', 'aluno.turmas', 'anoLetivo'])->findOrFail($dados['nota_id']);

        if ($podeComoProfessor) {
            $temAtribuicao = $user->atribuicoes()
                ->where('turma_id', $nota->turma_id)
                ->where('disciplina_id', $nota->disciplina_id)
                ->where('ano_letivo_id', $nota->ano_letivo_id)
                ->exists();

            if (! $temAtribuicao) {
                abort(403, 'Você não leciona esta disciplina nesta turma.');
            }
        }

        $trimestre = (int) $dados['trimestre'];

        $trimestreCorrente = $this->resolverTrimestreCorrente($nota->anoLetivo);

        if ($trimestre !== $trimestreCorrente) {
            return back()->with('error', "Só é permitido lançar avaliações contínuas no {$trimestreCorrente}º trimestre corrente.");
        }

        if (! $nota->trimestreEstaDisponivel($trimestre)) {
            return back()->with('error', $this->mensagemTrimestreNaoAplicavel($nota, $trimestre));
        }

        if ((bool) $nota->{"bloqueado_t{$trimestre}"}) {
            return back()->with('error', "O {$trimestre}º trimestre está bloqueado para este aluno.");
        }

        $dataAvaliacao = $this->normalizarDataAvaliacao($dados['data_avaliacao'] ?? null);

        AvaliacaoContinua::create([
            'nota_id' => $nota->id,
            'professor_id' => $user->id,
            'trimestre' => $trimestre,
            'descricao' => trim((string) ($dados['descricao'] ?? '')) ?: 'Avaliação contínua',
            'valor' => round((float) $dados['valor'], 2),
            'data_avaliacao' => $dataAvaliacao,
        ]);

        $this->registarLogAvaliacaoContinua($nota, 'criacao', $trimestre, null, [
            'descricao' => trim((string) ($dados['descricao'] ?? '')) ?: 'Avaliação contínua',
            'valor' => round((float) $dados['valor'], 2),
            'data_avaliacao' => $dataAvaliacao?->toDateString(),
        ]);

        $this->recalcularMacPorAvaliacoes($nota, $trimestre);

        return back()->with('success', 'Avaliação contínua registada com sucesso.');
    }

    public function adicionarAvaliacoesContinuasEmLote(Request $request)
    {
        $user = auth()->user();
        $podeComoProfessor = $user->isProfessor()
            && ($user->hasPermission('avaliacoes_continuas.create') || $user->hasPermission('notas.lancar'));

        $podeComoAdmin = ($user->isAdmin() || $user->isSecretaria())
            && $user->hasPermission('avaliacoes_continuas.create');

        if (! $podeComoProfessor && ! $podeComoAdmin) {
            abort(403, 'Sem permissão para lançar avaliações contínuas.');
        }

        $dados = $request->validate([
            'trimestre' => 'required|in:1,2,3',
            'descricao' => 'nullable|string|max:120',
            'data_avaliacao' => 'nullable|date',
            'avaliacoes' => 'required|array|min:1',
            'avaliacoes.*.nota_id' => 'required|exists:notas,id',
            'avaliacoes.*.valor' => 'nullable|numeric|min:0|max:20',
        ]);

        $trimestre = (int) $dados['trimestre'];
        $descricao = trim((string) ($dados['descricao'] ?? '')) ?: 'Avaliação contínua';
        $dataAvaliacao = $this->normalizarDataAvaliacao($dados['data_avaliacao'] ?? null);
        $anoLetivo = AnoLetivo::ativo()->first();
        $trimestreCorrente = $this->resolverTrimestreCorrente($anoLetivo);

        if ($trimestre !== $trimestreCorrente) {
            return back()->with('error', "Só é permitido lançar avaliações contínuas no {$trimestreCorrente}º trimestre corrente.");
        }

        $notaIds = collect($dados['avaliacoes'])
            ->pluck('nota_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $notas = Nota::query()
            ->with(['turma', 'disciplina', 'aluno.turmas', 'anoLetivo'])
            ->whereIn('id', $notaIds)
            ->get()
            ->keyBy('id');

        $registadas = 0;
        $ignoradas = 0;
        $erros = 0;

        DB::transaction(function () use (
            $dados,
            $notas,
            $user,
            $podeComoProfessor,
            $trimestre,
            $descricao,
            $dataAvaliacao,
            &$registadas,
            &$ignoradas,
            &$erros
        ) {
            foreach ($dados['avaliacoes'] as $item) {
                $valor = $item['valor'] ?? null;

                if ($valor === null || $valor === '') {
                    $ignoradas++;
                    continue;
                }

                $nota = $notas->get((int) $item['nota_id']);

                if (! $nota) {
                    $erros++;
                    continue;
                }

                if ($podeComoProfessor) {
                    $temAtribuicao = $user->atribuicoes()
                        ->where('turma_id', $nota->turma_id)
                        ->where('disciplina_id', $nota->disciplina_id)
                        ->where('ano_letivo_id', $nota->ano_letivo_id)
                        ->exists();

                    if (! $temAtribuicao) {
                        $erros++;
                        continue;
                    }
                }

                if (! $nota->trimestreEstaDisponivel($trimestre) || (bool) $nota->{"bloqueado_t{$trimestre}"}) {
                    $ignoradas++;
                    continue;
                }

                $valorFinal = round((float) $valor, 2);

                AvaliacaoContinua::create([
                    'nota_id' => $nota->id,
                    'professor_id' => $user->id,
                    'trimestre' => $trimestre,
                    'descricao' => $descricao,
                    'valor' => $valorFinal,
                    'data_avaliacao' => $dataAvaliacao,
                ]);

                $this->registarLogAvaliacaoContinua($nota, 'criacao', $trimestre, null, [
                    'descricao' => $descricao,
                    'valor' => $valorFinal,
                    'data_avaliacao' => $dataAvaliacao?->toDateString(),
                ]);

                $this->recalcularMacPorAvaliacoes($nota, $trimestre);
                $registadas++;
            }
        });

        if ($registadas === 0 && $erros > 0) {
            return back()->with('error', 'Não foi possível lançar as avaliações em lote.');
        }

        return back()->with(
            'success',
            "Lançamento em lote concluído: {$registadas} avaliação(ões) registada(s), {$ignoradas} ignorada(s) e {$erros} com erro."
        );
    }

    public function removerAvaliacaoContinua(AvaliacaoContinua $avaliacao)
    {
        $user = auth()->user();
        $this->checkPermission('avaliacoes_continuas.delete');

        if (! ($user->isAdmin() || $user->isSecretaria())) {
            abort(403, 'Apenas administração/secretaria pode remover avaliações contínuas após o lançamento.');
        }

        $nota = $avaliacao->nota()->with(['turma', 'disciplina', 'aluno.turmas', 'anoLetivo'])->firstOrFail();

        $trimestre = (int) $avaliacao->trimestre;

        if ((bool) $nota->{"bloqueado_t{$trimestre}"}) {
            return back()->with('error', "O {$trimestre}º trimestre está bloqueado para este aluno.");
        }

        $dadosAnteriores = [
            'descricao' => $avaliacao->descricao,
            'valor' => $avaliacao->valor,
            'professor_id' => $avaliacao->professor_id,
            'data_avaliacao' => optional($avaliacao->data_avaliacao)?->toDateString(),
        ];

        $avaliacao->delete();

        $this->registarLogAvaliacaoContinua(
            $nota,
            'exclusao',
            $trimestre,
            $dadosAnteriores,
            null
        );

        $this->recalcularMacPorAvaliacoes($nota, $trimestre);

        return back()->with('success', 'Avaliação contínua removida com sucesso.');
    }

    public function atualizarAvaliacaoContinua(Request $request, AvaliacaoContinua $avaliacao)
    {
        $user = auth()->user();
        $this->checkPermission('avaliacoes_continuas.edit');

        if (! ($user->isAdmin() || $user->isSecretaria())) {
            abort(403, 'Apenas administração/secretaria pode editar avaliações contínuas.');
        }

        $dados = $request->validate([
            'descricao' => 'nullable|string|max:120',
            'valor' => 'required|numeric|min:0|max:20',
            'data_avaliacao' => 'nullable|date',
        ]);

        $nota = $avaliacao->nota()->with(['turma', 'disciplina', 'aluno.turmas', 'anoLetivo'])->firstOrFail();
        $trimestre = (int) $avaliacao->trimestre;

        if ((bool) $nota->{"bloqueado_t{$trimestre}"}) {
            return back()->with('error', "O {$trimestre}º trimestre está bloqueado para este aluno.");
        }

        $dadosAnteriores = $avaliacao->only(['descricao', 'valor', 'data_avaliacao']);
        $dataAvaliacao = $this->normalizarDataAvaliacao($dados['data_avaliacao'] ?? null, $avaliacao->data_avaliacao);

        $avaliacao->update([
            'descricao' => trim((string) ($dados['descricao'] ?? '')) ?: 'Avaliação contínua',
            'valor' => round((float) $dados['valor'], 2),
            'data_avaliacao' => $dataAvaliacao,
        ]);

        $this->registarLogAvaliacaoContinua(
            $nota,
            'edicao',
            $trimestre,
            $dadosAnteriores,
            array_merge(
                $avaliacao->only(['descricao', 'valor']),
                ['data_avaliacao' => $dataAvaliacao?->toDateString()]
            )
        );

        $this->recalcularMacPorAvaliacoes($nota, $trimestre);

        return back()->with('success', 'Avaliação contínua atualizada com sucesso.');
    }

    private function recalcularMacPorAvaliacoes(Nota $nota, int $trimestre): void
    {
        $campoMac = "mac{$trimestre}";

        $media = $nota->avaliacoesContinuas()
            ->where('trimestre', $trimestre)
            ->avg('valor');

        $nota->{$campoMac} = $media !== null ? round((float) $media, 2) : null;

        $this->notaService->recalcularNota($nota);
        $nota->save();
    }

    private function registarLogAvaliacaoContinua(
        Nota $nota,
        string $acao,
        int $trimestre,
        mixed $anterior = null,
        mixed $novo = null
    ): void {
        NotaLog::create([
            'nota_id' => $nota->id,
            'usuario_id' => auth()->id(),
            'aluno_id' => $nota->aluno_id,
            'turma_id' => $nota->turma_id,
            'disciplina_id' => $nota->disciplina_id,
            'acao_global' => false,
            'acao' => $acao,
            'campo_alterado' => 'avaliacao_continua',
            'valor_anterior' => $anterior ? json_encode($anterior, JSON_UNESCAPED_UNICODE) : null,
            'valor_novo' => $novo ? json_encode($novo, JSON_UNESCAPED_UNICODE) : null,
            'trimestre' => (string) $trimestre,
            'motivo' => null,
            'ip_address' => request()?->ip(),
            'data_alteracao' => now(),
        ]);
    }

    private function normalizarDataAvaliacao(mixed $novaData, mixed $fallback = null): \Illuminate\Support\Carbon
    {
        if ($novaData) {
            return \Illuminate\Support\Carbon::parse($novaData);
        }

        if ($fallback) {
            return \Illuminate\Support\Carbon::parse($fallback);
        }

        return now();
    }

    private function normalizarNotaOpcional(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return round((float) $valor, 2);
    }

    private function resolverTrimestreCorrente(?AnoLetivo $anoLetivo): int
    {
        return $anoLetivo?->trimestreNaData() ?? 1;
    }
    
    private function resolverCamposCaSomenteLeitura(Nota $nota): array
    {
        $classeAtual = (int) $nota->turma->classe;

        return [
            'ca_10' => $classeAtual >= 11 && $this->alunoTemTurmaDaClasseAnterior($nota, 10),
            'ca_11' => $classeAtual >= 12 && $this->alunoTemTurmaDaClasseAnterior($nota, 11),
            'ca_12' => $classeAtual >= 13 && $this->alunoTemTurmaDaClasseAnterior($nota, 12),
        ];
    }

    private function alunoTemTurmaDaClasseAnterior(Nota $nota, int $classeAnterior): bool
    {
        return $nota->aluno
            ->turmas()
            ->where('classe', (string) $classeAnterior)
            ->where('ano_letivo_id', '<', $nota->ano_letivo_id)
            ->exists();
    }

}
