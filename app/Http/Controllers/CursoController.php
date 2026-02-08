<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /**
     * Listar cursos
     */
    public function index(Request $request)
    {
        $this->checkPermission('cursos.view');

        $query = Curso::with('coordenador')->withCount('turmas');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $cursos = $query->paginate(15);

        return view('cursos.index', compact('cursos'));
    }

    /**
     * Formulário de criação
     */
    public function create()
    {
        $this->checkPermission('cursos.create');

        // Apenas professores podem ser coordenadores
        $professores = User::professores()->ativos()->get();

        return view('cursos.create', compact('professores'));
    }

    /**
     * Salvar novo curso
     */
    public function store(Request $request)
    {
        $this->checkPermission('cursos.create');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:10|unique:cursos,codigo',
            'descricao' => 'nullable|string',
            'coordenador_id' => 'nullable|exists:users,id',
            'ativo' => 'boolean',
        ]);

        $curso = Curso::create($validated);

        return redirect()
            ->route('cursos.show', $curso)
            ->with('success', 'Curso criado com sucesso!');
    }

    /**
     * Exibir curso
     */
    public function show(Curso $curso)
    {
        $this->checkPermission('cursos.view');

        $curso->load([
            'coordenador',
            'turmas' => fn($q) => $q->with(['anoLetivo', 'alunos'])->latest(),
        ]);

        return view('cursos.show', compact('curso'));
    }

    /**
     * Formulário de edição
     */
    public function edit(Curso $curso)
    {
        $this->checkPermission('cursos.edit');

        $professores = User::professores()->ativos()->get();

        return view('cursos.edit', compact('curso', 'professores'));
    }

    /**
     * Atualizar curso
     */
    public function update(Request $request, Curso $curso)
    {
        $this->checkPermission('cursos.edit');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:10|unique:cursos,codigo,' . $curso->id,
            'descricao' => 'nullable|string',
            'coordenador_id' => 'nullable|exists:users,id',
            'ativo' => 'boolean',
        ]);

        $curso->update($validated);

        return redirect()
            ->route('cursos.show', $curso)
            ->with('success', 'Curso atualizado com sucesso!');
    }

    /**
     * Deletar curso
     */
    public function destroy(Curso $curso)
    {
        $this->checkPermission('cursos.delete');

        // Verificar se há turmas associadas
        if ($curso->turmas()->count() > 0) {
            return back()->with('error', 'Não é possível deletar um curso com turmas associadas!');
        }

        $curso->delete();

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso deletado com sucesso!');
    }

    /**
     * Ativar/Desativar curso
     */
    public function toggleStatus(Curso $curso)
    {
        $this->checkPermission('cursos.edit');

        $curso->update(['ativo' => !$curso->ativo]);

        $status = $curso->ativo ? 'ativado' : 'desativado';
        
        return back()->with('success', "Curso {$status} com sucesso!");
    }
}
