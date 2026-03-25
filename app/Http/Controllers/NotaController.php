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
    public function __construct(private readonly NotaService $notaService)
    {
    }

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

        $turmaId = $request->turma_id;
        $disciplinaId = $request->disciplina_id;

        $notas = null;
        $turma = null;
        $disciplina = null;

        if ($turmaId && $disciplinaId) {
            $temAtribuicao = $atribuicoes
                ->where('turma_id', $turmaId)
                ->where('disciplina_id', $disciplinaId)
                ->isNotEmpty();

            if (!$temAtribuicao) {
                return back()->with('error', 'Voce nao leciona esta disciplina nesta turma.');
            }

            $turma = Turma::findOrFail($turmaId);
            $disciplina = Disciplina::findOrFail($disciplinaId);
            $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();

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

    public function inicializarPauta(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
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

            if (!$temAtribuicao) {
                abort(403, 'Voce nao tem permissao para inicializar esta pauta.');
            }
        }

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $criados = $this->notaService->criarNotasParaTurma($turma, $disciplina);

        return redirect()
            ->route('notas.index', ['turma_id' => $turma->id, 'disciplina_id' => $disciplina->id])
            ->with('success', "Pauta inicializada. {$criados} registros criados.");
    }

    public function secretariaIndex(Request $request)
    {
        $this->checkPermission('notas.view_all');

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turmas = Turma::anoAtivo()->with('curso')->get();
        $disciplinas = collect();

        $turmaId = $request->turma_id;
        $disciplinaId = $request->disciplina_id;

        $notas = null;
        $notasAgrupadas = null;
        $turmaSelecionada = null;
        $disciplinaSelecionada = null;

        if ($turmaId) {
            $turmaSelecionada = Turma::findOrFail($turmaId);
            $disciplinas = $turmaSelecionada->disciplinas()->orderBy('nome')->get();

            $query = Nota::where('turma_id', $turmaSelecionada->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with(['aluno', 'disciplina']);

            if ($disciplinaId) {
                $disciplinaSelecionada = Disciplina::findOrFail($disciplinaId);
                $query->where('disciplina_id', $disciplinaSelecionada->id);
            }

            if ($request->filled('aluno')) {
                $query->whereHas('aluno', fn ($query) => $query
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

        $aluno = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplina', 'turma'])
            ->get();

        $turmaAtual = $notas->first()?->turma;
        $notasComCfd = $notas->whereNotNull('cfd');
        $mediaGeral = $notasComCfd->isNotEmpty()
            ? round($notasComCfd->avg('cfd'), 2)
            : 0;

        $aprovacoes = $notasComCfd->filter(fn ($nota) => $nota->cfd >= 10)->count();
        $reprovacoes = $notasComCfd->filter(fn ($nota) => $nota->cfd < 10)->count();

        return view('notas.aluno', compact(
            'notas',
            'turmaAtual',
            'mediaGeral',
            'aprovacoes',
            'reprovacoes'
        ));
    }

    public function lancarTrimestre1(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac1' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp1' => 'nullable|numeric|min:0|max:20',
            'notas.*.pt1' => 'nullable|numeric|min:0|max:20',
        ]);

        $ids = collect($validated['notas'])->pluck('id');
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']);

            if (!$nota) {
                continue;
            }

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '1')) {
                continue;
            }

            $nota->mac1 = $notaData['mac1'] ?? null;
            $nota->pp1 = $notaData['pp1'] ?? null;
            $nota->pt1 = $notaData['pt1'] ?? null;
            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 1o trimestre lancadas com sucesso.');
    }

    public function lancarTrimestre2(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac2' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp2' => 'nullable|numeric|min:0|max:20',
            'notas.*.pt2' => 'nullable|numeric|min:0|max:20',
        ]);

        $ids = collect($validated['notas'])->pluck('id');
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']);

            if (!$nota) {
                continue;
            }

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '2')) {
                continue;
            }

            $nota->mac2 = $notaData['mac2'] ?? null;
            $nota->pp2 = $notaData['pp2'] ?? null;
            $nota->pt2 = $notaData['pt2'] ?? null;
            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 2o trimestre lancadas com sucesso.');
    }

    public function lancarTrimestre3(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac3' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp3' => 'nullable|numeric|min:0|max:20',
            'notas.*.pg' => 'nullable|numeric|min:0|max:20',
        ]);

        $ids = collect($validated['notas'])->pluck('id');
        $notasMap = Nota::whereIn('id', $ids)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->keyBy('id');

        foreach ($validated['notas'] as $notaData) {
            $nota = $notasMap->get($notaData['id']);

            if (!$nota) {
                continue;
            }

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '3')) {
                continue;
            }

            $nota->mac3 = $notaData['mac3'] ?? null;
            $nota->pp3 = $notaData['pp3'] ?? null;
            $nota->pg = $notaData['pg'] ?? null;
            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 3o trimestre lancadas com sucesso.');
    }

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
            'mac1' => 'nullable|numeric|min:0|max:20',
            'pp1' => 'nullable|numeric|min:0|max:20',
            'pt1' => 'nullable|numeric|min:0|max:20',
            'mac2' => 'nullable|numeric|min:0|max:20',
            'pp2' => 'nullable|numeric|min:0|max:20',
            'pt2' => 'nullable|numeric|min:0|max:20',
            'mac3' => 'nullable|numeric|min:0|max:20',
            'pp3' => 'nullable|numeric|min:0|max:20',
            'pg' => 'nullable|numeric|min:0|max:20',
            'ca_10' => 'nullable|numeric|min:0|max:20',
            'ca_11' => 'nullable|numeric|min:0|max:20',
            'observacoes' => 'nullable|string',
        ]);

        $nota->update($validated);
        $nota->recalcular();
        $nota->save();

        return redirect()
            ->route('notas.index', [
                'turma_id' => $nota->turma_id,
                'disciplina_id' => $nota->disciplina_id,
            ])
            ->with('success', 'Nota atualizada com sucesso.');
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
        $permitirFinalizado = auth()->user()->can('notas.reabrir');

        $resultado = $this->notaService->importarCAsParaTurma(
            $turma,
            $disciplina,
            $permitirFinalizado
        );

        return back()->with('success', $resultado['mensagem']);
    }

    public function finalizar(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
            'motivo' => 'nullable|string|max:500',
            'trimestre' => 'nullable|in:1,2,3',
            'aluno_id' => 'nullable|exists:users,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turma = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $alunoId = $validated['aluno_id'] ?? null;
        $trimestre = $validated['trimestre'] ?? null;
        $notas = $this->buscarNotasDaPauta($turma, $disciplina, $alunoId);

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para finalizar neste ano letivo.');
        }

        $completude = $trimestre
            ? $this->notaService->verificarCompletudeTrimestre($turma, $disciplina, (int) $trimestre, $alunoId)
            : $this->notaService->verificarCompletudeFinalizacao($turma, $disciplina, $alunoId);

        if ($completude['incompletas'] > 0) {
            return back()->with('error', $this->mensagemCompletudePendente($trimestre, $completude['incompletas']));
        }

        [$finalizadas, $jaFinalizadas] = DB::transaction(function () use ($notas, $trimestre) {
            $finalizadas = 0;
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
                    if (
                        $nota->status === 'finalizado'
                        && $nota->bloqueado_t1
                        && $nota->bloqueado_t2
                        && $nota->bloqueado_t3
                    ) {
                        $jaFinalizadas++;
                        continue;
                    }

                    $nota->update([
                        'status' => 'finalizado',
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
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
            'motivo' => 'nullable|string|max:500',
            'trimestre' => 'nullable|in:1,2,3',
            'aluno_id' => 'nullable|exists:users,id',
        ]);

        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return $this->redirectSemAnoLetivoAtivo();
        }

        $turma = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);

        if ($turma->ano_letivo_id !== $anoLetivo->id) {
            return back()->with('error', 'A turma selecionada nao pertence ao ano letivo ativo.');
        }

        $alunoId = $validated['aluno_id'] ?? null;
        $trimestre = $validated['trimestre'] ?? null;
        $notas = $this->buscarNotasDaPauta($turma, $disciplina, $alunoId);

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para reabrir neste ano letivo.');
        }

        [$reabertas, $jaAbertas] = DB::transaction(function () use ($notas, $trimestre) {
            $reabertas = 0;
            $jaAbertas = 0;

            foreach ($notas as $nota) {
                if ($trimestre) {
                    $campo = "bloqueado_t{$trimestre}";
                    $precisaDesbloquearTrimestre = (bool) $nota->{$campo};
                    $precisaReabrirStatus = $nota->status === 'finalizado';

                    if (!$precisaDesbloquearTrimestre && !$precisaReabrirStatus) {
                        $jaAbertas++;
                        continue;
                    }

                    $dadosAtualizacao = [];

                    if ($precisaDesbloquearTrimestre) {
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
                        'status' => 'em_lancamento',
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

    private function verificarPermissaoProfessor(Nota $nota): void
    {
        $professor = auth()->user();

        $temAtribuicao = $professor->atribuicoes()
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

        if ($trimestre) {
            $campo = "bloqueado_t{$trimestre}";

            if (($nota->{$campo} ?? false) === true) {
                return true;
            }
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

    private function mensagemCompletudePendente(?string $trimestre, int $incompletas): string
    {
        if ($trimestre) {
            return "Ainda existem {$incompletas} notas incompletas para o {$trimestre}o trimestre.";
        }

        return "Ainda existem {$incompletas} notas sem classificacao final completa.";
    }
}
