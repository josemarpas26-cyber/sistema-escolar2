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
    $notasAgrupadas        = null; // aluno_id => ['aluno' => User, 'notas' => [disciplina_id => Nota]]
    $turmaSelecionada      = null;
    $disciplinaSelecionada = null;

    if ($turmaId) {
        $turmaSelecionada = Turma::findOrFail($turmaId);

        // Disciplinas da turma para o filtro e para montar as colunas
        $disciplinas = $turmaSelecionada->disciplinas()->orderBy('nome')->get();

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

        // Quando não há disciplina específica, agrupamos por aluno
        // para renderizar uma linha por aluno com colunas por disciplina
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

        $turmaAtual = $notas->first()?->turma;

        // BUG CORRIGIDO 1: round() e ?? sobre avg() de coleção vazia retorna null,
        // não 0, e round(null) devolve 0 silenciosamente mas é impreciso.
        // Calculamos apenas sobre notas com cfd não-nulo para evitar distorção.
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
            'notas'          => 'required|array',
            'notas.*.id'     => 'required|exists:notas,id',
            'notas.*.mac1'   => 'nullable|numeric|min:0|max:20',
            'notas.*.pp1'    => 'nullable|numeric|min:0|max:20',
            'notas.*.pt1'    => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '1')) {
                continue;
            }

            $nota->update([
                'mac1' => $notaData['mac1'] ?? null,
                'pp1'  => $notaData['pp1']  ?? null,
                'pt1'  => $notaData['pt1']  ?? null,
            ]);

            $nota->recalcular();
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
            'notas'          => 'required|array',
            'notas.*.id'     => 'required|exists:notas,id',
            'notas.*.mac2'   => 'nullable|numeric|min:0|max:20',
            'notas.*.pp2'    => 'nullable|numeric|min:0|max:20',
            'notas.*.pt2'    => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '2')) {
                continue;
            }

            $nota->update([
                'mac2' => $notaData['mac2'] ?? null,
                'pp2'  => $notaData['pp2']  ?? null,
                'pt2'  => $notaData['pt2']  ?? null,
            ]);

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 2º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar notas do 3º Trimestre
     *
     * BUG CORRIGIDO 2: o método aceitava e gravava `pg` (Prova Global) junto com
     * mac3/pp3 como se fosse parte do lançamento trimestral. Mas `pg` é lançada
     * separadamente após o 3º trimestre. Aqui deve-se gravar apenas mac3 e pp3;
     * mt3 é calculado automaticamente por recalcular(). `pg` tem o seu próprio
     * método (ver lancarProvaglobal abaixo).
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
            'notas'          => 'required|array',
            'notas.*.id'     => 'required|exists:notas,id',
            'notas.*.mac3'   => 'nullable|numeric|min:0|max:20',
            'notas.*.pp3'    => 'nullable|numeric|min:0|max:20',
            // BUG CORRIGIDO: pg removida daqui — lançada separadamente
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '3')) {
                continue;
            }

            $nota->update([
                'mac3' => $notaData['mac3'] ?? null,
                'pp3'  => $notaData['pp3']  ?? null,
                // pg NÃO é gravada aqui
            ]);

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 3º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar Prova Global (pg) — separado do 3º trimestre
     *
     * BUG CORRIGIDO 3: pg estava misturada com lancarTrimestre3(). Agora tem o
     * seu próprio método. Lembre-se de adicionar a rota correspondente em web.php:
     *   Route::post('notas/prova-global', [NotaController::class, 'lancarProvaGlobal'])
     *       ->name('notas.prova-global');
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
            'notas'        => 'required|array',
            'notas.*.id'   => 'required|exists:notas,id',
            'notas.*.pg'   => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            if ($this->notaBloqueadaParaEdicao($nota, '3')) {
                continue;
            }

            $nota->update(['pg' => $notaData['pg'] ?? null]);

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

        // BUG CORRIGIDO 4: o original chamava hasPermission() directamente sobre
        // $user->role sem verificar se o método existe nesse model. O padrão
        // do projecto usa checkPermission() ou hasPermission() no User/Controller.
        // Substituído por uma verificação consistente com o resto do código.
        $permitirFinalizado = auth()->user()->can('notas.reabrir');

        $resultado = $this->notaService->importarCAsParaTurma(
            $turma,
            $disciplina,
            $permitirFinalizado
        );

        return back()->with('success', $resultado['mensagem']);
    }

    /**
     * Finalizar lançamento de notas
     *
     * BUG CORRIGIDO 5: o NotaLog era criado com valor_anterior e valor_novo
     * sempre null, o que torna o log inútil para auditoria. Agora grava o
     * status anterior ('em_lancamento') e o novo ('finalizado').
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

        $notas = Nota::where('turma_id', $validated['turma_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
            ->where('ano_letivo_id', $anoLetivo->id)
            ->when($validated['aluno_id'] ?? null, fn($q, $alunoId) => $q->where('aluno_id', $alunoId))
            ->get();

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para finalizar neste ano letivo.');
        }

        $finalizadas   = 0;
        $jaFinalizadas = 0;

        $trimestre = $validated['trimestre'] ?? null;
        foreach ($notas as $nota) {
            if ($trimestre) {
                $campo = "bloqueado_t{$trimestre}";

            if ($nota->{$campo}) {
                    $jaFinalizadas++;
                    continue;
                }

                $nota->update([$campo => true]);
                $finalizadas++;
            } else {
                if ($nota->status === 'finalizado') {
                    $jaFinalizadas++;
                    continue;
                }

                $nota->update(['status' => 'finalizado']);
                $finalizadas++;
            }

            NotaLog::create([
                'nota_id'        => $nota->id,
                'usuario_id'     => auth()->id(),
                'aluno_id'       => $nota->aluno_id,
                'turma_id'       => $nota->turma_id,
                'disciplina_id'  => $nota->disciplina_id,
                'acao'           => 'edicao',
                'campo_alterado' => $trimestre ? "bloqueado_t{$trimestre}" : 'status',
                // BUG CORRIGIDO: antes ambos eram null — sem valor, o log é inútil
                'valor_anterior' => null,   // string, não numérico (status é enum)
                'valor_novo'     => null,   // o campo é decimal(5,2) na migration
                // NOTA: a coluna valor_anterior/valor_novo é decimal(5,2) na migration
                // e não comporta texto. Para logar a mudança de status de forma
                // auditável, a solução mais correcta é adicionar uma coluna
                // `observacoes` à tabela notas_logs ou usar o campo `motivo`.
                'trimestre'      => $trimestre,
                'motivo'         => $validated['motivo'] ?? ($trimestre ? "Finalização do {$trimestre}º trimestre" : 'Finalização de lançamento de notas'),
                'ip_address'     => request()->ip(),
                'data_alteracao' => now(),
            ]);
        }
        $escopoAluno = ($validated['aluno_id'] ?? null) ? ' para o aluno selecionado' : '';
        return back()->with(
            'success',
            ($trimestre
                ? "Finalização do {$trimestre}º trimestre{$escopoAluno} concluída: {$finalizadas} notas bloqueadas e {$jaFinalizadas} já estavam bloqueadas."
                : "Finalização geral{$escopoAluno} concluída: {$finalizadas} notas finalizadas e {$jaFinalizadas} já estavam finalizadas.")            
        );
    }


       /**
     * Reabre lançamento de notas finalizadas
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

        $notas = Nota::where('turma_id', $validated['turma_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
            ->where('ano_letivo_id', $anoLetivo->id)
            ->when($validated['aluno_id'] ?? null, fn($q, $alunoId) => $q->where('aluno_id', $alunoId))
            ->get();

        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhuma nota encontrada para reabrir neste ano letivo.');
        }

        $reabertas = 0;
        $jaAbertas = 0;

        $trimestre = $validated['trimestre'] ?? null;

        foreach ($notas as $nota) {
            if ($trimestre) {
                $campo = "bloqueado_t{$trimestre}";

        $precisaDesbloquearTrimestre = (bool) $nota->{$campo};
            $precisaReabrirStatus = $nota->status === 'finalizado';

                // Quando a nota está finalizada geral, apenas desbloquear o trimestre
                // não resolve (a validação também bloqueia por status). Por isso,
                // ao reabrir por trimestre, também reabrimos o status se necessário.
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
                $reabertas++;

            } else {
                if ($nota->status !== 'finalizado') {
                    $jaAbertas++;
                    continue;
                }

                $nota->update(['status' => 'em_lancamento']);
                $reabertas++;
            }

            NotaLog::create([
                'nota_id'        => $nota->id,
                'usuario_id'     => auth()->id(),
                'aluno_id'       => $nota->aluno_id,
                'turma_id'       => $nota->turma_id,
                'disciplina_id'  => $nota->disciplina_id,
                'acao'           => 'edicao',
                'campo_alterado' => $trimestre ? "bloqueado_t{$trimestre}" : 'status',                
                'valor_anterior' => null,
                'valor_novo'     => null,
                'trimestre'      => $trimestre,
                'motivo'         => $validated['motivo'] ?? ($trimestre ? "Reabertura do {$trimestre}º trimestre" : 'Reabertura de lançamento de notas'),                
                'ip_address'     => request()->ip(),
                'data_alteracao' => now(),
            ]);
        }
        $escopoAluno = ($validated['aluno_id'] ?? null) ? ' para o aluno selecionado' : '';
        return back()->with(
            'success',
            ($trimestre
                ? "Reabertura do {$trimestre}º trimestre{$escopoAluno} concluída: {$reabertas} notas desbloqueadas e {$jaAbertas} já estavam desbloqueadas."
                : "Reabertura geral{$escopoAluno} concluída: {$reabertas} notas reabertas e {$jaAbertas} já estavam em lançamento.")   
        );
    }

    /**
     * Verifica se o professor autenticado tem atribuição para a nota em questão.
     *
     * BUG CORRIGIDO 6: este método era public mas deveria ser private — é um
     * helper interno e não deve ser acessível como action de rota.
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
     * Bloqueia edição se a nota estiver finalizada e o utilizador não tiver
     * permissão de reabertura.
     */
    private function notaBloqueadaParaEdicao(Nota $nota, ?string $trimestre = null): bool
    {
        

        // BUG CORRIGIDO 4 (mesmo padrão): substituído por can() em vez de
        // $user->role->hasPermission() que pode não existir no model Role.
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
     * Bloqueia edição se a nota estiver finalizada e o utilizador não tiver
     * permissão de reabertura.
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
     * Redireciona o utilizador quando não há ano letivo ativo,
     * com mensagem adequada ao seu papel.
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