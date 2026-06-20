<?php

namespace App\Http\Controllers;

use App\Models\Turma;
use App\Models\Curso;
use App\Models\AnoLetivo;
use App\Models\User;
use App\Models\Disciplina;
use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\HistoricoAcademico;
use App\Services\ResultadoAlunoTurmaService;

class TurmaController extends Controller
{
    /**
     * Listar turmas
     */
    public function index(Request $request)
    {
        $this->checkPermission('turmas.view');

        $query = Turma::with(['curso.areaFormacao', 'anoLetivo', 'coordenador'])
            ->withCount('alunos');

        // Filtros
        if ($request->filled('curso_id')) {
            $query->where('curso_id', $request->curso_id);
        }

        if ($request->filled('classe')) {
            $query->where('classe', $request->classe);
        }

        if ($request->filled('ano_letivo_id')) {
            $query->where('ano_letivo_id', $request->ano_letivo_id);
        } else {
            // Por padrão, mostrar apenas do ano ativo
            $query->anoAtivo();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nome', 'like', "%{$search}%");
        }

        $turmas = $query->paginate(15);

        // Para os filtros
        $cursos = Curso::ativos()->with('areaFormacao')->orderBy('nome')->get();
        $anosLetivos = AnoLetivo::orderBy('nome', 'desc')->get();

        return view('turmas.index', compact('turmas', 'cursos', 'anosLetivos'));
    }

    /**
     * Formulário de criação
     */
     public function create()
{
    $this->checkPermission('turmas.create');

    $anoAtivo = AnoLetivo::ativo()->first();

    if (!$anoAtivo) {
        return redirect()
            ->route('anos-letivos.index')
            ->with('error', 'Não existe ano letivo ativo. Ative ou crie um antes de criar turmas.');
    }

    $cursos = Curso::ativos()->with('areaFormacao')->orderBy('nome')->get();
    $professores = User::professores()->ativos()->get();
    $disciplinas = Disciplina::ativos()->get();
    $anosLetivos = AnoLetivo::orderByDesc('created_at')->get(); 

    return view('turmas.create', compact(
        'cursos',
        'anoAtivo',
        'professores',
        'disciplinas',
        'anosLetivos' 
    ));
}


