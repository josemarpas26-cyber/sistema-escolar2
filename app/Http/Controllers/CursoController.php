<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'codigo' => [
                'required',
                'string',
                'min:2',
                'max:10',
                'regex:/^[A-Z]+$/',
                Rule::unique('cursos', 'codigo'),
            ],
            'descricao' => 'nullable|string',
            'coordenador_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('cursos')->where(function ($query) use ($request) {
                    return $query->where('ativo', $request->boolean('ativo', true));
                }),
            ],
            'ativo' => 'boolean',
                    ], [
            'codigo.required' => 'O código do curso é obrigatório.',
            'codigo.min' => 'O código deve ter pelo menos 2 letras.',
            'codigo.max' => 'O código não pode ter mais de 10 letras.',
            'codigo.regex' => 'O código deve conter apenas letras de A a Z.',
            'codigo.unique' => 'Este código já está a ser usado por outro curso.',
        ]);

        $validated['codigo'] = strtoupper(trim($validated['codigo']));
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
             'codigo' => [
                'required',
                'string',
                'min:2',
                'max:10',
                'regex:/^[A-Z]+$/',
                Rule::unique('cursos', 'codigo')->ignore($curso->id),
            ],
            'descricao' => 'nullable|string',
            'coordenador_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('cursos')
                    ->ignore($curso->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('ativo', $request->boolean('ativo', true));
                    }),
            ],
            'ativo' => 'boolean',
                    ], [
            'codigo.required' => 'O código do curso é obrigatório.',
            'codigo.min' => 'O código deve ter pelo menos 2 letras.',
            'codigo.max' => 'O código não pode ter mais de 10 letras.',
            'codigo.regex' => 'O código deve conter apenas letras de A a Z.',
            'codigo.unique' => 'Este código já está a ser usado por outro curso.',
        ]);

        $validated['codigo'] = strtoupper(trim($validated['codigo']));
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
