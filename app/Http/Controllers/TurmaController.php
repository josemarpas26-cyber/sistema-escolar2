<?php

namespace App\Http\Controllers;

use App\Models\Turma;
use App\Models\Curso;
use App\Models\AnoLetivo;
use App\Models\User;
use App\Models\Disciplina;
use Illuminate\Http\Request;

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

        $cursos = Curso::ativos()->get();
        $anosLetivos = AnoLetivo::orderBy('nome', 'desc')->get();
        $anoAtivo = AnoLetivo::ativo()->first();

        $professores = User::professores()->ativos()->get();
        $disciplinas = Disciplina::ativos()->get();

        return view('turmas.create', compact(
            'cursos',
            'anosLetivos',
            'anoAtivo',
            'professores',
            'disciplinas'
        ));
    }

    /**
     * Salvar nova turma
     */
    public function store(Request $request)
    {
        $this->checkPermission('turmas.create');

        $validated = $request->validate([
            'nome' => 'required|string|max:10',
            'classe' => 'required|in:10,11,12',
            'curso_id' => 'required|exists:cursos,id',
            'ano_letivo_id' => 'required|exists:anos_letivos,id',
            'coordenador_turma_id' => 'nullable|exists:users,id',
            'capacidade' => 'required|integer|min:1|max:100',
            'disciplinas' => 'nullable|array',
            'disciplinas.*' => 'exists:disciplinas,id',
        ]);

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
            'alunos' => fn($q) => $q->wherePivot('status', 'matriculado'),
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
            'nome' => 'required|string|max:10',
            'classe' => 'required|in:10,11,12',
            'curso_id' => 'required|exists:cursos,id',
            'ano_letivo_id' => 'required|exists:anos_letivos,id',
            'coordenador_turma_id' => 'nullable|exists:users,id',
            'capacidade' => 'required|integer|min:1|max:100',
            'ativo' => 'boolean',
            'disciplinas' => 'nullable|array',
            'disciplinas.*' => 'exists:disciplinas,id',
        ]);

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

        // Verificar se já está matriculado
        if ($turma->alunos()->where('aluno_id', $validated['aluno_id'])->exists()) {
            return back()->with('error', 'Aluno já está matriculado nesta turma!');
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

        // Verificar se já existe atribuição
        if ($turma->atribuicoes()
            ->where('professor_id', $validated['professor_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
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

        // Verificar se há próximo ano letivo
        $proximoAno = AnoLetivo::where('data_inicio', '>', $turma->anoLetivo->data_inicio)
        ->orderBy('data_inicio')
        ->first();

        
        if (!$proximoAno) {
            return back()->with('error', 'Não há ano letivo ativo para promover a turma!');
        }

        // Verificar se já existe turma promovida
        $novaClasse = (string) ((int) $turma->classe + 1);
        
        if ($novaClasse > 12) {
            return back()->with('error', 'Alunos da 12ª classe não podem ser promovidos!');
        }

        $turmaExistente = Turma::where('curso_id', $turma->curso_id)
            ->where('classe', $novaClasse)
            ->where('nome', $turma->nome)
            ->where('ano_letivo_id', $proximoAno->id)
            ->exists();

        if ($turmaExistente) {
            return back()->with('error', 'Já existe uma turma com este nome na classe seguinte!');
        }

        // Criar nova turma
        $novaTurma = Turma::create([
            'nome' => $turma->nome,
            'classe' => $novaClasse,
            'curso_id' => $turma->curso_id,
            'ano_letivo_id' => $proximoAno->id,
            'coordenador_turma_id' => $turma->coordenador_turma_id,
            'capacidade' => $turma->capacidade,
            'ativo' => true,
        ]);

        // Copiar disciplinas
        $novaTurma->disciplinas()->attach($turma->disciplinas->pluck('id'));

        // Promover alunos aprovados
        $alunosAprovados = $turma->alunos()
            ->wherePivot('status', 'matriculado')
            ->get();

        foreach ($alunosAprovados as $aluno) {
            $novaTurma->alunos()->attach($aluno->id, [
                'data_matricula' => now(),
                'status' => 'matriculado',
            ]);
            
            // Atualizar status na turma antiga
            $turma->alunos()->updateExistingPivot($aluno->id, [
                'status' => 'concluido',
            ]);
        }

        return redirect()
            ->route('turmas.show', $novaTurma)
            ->with('success', "Turma promovida com sucesso para {$novaClasse}ª classe!");
    }
}