<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\Disciplina;
use App\Models\User;
use App\Models\AnoLetivo;
use App\Models\NotaLog;
use App\Services\NotaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class NotaController extends Controller
{
    public function __construct(private readonly NotaService $notaService)
    {
    }

    /**
     * Painel de lançamento de notas (redireciona conforme o papel)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            return $this->professorIndex($request);
        }

        if ($user->isSecretaria() || $user->isAdmin()) {
            return $this->secretariaIndex($request);
        }

        if ($user->isAluno()) {
            return $this->alunoIndex();
        }

        abort(403);
    }

    /**
     * Painel do professor
     */
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

        $notas      = null;
        $turma      = null;
        $disciplina = null;

        if ($turmaId && $disciplinaId) {
            $temAtribuicao = $atribuicoes
                ->where('turma_id',      $turmaId)
                ->where('disciplina_id', $disciplinaId)
                ->isNotEmpty();

            if (!$temAtribuicao) {
                return back()->with('error', 'Você não leciona esta disciplina nesta turma!');
            }

            $turma      = Turma::findOrFail($turmaId);
            $disciplina = Disciplina::findOrFail($disciplinaId);

            $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();

            $notasPorAluno = Nota::where('turma_id',      $turma->id)
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

    /**
     * Inicializa pauta (criação de notas) via POST
     */
    public function inicializarPauta(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

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
                ->where('turma_id',      $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->exists();

            if (!$temAtribuicao) {
                abort(403, 'Você não tem permissão para inicializar esta pauta.');
            }
        }

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada não pertence ao ano letivo ativo.');
        }

        $criados = $this->notaService->criarNotasParaTurma($turma, $disciplina);

        return redirect()
            ->route('notas.index', ['turma_id' => $turma->id, 'disciplina_id' => $disciplina->id])
            ->with('success', "Pauta inicializada. {$criados} registros criados.");
    }

    /**
     * Painel da secretaria/admin
     */
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

        $notas                 = null;
        $notasAgrupadas        = null;
        $turmaSelecionada      = null;
        $disciplinaSelecionada = null;

        if ($turmaId) {
            $turmaSelecionada = Turma::findOrFail($turmaId);
            $disciplinas      = $turmaSelecionada->disciplinas()->orderBy('nome')->get();

            $query = Nota::where('turma_id',      $turmaSelecionada->id)
                         ->where('ano_letivo_id', $anoLetivo->id)
                         ->with(['aluno', 'disciplina']);

            if ($disciplinaId) {
                $disciplinaSelecionada = Disciplina::findOrFail($disciplinaId);
                $query->where('disciplina_id', $disciplinaSelecionada->id);
            }

            if ($request->filled('aluno')) {
                $query->whereHas('aluno', fn($q) => $q
                    ->where('name',              'like', "%{$request->aluno}%")
                    ->orWhere('numero_processo', 'like', "%{$request->aluno}%")
                );
            }

            $notas = $query->get();

            if (!$disciplinaSelecionada) {
                $notasAgrupadas = $notas
                    ->groupBy('aluno_id')
                    ->map(fn($grupo) => [
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

    /**
     * Painel do aluno
     */
    public function alunoIndex()
    {
        $this->checkPermission('notas.view_own');

        $aluno     = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $notas = Nota::where('aluno_id',     $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplina', 'turma'])
            ->get();

        $turmaAtual  = $notas->first()?->turma;
        $notasComCfd = $notas->whereNotNull('cfd');
        $mediaGeral  = $notasComCfd->isNotEmpty()
            ? round($notasComCfd->avg('cfd'), 2)
            : 0;

        $aprovacoes  = $notasComCfd->filter(fn($n) => $n->cfd >= 10)->count();
        $reprovacoes = $notasComCfd->filter(fn($n) => $n->cfd < 10)->count();

        return view('notas.aluno', compact(
            'notas',
            'turmaAtual',
            'mediaGeral',
            'aprovacoes',
            'reprovacoes'
        ));
    }

    /**
     * Lançar notas do 1º Trimestre
     */
    public function lancarTrimestre1(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas'        => 'required|array',
            'notas.*.id'   => 'required|exists:notas,id',
            'notas.*.mac1' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp1'  => 'nullable|numeric|min:0|max:20',
            'notas.*.pt1'  => 'nullable|numeric|min:0|max:20',
        ]);

        $ids = collect($validated['notas'])->pluck('id');
        
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']);
            
            if (!$nota) continue;

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '1')) {
                continue;
            }

            $nota->mac1 = $notaData['mac1'] ?? null;
            $nota->pp1  = $notaData['pp1']  ?? null;
            $nota->pt1  = $notaData['pt1']  ?? null;
            
            $nota->recalcular(); // agora turma e disciplina já estão carregadas
            $nota->save();
        }

        return back()->with('success', 'Notas do 1º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar notas do 2º Trimestre
     */
    public function lancarTrimestre2(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas'        => 'required|array',
            'notas.*.id'   => 'required|exists:notas,id',
            'notas.*.mac2' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp2'  => 'nullable|numeric|min:0|max:20',
            'notas.*.pt2'  => 'nullable|numeric|min:0|max:20',
        ]);

        $ids = collect($validated['notas'])->pluck('id');
        
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']);
            
            if (!$nota) continue;

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '2')) {
                continue;
            }

            $nota->mac1 = $notaData['mac2'] ?? null;
            $nota->pp1  = $notaData['pp2']  ?? null;
            $nota->pt1  = $notaData['pt2']  ?? null;
            
            $nota->recalcular(); // agora turma e disciplina já estão carregadas
            $nota->save();
        }

        return back()->with('success', 'Notas do 2º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar notas do 3º Trimestre
     */
    public function lancarTrimestre3(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas'        => 'required|array',
            'notas.*.id'   => 'required|exists:notas,id',
            'notas.*.mac3' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp3'  => 'nullable|numeric|min:0|max:20',
            'notas.*.pg'   => 'nullable|numeric|min:0|max:20', // ← ADICIONAR
        ]);

        $ids = collect($validated['notas'])->pluck('id');

        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']);

            if (!$nota) continue;

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '3')) {
                continue;
            }

            $nota->mac3 = $notaData['mac3'] ?? null;
            $nota->pp3  = $notaData['pp3']  ?? null;
            $nota->pg   = $notaData['pg']   ?? null; // ← ADICIONAR

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 3º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar Prova Global (pg) — separado do 3º trimestre
     */
    public function lancarProvaGlobal(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas'      => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.pg' => 'nullable|numeric|min:0|max:20',
        ]);

        // ✅ Eager load
        $notasMap = Nota::whereIn('id', collect($validated['notas'])->pluck('id'))
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']); // ✅ sem query

            if (!$nota) continue;

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '3')) {
                continue;
            }

            $nota->pg = $notaData['pg'] ?? null;

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Prova Global lançada com sucesso!');
    }

    /**
     * Editar nota individual
     */
    public function edit(Nota $nota)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->verificarPermissaoProfessor($nota);
        } else {
            $this->checkPermission('notas.editar');
        }

        $this->validarBloqueioFinalizacao($nota);
        $nota->load(['aluno', 'turma', 'disciplina']);

        return view('notas.edit', compact('nota'));
    }

    /**
     * Atualizar nota individual
     */
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

        $validated = $request->validate([
            'mac1'        => 'nullable|numeric|min:0|max:20',
            'pp1'         => 'nullable|numeric|min:0|max:20',
            'pt1'         => 'nullable|numeric|min:0|max:20',
            'mac2'        => 'nullable|numeric|min:0|max:20',
            'pp2'         => 'nullable|numeric|min:0|max:20',
            'pt2'         => 'nullable|numeric|min:0|max:20',
            'mac3'        => 'nullable|numeric|min:0|max:20',
            'pp3'         => 'nullable|numeric|min:0|max:20',
            'pg'          => 'nullable|numeric|min:0|max:20',
            'ca_10'       => 'nullable|numeric|min:0|max:20',
            'ca_11'       => 'nullable|numeric|min:0|max:20',
            'observacoes' => 'nullable|string',
        ]);

        $nota->update($validated);
        $nota->recalcular();
        $nota->save();

        return redirect()
            ->route('notas.index', [
                'turma_id'      => $nota->turma_id,
                'disciplina_id' => $nota->disciplina_id,
            ])
            ->with('success', 'Nota atualizada com sucesso!');
    }

    /**
     * Importar CAs de anos anteriores (11ª e 12ª)
     */
    public function importarCAs(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id'      => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $turma      = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        $permitirFinalizado = auth()->user()->can('notas.reabrir');

        $resultado = $this->notaService->importarCAsParaTurma(
            $turma,
            $disciplina,
            $permitirFinalizado
        );

        return back()->with('success', $resultado['mensagem']);
    }

    /**
     * Finalizar lançamento de notas.
     *
     * CORREÇÃO: a finalização geral agora também força bloqueado_t1/t2/t3 = true,
     * garantindo estado consistente independente de bloqueios parciais anteriores.
     * Da mesma forma, a finalização por trimestre não toca no status geral.
     */
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

        $notas = Nota::where('turma_id',      $validated['turma_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
            ->where('ano_letivo_id', $anoLetivo->id)
            ->when($validated['aluno_id'] ?? null, fn($q, $id) => $q->where('aluno_id', $id))
            ->get();

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para finalizar neste ano letivo.');
        }

        $finalizadas   = 0;
        $jaFinalizadas = 0;
        $trimestre     = $validated['trimestre'] ?? null;

        foreach ($notas as $nota) {
            if ($trimestre) {
                // ── Bloqueio por trimestre ──────────────────────────────────
                $campo = "bloqueado_t{$trimestre}";

                if ($nota->{$campo}) {
                    $jaFinalizadas++;
                    continue;
                }

                $nota->update([$campo => true]);

            } else {
                // ── Finalização geral ───────────────────────────────────────
                // CORREÇÃO: força os três flags de trimestre para true junto com
                // o status, eliminando qualquer inconsistência de estado misto
                // que viesse de bloqueios/desbloqueios parciais anteriores.
                if ($nota->status === 'finalizado'
                    && $nota->bloqueado_t1
                    && $nota->bloqueado_t2
                    && $nota->bloqueado_t3
                ) {
                    $jaFinalizadas++;
                    continue;
                }

                $nota->update([
                    'status'      => 'finalizado',
                    'bloqueado_t1' => true,
                    'bloqueado_t2' => true,
                    'bloqueado_t3' => true,
                ]);
            }

            $finalizadas++;

        }

        $escopoAluno = ($validated['aluno_id'] ?? null) ? ' para o aluno selecionado' : '';

        return back()->with('success', $trimestre
            ? "Finalização do {$trimestre}º trimestre{$escopoAluno} concluída: {$finalizadas} notas bloqueadas e {$jaFinalizadas} já estavam bloqueadas."
            : "Finalização geral{$escopoAluno} concluída: {$finalizadas} notas finalizadas e {$jaFinalizadas} já estavam finalizadas."
        );
    }

    /**
     * Reabre lançamento de notas finalizadas.
     *
     * CORREÇÃO: a reabertura geral agora também zera bloqueado_t1/t2/t3,
     * eliminando o estado inconsistente onde um aluno com trimestre bloqueado
     * individualmente ficaria com flags em conflito após uma reabertura geral.
     * A reabertura por trimestre continua funcionando de forma independente.
     */
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

        $notas = Nota::where('turma_id',      $validated['turma_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
            ->where('ano_letivo_id', $anoLetivo->id)
            ->when($validated['aluno_id'] ?? null, fn($q, $id) => $q->where('aluno_id', $id))
            ->get();

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para reabrir neste ano letivo.');
        }

        $reabertas = 0;
        $jaAbertas = 0;
        $trimestre = $validated['trimestre'] ?? null;

        foreach ($notas as $nota) {
            if ($trimestre) {
                // ── Desbloqueio por trimestre ───────────────────────────────
                $campo = "bloqueado_t{$trimestre}";

                $precisaDesbloquearTrimestre = (bool) $nota->{$campo};
                $precisaReabrirStatus        = $nota->status === 'finalizado';

                if (!$precisaDesbloquearTrimestre && !$precisaReabrirStatus) {
                    $jaAbertas++;
                    continue;
                }

                $dadosAtualizacao = [];

                if ($precisaDesbloquearTrimestre) {
                    $dadosAtualizacao[$campo] = false;
                }

                // Se o status estava finalizado, reabre — mas preserva os
                // outros trimestres que estejam bloqueados individualmente.
                if ($precisaReabrirStatus) {
                    $dadosAtualizacao['status'] = 'em_lancamento';
                }

                $nota->update($dadosAtualizacao);

            } else {
                // ── Reabertura geral ────────────────────────────────────────
                // CORREÇÃO: além de reverter o status, zera os três flags de
                // trimestre. Sem isso, um aluno que tinha bloqueado_t1 = true
                // individualmente continuaria com T1 bloqueado após a reabertura
                // geral — o que é o comportamento errado relatado.
                $jaEstaAberto = $nota->status !== 'finalizado'
                    && ! $nota->bloqueado_t1
                    && ! $nota->bloqueado_t2
                    && ! $nota->bloqueado_t3;

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

        $escopoAluno = ($validated['aluno_id'] ?? null) ? ' para o aluno selecionado' : '';

        return back()->with('success', $trimestre
            ? "Reabertura do {$trimestre}º trimestre{$escopoAluno} concluída: {$reabertas} notas desbloqueadas e {$jaAbertas} já estavam desbloqueadas."
            : "Reabertura geral{$escopoAluno} concluída: {$reabertas} notas reabertas e {$jaAbertas} já estavam em lançamento."
        );
    }

    /**
     * Verifica se o professor autenticado tem atribuição para a nota em questão.
     */
    private function verificarPermissaoProfessor(Nota $nota): void
    {
        $professor = auth()->user();

        $temAtribuicao = $professor->atribuicoes()
            ->where('turma_id',      $nota->turma_id)
            ->where('disciplina_id', $nota->disciplina_id)
            ->where('ano_letivo_id', $nota->ano_letivo_id)
            ->exists();

        if (!$temAtribuicao) {
            abort(403, 'Você não tem permissão para editar esta nota!');
        }
    }

    /**
     * Retorna true se a nota estiver bloqueada para edição naquele trimestre.
     */
    private function notaBloqueadaParaEdicao(Nota $nota, ?string $trimestre = null): bool
    {
        if (auth()->user()->can('notas.reabrir')) {
            return false;
        }

        if ($trimestre) {
            $campo = "bloqueado_t{$trimestre}";
            if (($nota->{$campo} ?? false) === true) {
                return true;
            }
        }

        return $nota->status === 'finalizado';
    }

    /**
     * Aborta com 403 se a nota estiver bloqueada para edição.
     */
    private function validarBloqueioFinalizacao(Nota $nota, ?string $trimestre = null): void
    {
        if (!$this->notaBloqueadaParaEdicao($nota, $trimestre)) {
            return;
        }

        if ($trimestre && (($nota->{"bloqueado_t{$trimestre}"} ?? false) === true)) {
            abort(403, "Este {$trimestre}º trimestre está finalizado e bloqueado para edição.");
        }

        abort(403, 'Esta nota já foi finalizada e está bloqueada para edição.');
    }

    /**
     * Redireciona o utilizador quando não há ano letivo ativo.
     */
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
            ->with('error', 'Nenhum ano letivo ativo encontrado. Entre em contato com a administração.');
    }
}