    /**
     * Salvar nova turma
     */
    public function store(Request $request)
    {
        $this->checkPermission('turmas.create');
        $nomeCompletoInformado = $this->montarNomeCompleto($request->all());

        // 1️⃣ Verificar se existe ano letivo ativo
        $anoAtivo = AnoLetivo::ativo()->first();

        if (!$anoAtivo) {
            return back()
                ->withInput()
                ->with('error', 'Não existe ano letivo ativo. Ative um ano letivo antes de criar turmas.');
        }

        // 2️⃣ Validar dados
        $validated = $request->validate([
             'nome' => [
                'required',
                'string',
                'size:1',
                'regex:/^[A-Za-z]$/',
                Rule::unique('turmas', 'nome')->where(function ($query) use ($request) {
                    return $query
                        ->where('curso_id', $request->input('curso_id'))
                        ->where('classe', $request->input('classe'))
                        ->where('turno', $request->input('turno'));
                }),
            ],
            'classe' => 'required|in:10,11,12,13',
            'curso_id' => 'required|exists:cursos,id',
            'ano_letivo_id' => 'required|exists:anos_letivos,id',
            'coordenador_turma_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('turmas')->where(function ($query) use ($request) {
                    return $query->where('ano_letivo_id', $request->input('ano_letivo_id'));
                }),
            ],
            'capacidade' => 'required|integer|min:1|max:100',
            'sala' => ['nullable', 'string', 'max:20'],
            'turno' => ['required', 'in:M,T'],
            'disciplinas' => 'nullable|array',
            'disciplinas.*' => 'exists:disciplinas,id',
        ], [
            'nome.required' => 'O nome da turma é obrigatório.',
            'nome.size' => 'O nome da turma deve ter exactamente 1 letra.',
            'nome.regex' => 'O nome da turma deve ser uma letra de A a Z.',
            'turno.required' => 'O turno é obrigatório.',
            'turno.in' => 'O turno deve ser Manhã (M) ou Tarde (T).',
            'sala.max' => 'A sala não pode ter mais de 20 caracteres.',
            'nome.unique' => "Já existe uma turma com o nome {$nomeCompletoInformado} neste curso, classe e turno.",
        ]);

            $validated['nome'] = strtoupper(trim($validated['nome']));
            $validated['sala'] = $this->normalizarSala($validated['sala'] ?? null);

        $erroDisciplinas = $this->validarDisciplinasDaClasse($validated['classe'], $request->input('disciplinas', []));

        if ($erroDisciplinas !== null) {
            return back()
                ->withInput()
                ->withErrors(['disciplinas' => $erroDisciplinas]);
        }

        // 3️⃣ Garantir que o ano selecionado é o ativo
        if ($validated['ano_letivo_id'] != $anoAtivo->id) {
            return back()
                ->withInput()
                ->with('error', 'Só é permitido criar turmas no ano letivo ativo.');
        }

        // 4️⃣ Verificar se já existe turma com o mesmo nome completo
        $turmaExiste = Turma::where('nome', $validated['nome'])
             ->where('curso_id', $validated['curso_id'])
            ->where('classe', $validated['classe'])
            ->where('turno', $validated['turno'])
            ->exists();

        if ($turmaExiste) {
            return back()
                ->withInput()
                ->with('error', 'Já existe uma turma com o nome completo '.$this->montarNomeCompleto($validated).' neste curso, classe e turno.');
        }

        // 5️⃣ Criar turma
        $turma = Turma::create($validated);

        // Associar disciplinas
        if ($request->filled('disciplinas')) {
            $turma->disciplinas()->attach($request->disciplinas);
        }

        return redirect()
            ->route('turmas.show', $turma)
            ->with('success', 'Turma criada com sucesso!');
    }


    /**
     * Exibir turma
     */
    public function show(Turma $turma)
    {
        $this->checkPermission('turmas.view');

        $turma->load([
            'curso.areaFormacao',
            'anoLetivo',
            'coordenador',
            'disciplinas',
            'alunos' => fn($q) => $q->orderBy('name'),
            'atribuicoes' => fn($q) => $q->with(['professor', 'disciplina']),
        ]);

        // Buscar alunos disponíveis para matrícula
        // Assumindo que role_id = 3 é para alunos (ajuste conforme sua tabela roles)
        $alunosDisponiveis = User::where('role_id', 4)
            ->where('ativo', true)
            ->whereDoesntHave('turmas', function ($q) {
                $q->where('status', 'matriculado');
            })
            ->orderBy('name')
            ->get();


        // Buscar professores ativos para atribuição
        $professores = User::professores()->ativos()->get();

        return view('turmas.show', compact('turma', 'alunosDisponiveis', 'professores'));
    }

    /**
     * Formulário de edição
     */
    public function edit(Turma $turma)
    {
        $this->checkPermission('turmas.edit');

        $cursos = Curso::ativos()->with('areaFormacao')->orderBy('nome')->get();
        $anosLetivos = AnoLetivo::all();
        $professores = User::professores()->ativos()->get();
        $disciplinas = Disciplina::ativos()->get();

        return view('turmas.edit', compact('turma', 'cursos', 'anosLetivos', 'professores', 'disciplinas'));
    }

    /**
     * Atualizar turma
     */
    public function update(Request $request, Turma $turma)
    {
        $this->checkPermission('turmas.edit');
        $nomeCompletoInformado = $this->montarNomeCompleto($request->all());

        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'size:1',
                'regex:/^[A-Za-z]$/',
                Rule::unique('turmas', 'nome')
                    ->ignore($turma->id)
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('curso_id', $request->input('curso_id'))
                            ->where('classe', $request->input('classe'))
                            ->where('turno', $request->input('turno'));
                    }),
            ],
            'classe' => 'required|in:10,11,12,13',
            'curso_id' => 'required|exists:cursos,id',
            'ano_letivo_id' => 'required|exists:anos_letivos,id',
            'coordenador_turma_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('turmas')
                    ->ignore($turma->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('ano_letivo_id', $request->input('ano_letivo_id'));
                    }),
            ],
            'capacidade' => 'required|integer|min:1|max:100',
            'sala' => ['nullable', 'string', 'max:20'],
            'turno' => ['required', 'in:M,T'],
            'ativo' => 'boolean',
            'disciplinas' => 'nullable|array',
            'disciplinas.*' => 'exists:disciplinas,id',
        ], [
            'nome.required' => 'O nome da turma é obrigatório.',
            'nome.size' => 'O nome da turma deve ter exactamente 1 letra.',
            'nome.regex' => 'O nome da turma deve ser uma letra de A a Z.',
            'turno.required' => 'O turno é obrigatório.',
            'turno.in' => 'O turno deve ser Manhã (M) ou Tarde (T).',
            'sala.max' => 'A sala não pode ter mais de 20 caracteres.',
            'nome.unique' => "Já existe uma turma com o nome {$nomeCompletoInformado} neste curso, classe e turno.",
        ]);

            $validated['nome'] = strtoupper(trim($validated['nome']));
            $validated['sala'] = $this->normalizarSala($validated['sala'] ?? null);

        $erroDisciplinas = $this->validarDisciplinasDaClasse($validated['classe'], $request->input('disciplinas', []));

        if ($erroDisciplinas !== null) {
            return back()
                ->withInput()
                ->withErrors(['disciplinas' => $erroDisciplinas]);
        }

        $turmaExiste = Turma::where('nome', $validated['nome'])
            ->where('curso_id', $validated['curso_id'])
            ->where('classe', $validated['classe'])
            ->where('turno', $validated['turno'])
            ->where('id', '!=', $turma->id)
            ->exists();

        if ($turmaExiste) {
            return back()
                ->withInput()
                ->with('error', 'Já existe uma turma com o nome completo '.$this->montarNomeCompleto($validated).' neste curso, classe e turno.');
        }


        $turma->update($validated);

        // Atualizar disciplinas
        if ($request->has('disciplinas')) {
            $turma->disciplinas()->sync($request->disciplinas);
        }

        return redirect()
            ->route('turmas.show', $turma)
            ->with('success', 'Turma atualizada com sucesso!');
    }

    /**
     * Deletar turma
     */
    public function destroy(Turma $turma)
    {
        $this->checkPermission('turmas.delete');

        // Verificar se há alunos matriculados
        if ($turma->alunos()->wherePivot('status', 'matriculado')->count() > 0) {
            return back()->with('error', 'Não é possível eliminar uma turma com alunos matriculados.');
        }

        // Verificar se há notas lançadas
        if ($turma->notas()->count() > 0) {
            return back()->with('error', 'Não é possível eliminar uma turma com notas lançadas.');
        }

        $turma->delete();

        return redirect()
            ->route('turmas.index')
            ->with('success', 'Turma eliminada com sucesso!');
    }

    /**
     * Matricular aluno
     */
    public function matricularAluno(Request $request, Turma $turma)
    {
        $this->checkPermission('turmas.edit');

        $validated = $request->validate([
            'aluno_id' => 'required|exists:users,id',
            'data_matricula' => 'required|date',
        ]);

        // Verificar se há vagas
        if (!$turma->hasVagas()) {
            return back()->with('error', 'A turma não possui vagas disponíveis!');
        }

        $statusAtual = DB::table('turma_aluno')
            ->where('turma_id', $turma->id)
            ->where('aluno_id', $validated['aluno_id'])
            ->value('status');

        $jaMatriculadoNoutraTurma = DB::table('turma_aluno')
            ->join('turmas', 'turmas.id', '=', 'turma_aluno.turma_id')
            ->where('turma_aluno.aluno_id', $validated['aluno_id'])
            ->where('turma_aluno.status', 'matriculado')
            ->where('turma_aluno.turma_id', '!=', $turma->id)
            ->where('turmas.ano_letivo_id', $turma->ano_letivo_id)
            ->exists();

        if ($jaMatriculadoNoutraTurma) {
            return back()->with('error', 'O aluno já está matriculado noutra turma neste ano letivo.');
        }

        // Já existe pivot para este aluno nesta turma
        if ($statusAtual !== null) {
            if ($statusAtual === 'matriculado') {
                return back()->with('error', 'Aluno já está matriculado nesta turma!');
            }

            // Reativar matrícula existente (a tabela possui unique por turma+aluno)
            $turma->alunos()->updateExistingPivot($validated['aluno_id'], [
                'data_matricula' => $validated['data_matricula'],
                'status' => 'matriculado',
            ]);

            return back()->with('success', 'Matrícula do aluno reativada com sucesso!');
        }

        $turma->alunos()->attach($validated['aluno_id'], [
            'data_matricula' => $validated['data_matricula'],
            'status' => 'matriculado',
        ]);

        return back()->with('success', 'Aluno matriculado com sucesso!');
    }

    /**
     * Remover matrícula do aluno
     */
    public function removerAluno(Turma $turma, User $aluno)
    {
        $this->checkPermission('turmas.edit');

        return $this->atualizarStatusAluno($turma, $aluno, 'transferido', 'Aluno removido da turma!');
    }

    /**
     * Marcar aluno como desistente
     */
    public function marcarDesistente(Turma $turma, User $aluno)
    {
        $this->checkPermission('turmas.edit');

        return $this->atualizarStatusAluno($turma, $aluno, 'desistente', 'Aluno marcado como desistente!');
    }

    /**
     * Atribuir professor a disciplina
     */
    public function atribuirProfessor(Request $request, Turma $turma)
    {
        $this->checkPermission('turmas.edit');

        $validated = $request->validate([
            'professor_id' => 'required|exists:users,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        // Verificar se a disciplina está na turma
        if (!$turma->disciplinas()->where('disciplinas.id', $validated['disciplina_id'])->exists()) {
            return back()->with('error', 'Esta disciplina não está associada à turma!');
        }

        // Não permitir mais de um professor na mesma disciplina da turma no mesmo ano letivo
        if ($turma->atribuicoes()
            ->where('disciplina_id', $validated['disciplina_id'])
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->exists()) {
            return back()->with('error', 'Esta disciplina já possui professor atribuído nesta turma.');
        }

        // Verificar duplicidade exata (professor + disciplina)
        if ($turma->atribuicoes()
            ->where('professor_id', $validated['professor_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->exists()) {
            return back()->with('error', 'Professor já está atribuído a esta disciplina nesta turma!');
        }

        $turma->atribuicoes()->create([
            'professor_id' => $validated['professor_id'],
            'disciplina_id' => $validated['disciplina_id'],
            'ano_letivo_id' => $turma->ano_letivo_id,
        ]);

        return back()->with('success', 'Professor atribuído com sucesso!');
    }

    /**
     * Remover atribuição de professor
     */
    public function removerProfessor(Turma $turma, $atribuicaoId)
    {
        $this->checkPermission('turmas.edit');

        $turma->atribuicoes()->where('id', $atribuicaoId)->delete();

        return back()->with('success', 'Atribuição removida com sucesso!');
    }

    /**
     * Ativar/Desativar turma
     */
    public function toggleStatus(Turma $turma)
    {
        $this->checkPermission('turmas.edit');

        $turma->update(['ativo' => !$turma->ativo]);

        $status = $turma->ativo ? 'ativada' : 'desativada';
        
        return back()->with('success', "Turma {$status} com sucesso!");
    }

    /**
     * Promover turma para próximo ano
     */
    public function promover(Turma $turma)
    {
        $this->checkPermission('turmas.promote');

        // ----------------------------------------------------------------
        // Validações prévias
        // ----------------------------------------------------------------

        $proximoAno = AnoLetivo::where('data_inicio', '>', $turma->anoLetivo->data_inicio)
            ->orderBy('data_inicio')
            ->first();

        if (!$proximoAno) {
            return back()->with('error', 'Não há ano letivo posterior para promover a turma.');
        }

        $novaClasse = (int) $turma->classe + 1;

        if ($novaClasse > 13) {
            return back()->with('error', 'Alunos da 13ª classe não podem ser promovidos.');
        }

        $turmaExistente = Turma::where('curso_id', $turma->curso_id)
            ->where('classe', (string) $novaClasse)
            ->where('turno', $turma->turno)
            ->where('nome', $turma->nome)
            ->where('ano_letivo_id', $proximoAno->id)
            ->exists();

        if ($turmaExistente) {
            return back()->with('error', 'Já existe uma turma com este nome na classe seguinte.');
        }

        if ($turma->coordenador_turma_id) {
            $diretorJaAlocado = Turma::where('coordenador_turma_id', $turma->coordenador_turma_id)
                ->where('ano_letivo_id', $proximoAno->id)
                ->exists();

            if ($diretorJaAlocado) {
                return back()->with('error', 'O director de turma já está atribuído a outra turma no próximo ano letivo.');
            }
        }

        // ----------------------------------------------------------------
        // Guarda: turma sem disciplinas
        // ----------------------------------------------------------------

        $turma->loadMissing(['disciplinas.cursos', 'curso.disciplinas.cursos']);

        $disciplinas      = $turma->disciplinas;
        $disciplinaIds    = $disciplinas->pluck('id');
        $totalDisciplinas = $disciplinaIds->count();

        if ($totalDisciplinas === 0) {
            return back()->with(
                'error',
                'A turma não tem disciplinas associadas. Não é possível determinar aprovações.'
            );
        }

        // ----------------------------------------------------------------
        // Identificar todos os alunos matriculados e classificá-los
        // ----------------------------------------------------------------

        $todosAlunos = $turma->alunos()
            ->wherePivotIn('status', ['matriculado', 'aprovado', ResultadoAlunoTurmaService::STATUS_RECURSO])
            ->get();

        if ($todosAlunos->isEmpty()) {
            return back()->with('warning', 'A turma não tem alunos matriculados ou em recurso.');
        }

        // Buscar contagens de aprovação por aluno — UMA query
        $notasFinaisPorAluno = Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->whereIn('disciplina_id', $disciplinaIds)
            ->get(['aluno_id', 'disciplina_id', 'cf', 'cfd', 'nota_recurso'])
            ->groupBy('aluno_id');

        // Buscar contagens de notas lançadas por aluno — para feedback
        // Classificar alunos com a regra final de transicao da turma.
        $aprovados     = collect();
        $reprovados    = collect();
        $emRecurso     = collect();
        $incompletos   = collect();
        $resultadoAlunoService = app(ResultadoAlunoTurmaService::class);

        foreach ($todosAlunos as $aluno) {
            $notasAluno = $notasFinaisPorAluno->get($aluno->id, collect());
            $resultado = $resultadoAlunoService->avaliar($turma, $disciplinas, $notasAluno);

            if ($resultado['status'] === ResultadoAlunoTurmaService::STATUS_PENDENTE) {
                $lancadas = $notasAluno->pluck('disciplina_id')->unique()->count();

                $incompletos->push([
                    'aluno'    => $aluno,
                    'lancadas' => $lancadas,
                    'faltam'   => $totalDisciplinas - $lancadas,
                ]);
            } elseif ($resultado['status'] === ResultadoAlunoTurmaService::STATUS_TRANSITA) {
                $aprovados->push($aluno);
            } elseif ($resultado['status'] === ResultadoAlunoTurmaService::STATUS_RECURSO) {
                $emRecurso->push($aluno);
            } else {
                $reprovados->push([
                    'aluno' => $aluno,
                ]);
            }
        }

        if ($aprovados->isEmpty()) {
            DB::transaction(function () use ($turma, $reprovados, $emRecurso, $notasFinaisPorAluno) {
                foreach ($reprovados as $dadosReprovado) {
                    $alunoReprovado = $dadosReprovado['aluno'];

                    $turma->alunos()->updateExistingPivot($alunoReprovado->id, [
                        'status' => 'reprovado',
                    ]);

                    $this->registarHistoricoAluno($turma, $alunoReprovado->id, $notasFinaisPorAluno, 'Registo automático no fecho anual da turma.');
                }

                if ($emRecurso->isNotEmpty()) {
                    $this->marcarAlunosEmRecurso($turma, $emRecurso);
                }

                foreach ($emRecurso as $alunoRecurso) {
                    $this->registarHistoricoAluno($turma, $alunoRecurso->id, $notasFinaisPorAluno, 'Registo automático no fecho anual da turma (aluno em recurso).');
                }
            });

            $mensagem = 'Nenhum aluno cumpre os critérios de aprovação.';

            if ($incompletos->isNotEmpty()) {
                $mensagem .= " {$incompletos->count()} aluno(s) com notas incompletas.";
            }

            if ($reprovados->isNotEmpty()) {
                $mensagem .= " {$reprovados->count()} aluno(s) reprovados.";
            }

            if ($emRecurso->isNotEmpty()) {
                $mensagem .= " {$emRecurso->count()} aluno(s) em recurso.";
            }

            return back()->with('warning', $mensagem);
        }

        // ----------------------------------------------------------------
        // Transacção: criar turma + mover alunos aprovados
        // ----------------------------------------------------------------

        $disciplinasDestino = $this->disciplinasParaClasseSeguinte($turma, (string) $novaClasse);

        if ($disciplinasDestino->isEmpty()) {
            return back()->with('error', 'Não há disciplinas configuradas para a classe seguinte neste curso.');
        }

        $novaTurma = DB::transaction(function () use (
        $turma, $novaClasse, $proximoAno, $aprovados, $reprovados, $emRecurso, $disciplinaIds, $disciplinasDestino, $notasFinaisPorAluno
        ) {
            $novaTurma = Turma::create([
                'nome'                 => $turma->nome,
                'classe'               => (string) $novaClasse,
                'curso_id'             => $turma->curso_id,
                'ano_letivo_id'        => $proximoAno->id,
                'coordenador_turma_id' => $turma->coordenador_turma_id,
                'capacidade'           => $turma->capacidade,
                'sala'                 => $turma->sala,
                'turno'                => $turma->turno,
                'ativo'                => true,
            ]);

            $novaTurma->disciplinas()->attach($disciplinasDestino->pluck('id'));

            $notasFinais = Nota::where('turma_id', $turma->id)
                ->where('ano_letivo_id', $turma->ano_letivo_id)
                ->whereIn('disciplina_id', $disciplinaIds)
                ->whereIn('aluno_id', $aprovados->pluck('id'))
                ->get()
                ->groupBy('aluno_id');


            foreach ($aprovados as $aluno) {
                $novaTurma->alunos()->attach($aluno->id, [
                    'data_matricula' => now(),
                    'status'         => 'matriculado',
                ]);

                $turma->alunos()->updateExistingPivot($aluno->id, [
                    'status' => 'aprovado',
                ]);

                foreach ($notasFinais->get($aluno->id, collect()) as $nota) {
                    HistoricoAcademico::updateOrCreate(
                        [
                            'aluno_id' => $aluno->id,
                            'turma_id' => $turma->id,
                            'disciplina_id' => $nota->disciplina_id,
                            'ano_letivo_id' => $turma->ano_letivo_id,
                        ],
                        [
                            'classe' => (string) $turma->classe,
                            'classificacao_final' => (float) ($nota->cfd_efetiva ?? $nota->ca ?? 0),
                            'resultado' => $nota->isAprovado() ? 'aprovado' : 'reprovado',
                            'observacoes' => 'Registo automático na promoção da turma.',
                            'data_conclusao' => now(),
                        ]
                    );
                }
            }

            foreach ($reprovados as $dadosReprovado) {
                $alunoReprovado = $dadosReprovado['aluno'];

                $turma->alunos()->updateExistingPivot($alunoReprovado->id, [
                    'status' => 'reprovado',
                ]);
                foreach ($notasFinaisPorAluno->get($alunoReprovado->id, collect()) as $nota) {
                    HistoricoAcademico::updateOrCreate(
                        [
                            'aluno_id' => $alunoReprovado->id,
                            'turma_id' => $turma->id,
                            'disciplina_id' => $nota->disciplina_id,
                            'ano_letivo_id' => $turma->ano_letivo_id,
                        ],
                        [
                            'classe' => (string) $turma->classe,
                            'classificacao_final' => (float) ($nota->cfd_efetiva ?? $nota->ca ?? 0),
                            'resultado' => $nota->isAprovado() ? 'aprovado' : 'reprovado',
                            'observacoes' => 'Registo automático no fecho anual da turma.',
                            'data_conclusao' => now(),
                        ]
                    );
                }
            }

            $this->marcarAlunosEmRecurso($turma, $emRecurso);
            foreach ($emRecurso as $alunoRecurso) {
                foreach ($notasFinaisPorAluno->get($alunoRecurso->id, collect()) as $nota) {
                    HistoricoAcademico::updateOrCreate(
                        [
                            'aluno_id' => $alunoRecurso->id,
                            'turma_id' => $turma->id,
                            'disciplina_id' => $nota->disciplina_id,
                            'ano_letivo_id' => $turma->ano_letivo_id,
                        ],
                        [
                            'classe' => (string) $turma->classe,
                            'classificacao_final' => (float) ($nota->cfd_efetiva ?? $nota->ca ?? 0),
                            'resultado' => $nota->isAprovado() ? 'aprovado' : 'reprovado',
                            'observacoes' => 'Registo automático no fecho anual da turma (aluno em recurso).',
                            'data_conclusao' => now(),
                        ]
                    );
                }
            }

            return $novaTurma;
        });

        // ----------------------------------------------------------------
        // Feedback detalhado
        // ----------------------------------------------------------------

        $partes = ["{$aprovados->count()} aluno(s) aprovado(s) e transferido(s)."];

        if ($reprovados->isNotEmpty()) {
            $partes[] = "{$reprovados->count()} reprovado(s).";
        }

        if ($emRecurso->isNotEmpty()) {
            $partes[] = "{$emRecurso->count()} em recurso.";
        }

        if ($incompletos->isNotEmpty()) {
            $partes[] = "{$incompletos->count()} com notas incompletas (não promovido(s)).";
        }

        return redirect()
            ->route('turmas.show', $novaTurma)
            ->with(
                'success',
                "Turma promovida para {$novaClasse}ª classe. " . implode(' ', $partes)
            );
    }

    private function marcarAlunosEmRecurso(Turma $turma, Collection $alunos): void
    {
        foreach ($alunos as $aluno) {
            $turma->alunos()->updateExistingPivot($aluno->id, [
                'status' => ResultadoAlunoTurmaService::STATUS_RECURSO,
            ]);
        }
    }

    private function registarHistoricoAluno(
        Turma $turma,
        int $alunoId,
        Collection $notasFinaisPorAluno,
        string $observacoes
    ): void {
        foreach ($notasFinaisPorAluno->get($alunoId, collect()) as $nota) {
            HistoricoAcademico::updateOrCreate(
                [
                    'aluno_id' => $alunoId,
                    'turma_id' => $turma->id,
                    'disciplina_id' => $nota->disciplina_id,
                    'ano_letivo_id' => $turma->ano_letivo_id,
                ],
                [
                    'classe' => (string) $turma->classe,
                    'classificacao_final' => (float) ($nota->cfd_efetiva ?? $nota->ca ?? 0),
                    'resultado' => $nota->isAprovado() ? 'aprovado' : 'reprovado',
                    'observacoes' => $observacoes,
                    'data_conclusao' => now(),
                ]
            );
        }
    }
    private function atualizarStatusAluno(Turma $turma, User $aluno, string $status, string $mensagemSucesso)
    {
        $statusPermitidos = ['transferido', 'desistente'];

        if (! in_array($status, $statusPermitidos, true)) {
            return back()->with('error', 'Estado da matrícula inválido.');
        }

        $pivot = DB::table('turma_aluno')
            ->where('turma_id', $turma->id)
            ->where('aluno_id', $aluno->id)
            ->first();

        if (! $pivot) {
            return back()->with('error', 'O aluno não está vinculado a esta turma.');
        }

        if (in_array($pivot->status, ['transferido', 'desistente'], true)) {
            return back()->with('warning', 'O aluno já se encontra inativo nesta turma.');
        }

        $turma->alunos()->updateExistingPivot($aluno->id, [
            'status' => $status,
        ]);

        return back()->with('success', $mensagemSucesso);
    }

    private function validarDisciplinasDaClasse(string $classe, array $disciplinasIds): ?string
    {
        if (empty($disciplinasIds)) {
            return null;
        }

        $disciplinasInvalidas = Disciplina::query()
            ->whereIn('id', $disciplinasIds)
            ->get()
            ->filter(fn (Disciplina $disciplina) => ! $disciplina->isLecionadaEm($classe))
            ->pluck('nome')
            ->values();

        if ($disciplinasInvalidas->isEmpty()) {
            return null;
        }

        return 'As seguintes disciplinas não estão configuradas para a '.$classe.'ª classe: '
            .$disciplinasInvalidas->implode(', ').'.';
    }

    private function disciplinasParaClasseSeguinte(Turma $turma, string $classe): \Illuminate\Support\Collection
    {
        $disciplinas = $turma->curso->disciplinas
            ->merge($turma->disciplinas)
            ->unique('id')
            ->values();

        return $disciplinas
            ->filter(fn (Disciplina $disciplina) => $disciplina->isLecionadaEm($classe))
            ->values();
    }

    private function montarNomeCompleto(array $dados): string
    {
        $cursoCodigo = isset($dados['curso_id']) ? (Curso::find($dados['curso_id'])?->codigo ?? 'CURSO') : 'CURSO';
        $classe = strtoupper(trim((string) ($dados['classe'] ?? 'XX')));
        $nome = strtoupper(trim((string) ($dados['nome'] ?? 'X')));
        $turno = strtoupper(trim((string) ($dados['turno'] ?? 'X')));

        return strtoupper(trim((string) $cursoCodigo)).$classe.$nome.$turno;
    }

    private function normalizarSala(?string $sala): ?string
    {
        $sala = trim((string) $sala);

        return $sala === '' ? null : mb_strtoupper($sala);
    }
}
