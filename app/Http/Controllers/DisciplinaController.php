<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use Illuminate\Http\Request;

class DisciplinaController extends Controller
{
    /**
     * Listar disciplinas
     */
    public function index(Request $request)
    {
        $this->checkPermission('disciplinas.view');

        $query = Disciplina::withCount('turmas');

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

        $disciplinas = $query->paginate(15);

        return view('disciplinas.index', compact('disciplinas'));
    }

    /**
     * Formulário de criação
     */
    public function create()
    {
        $this->checkPermission('disciplinas.create');

        return view('disciplinas.create');
    }

    /**
     * Salvar nova disciplina
     */
    public function store(Request $request)
    {
        $this->checkPermission('disciplinas.create');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:10|unique:disciplinas,codigo',
            'descricao' => 'nullable|string',
            'leciona_10' => 'boolean',
            'leciona_11' => 'boolean',
            'leciona_12' => 'boolean',
            'disciplina_terminal' => 'boolean',
            'ativo' => 'boolean',
        ]);
        $validated['leciona_10'] = $request->has('leciona_10');
        $validated['leciona_11'] = $request->has('leciona_11');
        $validated['leciona_12'] = $request->has('leciona_12');
        $validated['disciplina_terminal'] = $request->has('disciplina_terminal');
        $validated['ativo'] = $request->has('ativo');
        
        $disciplina = Disciplina::create($validated);

        return redirect()
            ->route('disciplinas.show', $disciplina)
            ->with('success', 'Disciplina criada com sucesso!');
    }

    /**
     * Exibir disciplina
     */
    public function show(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.view');

        $disciplina->load([
            'turmas' => fn($q) => $q->with(['curso', 'anoLetivo'])->latest(),
            'atribuicoes' => fn($q) => $q->with(['professor', 'turma'])->latest(),
        ]);

        return view('disciplinas.show', compact('disciplina'));
    }

    /**
     * Formulário de edição
     */
    public function edit(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        return view('disciplinas.edit', compact('disciplina'));
    }

    /**
     * Atualizar disciplina
     */
    public function update(Request $request, Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:10|unique:disciplinas,codigo,' . $disciplina->id,
            'descricao' => 'nullable|string',
            'leciona_10' => 'boolean',
            'leciona_11' => 'boolean',
            'leciona_12' => 'boolean',
            'disciplina_terminal' => 'boolean',
            'ativo' => 'boolean',
        ]);

        $disciplina->update($validated);

        return redirect()
            ->route('disciplinas.show', $disciplina)
            ->with('success', 'Disciplina atualizada com sucesso!');
    }

    /**
     * Deletar disciplina
     */
    public function destroy(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.delete');

        // Verificar se há turmas ou notas associadas
        if ($disciplina->turmas()->count() > 0) {
            return back()->with('error', 'Não é possível deletar uma disciplina com turmas associadas!');
        }

        if ($disciplina->notas()->count() > 0) {
            return back()->with('error', 'Não é possível deletar uma disciplina com notas lançadas!');
        }

        $disciplina->delete();

        return redirect()
            ->route('disciplinas.index')
            ->with('success', 'Disciplina deletada com sucesso!');
    }

    /**
     * Ativar/Desativar disciplina
     */
    public function toggleStatus(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $disciplina->update(['ativo' => !$disciplina->ativo]);

        $status = $disciplina->ativo ? 'ativada' : 'desativada';
        
        return back()->with('success', "Disciplina {$status} com sucesso!");
    }
}
