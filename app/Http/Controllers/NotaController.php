<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Turma;
use App\Services\NotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaController extends Controller
{
    // Mapeamento dos campos por trimestre — única fonte de verdade
    private const CAMPOS_TRIMESTRE = [
        '1' => ['mac1', 'pp1', 'pt1'],
        '2' => ['mac2', 'pp2', 'pt2'],
        '3' => ['mac3', 'pp3', 'pg'],
    ];

    public function __construct(private readonly NotaService $notaService)
    {
    }

    // -------------------------------------------------------------------------
    // Index / visualização
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $user = auth()->user();

        return match (true) {
            $user->isProfessor()                        => $this->professorIndex($request),
            $user->isSecretaria() || $user->isAdmin()   => $this->secretariaIndex($request),
            $user->isAluno()                            => $this->alunoIndex(),
            default                                     => abort(403),
        };
    }

    public function professorIndex(Request $request)
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $atribuicoes = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['turma', 'disciplina'])
            ->get();

        $turmaId      = $request->turma_id;
        $disciplinaId = $request->disciplina_id;
        $notas        = null;
        $turma        = null;
        $disciplina   = null;

        if ($turmaId && $disciplinaId) {
            $temAtribuicao = $atribuicoes
                ->where('turma_id', $turmaId)
                ->where('disciplina_id', $disciplinaId)
                ->isNotEmpty();

            if (!$temAtribuicao) {
                return back()->with('error', 'Voce nao leciona esta disciplina nesta turma.');
            }

            $turma      = Turma::findOrFail($turmaId);
            $disciplina = Disciplina::findOrFail($disciplinaId);
            $alunos     = $turma->alunos()->wherePivot('status', 'matriculado')->get();

            $notasPorAluno = Nota::where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with('aluno')
                ->get()
                ->keyBy('aluno_id');

            $notas = $alunos
                ->map(function ($aluno) use ($notasPorAluno) {
                    $nota = $notasPorAluno->get($aluno->id);

                    if ($nota) {
                        $nota->aluno = $aluno;
                    }

                    return $nota;
                })
                ->filter()
                ->values();
        }

        return view('notas.professor', compact('atribuicoes', 'notas', 'turma', 'disciplina'));
    }

    public function secretariaIndex(Request $request)
    {
        $this->checkPermission('notas.view_all');

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turmas      = Turma::anoAtivo()->with('curso')->get();
        $disciplinas = collect();

        $turmaId      = $request->turma_id;
        $disciplinaId = $request->disciplina_id;

        $notas               = null;
        $notasAgrupadas      = null;
        $turmaSelecionada    = null;
        $disciplinaSelecionada = null;

        if ($turmaId) {
            $turmaSelecionada = Turma::findOrFail($turmaId);
            $disciplinas      = $turmaSelecionada->disciplinas()->orderBy('nome')->get();

            $query = Nota::where('turma_id', $turmaSelecionada->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['aluno', 'disciplina']);

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

            if (!$disciplinaSelecionada) {
                $notasAgrupadas = $notas
                    ->groupBy('aluno_id')
                    ->map(fn ($grupo) => [
                        'aluno' => $grupo->first()->aluno,
                        'notas' => $grupo->keyBy('disciplina_id'),
                    ]);
            }
        }

        return view('notas.secretaria', compact(
            'turmas',
            'disciplinas',
            'notas',
            'notasAgrupadas',
            'turmaSelecionada',
            'disciplinaSelecionada'
        ));
    }

    public function alunoIndex()
    {
        $this->checkPermission('notas.view_own');

        $aluno     = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplina', 'turma'])
            ->get();

        $turmaAtual   = $notas->first()?->turma;
        $notasComCfd  = $notas->whereNotNull('cfd');
        $mediaGeral   = $notasComCfd->isNotEmpty()
            ? round($notasComCfd->avg('cfd'), 2)
            : 0;

        $aprovacoes  = $notasComCfd->filter(fn ($nota) => $nota->cfd >= 10)->count();
        $reprovacoes = $notasComCfd->filter(fn ($nota) => $nota->cfd < 10)->count();

        return view('notas.aluno', compact(
            'notas',
            'turmaAtual',
            'mediaGeral',
            'aprovacoes',
            'reprovacoes'
        ));
    }







        private function buildFeedbackLancamento(
        string $trimestre,
        int $salvas,
        int $bloqueadas,
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

        if (!empty($naoEncontradas)) {
            $ids = implode(', ', $naoEncontradas);
            $partes[] = count($naoEncontradas) . " nota(s) não encontrada(s) (IDs: {$ids})";
        }

        if (empty($partes)) {
            return ['warning', "Nenhuma nota do {$trimestre}º trimestre foi processada."];
        }

        $mensagem = "{$trimestre}º trimestre: " . implode('. ', $partes) . '.';

        // success só se houve pelo menos 1 salva e nenhum problema
        if ($salvas > 0 && $bloqueadas === 0 && empty($naoEncontradas)) {
            $tipo = 'success';
        } elseif ($salvas === 0 && $semAlteracao > 0 && $bloqueadas === 0) {
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
        if (!array_key_exists($trimestre, self::CAMPOS_TRIMESTRE)) {
            abort(404, 'Trimestre inválido.');
        }

        $user = auth()->user();

        $user->isProfessor()
            ? $this->checkPermission('notas.lancar')
            : $this->checkPermission('notas.editar');

        $campos = self::CAMPOS_TRIMESTRE[$trimestre];
        $rules  = ['notas' => 'required|array', 'notas.*.id' => 'required|exists:notas,id'];

        foreach ($campos as $campo) {
            $rules["notas.*.{$campo}"] = 'nullable|numeric|min:0|max:20';
        }

        $validated = $request->validate($rules);

        $ids      = collect($validated['notas'])->pluck('id');
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        $salvas         = 0;
        $bloqueadas     = 0;
        $semAlteracao   = 0;
        $naoEncontradas = [];

        DB::transaction(function () use (
            $validated, $notasMap, $campos, $trimestre, $user,
            &$salvas, &$bloqueadas, &$semAlteracao, &$naoEncontradas
        ) {
            foreach ($validated['notas'] as $notaData) {
                $nota = $notasMap->get($notaData['id']);

                if (!$nota) {
                    $naoEncontradas[] = $notaData['id'];
                    continue;
                }

                if ($user->isProfessor()) {
                    $this->verificarPermissaoProfessor($nota);
                }

                if ($this->notaBloqueadaParaEdicao($nota, $trimestre)) {
                    $bloqueadas++;
                    continue;
                }

                // Atribui os valores do formulário
                foreach ($campos as $campo) {
                    $nota->{$campo} = $notaData[$campo] ?? null;
                }

                // Só recalcula e salva se algum campo do trimestre realmente mudou
                if (!$nota->isDirty($campos)) {
                    $semAlteracao++;
                    continue;
                }

                $this->notaService->recalcularNota($nota);
                $nota->save();
                $salvas++;
            }
        });

        [$tipo, $mensagem] = $this->buildFeedbackLancamento(
            $trimestre, $salvas, $bloqueadas, $semAlteracao, $naoEncontradas
        );

        return back()->with($tipo, $mensagem);
    }



    // -------------------------------------------------------------------------
    // Edição individual
    // -------------------------------------------------------------------------

    public function edit(Nota $nota)
    {
        $user = auth()->user();

        $user->isProfessor()
            ? $this->verificarPermissaoProfessor($nota)
            : $this->checkPermission('notas.editar');

        $this->validarBloqueioFinalizacao($nota);
        $nota->load(['aluno', 'turma', 'disciplina']);

        return view('notas.edit', compact('nota'));
    }

    public function update(Request $request, Nota $nota)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
            $this->verificarPermissaoProfessor($nota);
        } else {
            $this->checkPermission('notas.editar');
        }

        $this->validarBloqueioFinalizacao($nota);

        // Todos os campos de todos os trimestres + extras
        $allCampos = array_merge(...array_values(self::CAMPOS_TRIMESTRE));
        $rules     = array_fill_keys(
            array_map(fn ($c) => $c, $allCampos),
            'nullable|numeric|min:0|max:20'
        );
        $rules['ca_10']      = 'nullable|numeric|min:0|max:20';
        $rules['ca_11']      = 'nullable|numeric|min:0|max:20';
        $rules['observacoes'] = 'nullable|string';

        $validated = $request->validate($rules);

        $nota->fill($validated);
        $this->notaService->recalcularNota($nota);
        $nota->save();

        return redirect()
            ->route('notas.index', [
                'turma_id'      => $nota->turma_id,
                'disciplina_id' => $nota->disciplina_id,
            ])
            ->with('success', 'Nota atualizada com sucesso.');
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
            'turma_id'      => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turma      = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        if ($user->isProfessor()) {
            $temAtribuicao = $user->atribuicoes()
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->exists();

            if (!$temAtribuicao) {
                abort(403, 'Voce nao tem permissao para inicializar esta pauta.');
            }
        }

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $criados = $this->notaService->criarNotasParaTurma($turma, $disciplina);

        return redirect()
            ->route('notas.index', [
                'turma_id'      => $turma->id,
                'disciplina_id' => $disciplina->id,
            ])
            ->with('success', "Pauta inicializada. {$criados} registros criados.");
    }

    public function importarCAs(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id'      => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $turma      = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        $resultado = $this->notaService->importarCAsParaTurma(
            $turma,
            $disciplina,
            auth()->user()->can('notas.reabrir')
        );

        return back()->with('success', $resultado['mensagem']);
    }

    public function finalizar(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id'      => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
            'motivo'        => 'nullable|string|max:500',
            'trimestre'     => 'nullable|in:1,2,3',
            'aluno_id'      => 'nullable|exists:users,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turma      = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $alunoId   = $validated['aluno_id'] ?? null;
        $trimestre = $validated['trimestre'] ?? null;
        $notas     = $this->buscarNotasDaPauta($turma, $disciplina, $alunoId);

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para finalizar neste ano letivo.');
        }

        [$finalizadas, $jaFinalizadas] = DB::transaction(function () use ($notas, $trimestre) {
            $finalizadas  = 0;
            $jaFinalizadas = 0;

            foreach ($notas as $nota) {
                if ($trimestre) {
                    $campo = "bloqueado_t{$trimestre}";

                    if ($nota->{$campo}) {
                        $jaFinalizadas++;
                        continue;
                    }

                    $nota->update([$campo => true]);
                } else {
                    if ($nota->status === 'finalizado'
                        && $nota->bloqueado_t1
                        && $nota->bloqueado_t2
                        && $nota->bloqueado_t3
                    ) {
                        $jaFinalizadas++;
                        continue;
                    }

                    $nota->update([
                        'status'       => 'finalizado',
                        'bloqueado_t1' => true,
                        'bloqueado_t2' => true,
                        'bloqueado_t3' => true,
                    ]);
                }

                $finalizadas++;
            }

            return [$finalizadas, $jaFinalizadas];
        });

        $escopoAluno = $alunoId ? ' para o aluno selecionado' : '';

        return back()->with('success', $trimestre
            ? "Bloqueio do {$trimestre}o trimestre{$escopoAluno} concluido: {$finalizadas} notas bloqueadas e {$jaFinalizadas} ja estavam bloqueadas."
            : "Finalizacao geral{$escopoAluno} concluida: {$finalizadas} notas finalizadas e {$jaFinalizadas} ja estavam finalizadas.");
    }

    public function reabrir(Request $request)
    {
        $this->checkPermission('notas.reabrir');

        $validated = $request->validate([
            'turma_id'      => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
            'motivo'        => 'nullable|string|max:500',
            'trimestre'     => 'nullable|in:1,2,3',
            'aluno_id'      => 'nullable|exists:users,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turma      = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $alunoId   = $validated['aluno_id'] ?? null;
        $trimestre = $validated['trimestre'] ?? null;
        $notas     = $this->buscarNotasDaPauta($turma, $disciplina, $alunoId);

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para reabrir neste ano letivo.');
        }

        [$reabertas, $jaAbertas] = DB::transaction(function () use ($notas, $trimestre) {
            $reabertas = 0;
            $jaAbertas = 0;

            foreach ($notas as $nota) {
                if ($trimestre) {
                    $campo                    = "bloqueado_t{$trimestre}";
                    $precisaDesbloquear       = (bool) $nota->{$campo};
                    $precisaReabrirStatus     = $nota->status === 'finalizado';

                    if (!$precisaDesbloquear && !$precisaReabrirStatus) {
                        $jaAbertas++;
                        continue;
                    }

                    $dadosAtualizacao = [];

                    if ($precisaDesbloquear) {
                        $dadosAtualizacao[$campo] = false;
                    }

                    if ($precisaReabrirStatus) {
                        $dadosAtualizacao['status'] = 'em_lancamento';
                    }

                    $nota->update($dadosAtualizacao);
                } else {
                    $jaEstaAberto = $nota->status !== 'finalizado'
                        && !$nota->bloqueado_t1
                        && !$nota->bloqueado_t2
                        && !$nota->bloqueado_t3;

                    if ($jaEstaAberto) {
                        $jaAbertas++;
                        continue;
                    }

                    $nota->update([
                        'status'       => 'em_lancamento',
                        'bloqueado_t1' => false,
                        'bloqueado_t2' => false,
                        'bloqueado_t3' => false,
                    ]);
                }

                $reabertas++;
            }

            return [$reabertas, $jaAbertas];
        });

        $escopoAluno = $alunoId ? ' para o aluno selecionado' : '';

        return back()->with('success', $trimestre
            ? "Reabertura do {$trimestre}o trimestre{$escopoAluno} concluida: {$reabertas} notas desbloqueadas e {$jaAbertas} ja estavam desbloqueadas."
            : "Reabertura geral{$escopoAluno} concluida: {$reabertas} notas reabertas e {$jaAbertas} ja estavam em lancamento.");
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function verificarPermissaoProfessor(Nota $nota): void
    {
        $temAtribuicao = auth()->user()->atribuicoes()
            ->where('turma_id', $nota->turma_id)
            ->where('disciplina_id', $nota->disciplina_id)
            ->where('ano_letivo_id', $nota->ano_letivo_id)
            ->exists();

        if (!$temAtribuicao) {
            abort(403, 'Voce nao tem permissao para editar esta nota.');
        }
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

    private function validarBloqueioFinalizacao(Nota $nota, ?string $trimestre = null): void
    {
        if (!$this->notaBloqueadaParaEdicao($nota, $trimestre)) {
            return;
        }

        if ($trimestre && (($nota->{"bloqueado_t{$trimestre}"} ?? false) === true)) {
            abort(403, "Este {$trimestre}o trimestre esta finalizado e bloqueado para edicao.");
        }

        abort(403, 'Esta nota ja foi finalizada e esta bloqueada para edicao.');
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

    private function buscarNotasDaPauta(Turma $turma, Disciplina $disciplina, ?int $alunoId = null)
    {
        return Nota::where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->when($alunoId, fn ($query, $id) => $query->where('aluno_id', $id))
            ->get();
    }

}
