<?php

namespace App\Http\Controllers;

use App\Models\Curso;
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

        $cursos = Curso::ativos()->orderBy('nome')->get();

        return view('disciplinas.form', compact('cursos'));
    }

    /**
     * Salvar nova disciplina
     */
    public function store(Request $request)
    {
        $this->checkPermission('disciplinas.create');

        // BUG CORRIGIDO 1: Removida validação 'boolean' dos checkboxes
        // Checkboxes desmarcados não enviam valor, então a validação falhava
        $validated = $request->validate([
            'nome'              => 'required|string|max:255',
            'codigo'            => 'required|string|max:10|unique:disciplinas,codigo',
            'descricao'         => 'nullable|string|max:500',
            'cursos_terminal'   => 'nullable|array',
            'cursos_terminal.*' => 'nullable|in:10,11,12',
        ]);

        // BUG CORRIGIDO 3: Validação de pelo menos uma classe
        if (!$request->has('leciona_10') && 
            !$request->has('leciona_11') && 
            !$request->has('leciona_12')) {
            return back()
                ->withErrors(['leciona_10' => 'Selecione pelo menos uma classe onde a disciplina será lecionada.'])
                ->withInput();
        }

        // Adicionar booleans manualmente (checkboxes só enviam quando marcados)
        $validated['leciona_10']          = $request->has('leciona_10');
        $validated['leciona_11']          = $request->has('leciona_11');
        $validated['leciona_12']          = $request->has('leciona_12');
        $validated['disciplina_terminal'] = $request->has('disciplina_terminal');
        $validated['ativo']               = $request->has('ativo');
        
        $disciplina = Disciplina::create($validated);

        $this->syncTerminalPorCurso($disciplina, $request->input('cursos_terminal', []));

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
            'turmas'      => fn($q) => $q->with(['curso', 'anoLetivo'])->latest(),
            'atribuicoes' => fn($q) => $q->with(['professor', 'turma'])->latest(),
            'cursos'      => fn($q) => $q->orderBy('nome'),
        ]);

        return view('disciplinas.show', compact('disciplina'));
    }

    /**
     * Formulário de edição
     */
    public function edit(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $disciplina->load('cursos');
        $cursos = Curso::ativos()->orderBy('nome')->get();

        return view('disciplinas.form', compact('disciplina', 'cursos'));
    }

    /**
     * Atualizar disciplina
     */
    public function update(Request $request, Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        // BUG CORRIGIDO 1: Removida validação 'boolean' dos checkboxes
        $validated = $request->validate([
            'nome'              => 'required|string|max:255',
            'codigo'            => 'required|string|max:10|unique:disciplinas,codigo,' . $disciplina->id,
            'descricao'         => 'nullable|string|max:500',
            'cursos_terminal'   => 'nullable|array',
            'cursos_terminal.*' => 'nullable|in:10,11,12',
        ]);

        // BUG CORRIGIDO 3: Validação de pelo menos uma classe
        if (!$request->has('leciona_10') && 
            !$request->has('leciona_11') && 
            !$request->has('leciona_12')) {
            return back()
                ->withErrors(['leciona_10' => 'Selecione pelo menos uma classe onde a disciplina será lecionada.'])
                ->withInput();
        }

        // Adicionar booleans manualmente
        $validated['leciona_10']          = $request->has('leciona_10');
        $validated['leciona_11']          = $request->has('leciona_11');
        $validated['leciona_12']          = $request->has('leciona_12');
        $validated['disciplina_terminal'] = $request->has('disciplina_terminal');
        $validated['ativo']               = $request->has('ativo');

        $disciplina->update($validated);

        $this->syncTerminalPorCurso($disciplina, $request->input('cursos_terminal', []));

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

        // BUG CORRIGIDO 4: exists() é muito mais rápido que count() > 0
        // exists() para na primeira row encontrada, count() conta todas
        if ($disciplina->turmas()->exists()) {
            return back()->with('error', 'Não é possível deletar uma disciplina com turmas associadas!');
        }

        if ($disciplina->notas()->exists()) {
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

    /**
     * Sincroniza a configuração de ano terminal por curso.
     *
     * BUG CORRIGIDO 2: O sync([]) vazio apagava TODAS as relações.
     * Agora processa todos os cursos recebidos no form, mantendo ano_terminal = null
     * quando não foi especificado, e só faz sync() se houver dados.
     *
     * @param  Disciplina  $disciplina
     * @param  array  $cursosTerminal  Array [curso_id => ano_terminal]
     */
    private function syncTerminalPorCurso(Disciplina $disciplina, array $cursosTerminal): void
    {
        $syncData = [];

        foreach ($cursosTerminal as $cursoId => $anoTerminal) {
            // BUG CORRIGIDO 2: Antes, quando $anoTerminal era vazio, o continue
            // fazia com que o curso não fosse incluído no sync(), resultando em
            // sua remoção da tabela pivot. Agora, incluímos com ano_terminal = null
            // para manter a relação mas sem ano definido.
            $syncData[(int) $cursoId] = [
                'ano_terminal' => $anoTerminal ? (int) $anoTerminal : null
            ];
        }

        // Só faz sync se houver dados (evita apagar tudo com sync([]))
        if (!empty($syncData)) {
            $disciplina->cursos()->sync($syncData);
        }
    }
}