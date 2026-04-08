<?php

namespace App\Http\Controllers;

use App\Models\Turma;
use App\Models\Curso;
use App\Models\AnoLetivo;
use App\Models\User;
use App\Models\Disciplina;
use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\HistoricoAcademico;

class TurmaController extends Controller
{
    /**
     * Listar turmas
     */
    public function index(Request $request)
    {
        $this->checkPermission('turmas.view');

        $query = Turma::with(['curso', 'anoLetivo', 'coordenador'])
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
        $cursos = Curso::ativos()->get();
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

    $cursos = Curso::ativos()->get();
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

        // 1️⃣ Verificar se existe ano letivo ativo
        $anoAtivo = AnoLetivo::ativo()->first();

        if (!$anoAtivo) {
            return back()
                ->withInput()
                ->with('error', 'Não existe ano letivo ativo. Ative um ano letivo antes de criar turmas.');
        }

        // 2️⃣ Validar dados
        $validated = $request->validate([
            'nome' => ['required', 'string', 'size:1', 'regex:/^[A-Za-z]$/'],
            'classe' => 'required|in:10,11,12',
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
            'turno' => ['required', 'in:M,T'],
            'disciplinas' => 'nullable|array',
            'disciplinas.*' => 'exists:disciplinas,id',
        ], [
            'nome.required' => 'O nome da turma é obrigatório.',
            'nome.size' => 'O nome da turma deve ter exactamente 1 letra.',
            'nome.regex' => 'O nome da turma deve ser uma letra de A a Z.',
            'turno.required' => 'O turno é obrigatório.',
            'turno.in' => 'O turno deve ser Manhã (M) ou Tarde (T).',
        ]);

            $validated['nome'] = strtoupper(trim($validated['nome']));

        // 3️⃣ Garantir que o ano selecionado é o ativo
        if ($validated['ano_letivo_id'] != $anoAtivo->id) {
            return back()
                ->withInput()
                ->with('error', 'Só é permitido criar turmas no ano letivo ativo.');
        }

        // 4️⃣ Verificar se já existe turma com mesmo nome no mesmo ano
        $turmaExiste = Turma::where('nome', $validated['nome'])
            ->where('ano_letivo_id', $validated['ano_letivo_id'])
            ->exists();

        if ($turmaExiste) {
            return back()
                ->withInput()
                ->with('error', 'Já existe uma turma com este nome neste ano letivo.');
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
            'curso',
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

        $cursos = Curso::ativos()->get();
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

        $validated = $request->validate([
            'nome' => ['required', 'string', 'size:1', 'regex:/^[A-Za-z]$/'],
            'classe' => 'required|in:10,11,12',
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
        ]);

            $validated['nome'] = strtoupper(trim($validated['nome']));

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
            return back()->with('error', 'Não é possível deletar uma turma com alunos matriculados!');
        }

        // Verificar se há notas lançadas
        if ($turma->notas()->count() > 0) {
            return back()->with('error', 'Não é possível deletar uma turma com notas lançadas!');
        }

        $turma->delete();

        return redirect()
            ->route('turmas.index')
            ->with('success', 'Turma deletada com sucesso!');
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

        // Atualizar status ao invés de deletar (manter histórico)
        $turma->alunos()->updateExistingPivot($aluno->id, [
            'status' => 'transferido',
        ]);

        return back()->with('success', 'Aluno removido da turma!');
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

        if ($novaClasse > 12) {
            return back()->with('error', 'Alunos da 12ª classe não podem ser promovidos.');
        }

        $turmaExistente = Turma::where('curso_id', $turma->curso_id)
            ->where('classe', (string) $novaClasse)
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

        $disciplinaIds    = $turma->disciplinas()->pluck('disciplinas.id');
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
            ->wherePivot('status', 'matriculado')
            ->get();

        if ($todosAlunos->isEmpty()) {
            return back()->with('warning', 'A turma não tem alunos matriculados.');
        }

        // Buscar contagens de aprovação por aluno — UMA query
        $contagensAprovados = Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->whereIn('disciplina_id', $disciplinaIds)
            ->where('cfd', '>=', 10)
            ->selectRaw('aluno_id, COUNT(DISTINCT disciplina_id) as aprovadas')
            ->groupBy('aluno_id')
            ->pluck('aprovadas', 'aluno_id');

        // Buscar contagens de notas lançadas por aluno — para feedback
        $contagensLancadas = Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->whereIn('disciplina_id', $disciplinaIds)
            ->selectRaw('aluno_id, COUNT(DISTINCT disciplina_id) as lancadas')
            ->groupBy('aluno_id')
            ->pluck('lancadas', 'aluno_id');

        // Classificar alunos
        $aprovados     = collect();
        $reprovados    = collect();
        $incompletos   = collect();

        foreach ($todosAlunos as $aluno) {
            $lancadas  = $contagensLancadas->get($aluno->id, 0);
            $aprovadas = $contagensAprovados->get($aluno->id, 0);

            if ($lancadas < $totalDisciplinas) {
                // Faltam notas em alguma(s) disciplina(s)
                $incompletos->push([
                    'aluno'    => $aluno,
                    'lancadas' => $lancadas,
                    'faltam'   => $totalDisciplinas - $lancadas,
                ]);
            } elseif ($aprovadas < $totalDisciplinas) {
                // Tem todas as notas mas reprovou em alguma(s)
                $reprovados->push([
                    'aluno'      => $aluno,
                    'aprovadas'  => $aprovadas,
                    'reprovadas' => $totalDisciplinas - $aprovadas,
                ]);
            } else {
                $aprovados->push($aluno);
            }
        }

        if ($aprovados->isEmpty()) {
            $mensagem = 'Nenhum aluno cumpre os critérios de aprovação.';

            if ($incompletos->isNotEmpty()) {
                $mensagem .= " {$incompletos->count()} aluno(s) com notas incompletas.";
            }

            if ($reprovados->isNotEmpty()) {
                $mensagem .= " {$reprovados->count()} aluno(s) reprovados.";
            }

            return back()->with('warning', $mensagem);
        }

        // ----------------------------------------------------------------
        // Transacção: criar turma + mover alunos aprovados
        // ----------------------------------------------------------------

        $novaTurma = DB::transaction(function () use (
        $turma, $novaClasse, $proximoAno, $aprovados, $disciplinaIds
        ) {
            $novaTurma = Turma::create([
                'nome'                 => $turma->nome,
                'classe'               => (string) $novaClasse,
                'curso_id'             => $turma->curso_id,
                'ano_letivo_id'        => $proximoAno->id,
                'coordenador_turma_id' => $turma->coordenador_turma_id,
                'capacidade'           => $turma->capacidade,
                'ativo'                => true,
            ]);

            $novaTurma->disciplinas()->attach($turma->disciplinas->pluck('id'));

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
                    'status' => 'concluido',
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
                            'classificacao_final' => (float) ($nota->cfd ?? $nota->ca ?? 0),
                            'resultado' => (($nota->cfd ?? 0) >= 10) ? 'aprovado' : 'reprovado',
                            'observacoes' => 'Registo automático na promoção da turma.',
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
}
