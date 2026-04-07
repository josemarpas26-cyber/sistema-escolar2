<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisciplinaController extends Controller
{
    /**
     * Listar disciplinas
     */
    public function index(Request $request)
    {
        $this->checkPermission('disciplinas.view');

        $query = Disciplina::with(['coordenador'])->withCount('turmas');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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
     * Formulario de criacao
     */
    public function create()
    {
        $this->checkPermission('disciplinas.create');

        $cursos = Curso::ativos()->orderBy('nome')->get();
        $professores = $this->professoresCoordenadores();

        return view('disciplinas.form', compact('cursos', 'professores'));
    }

    /**
     * Salvar nova disciplina
     */
    public function store(Request $request)
    {
        $this->checkPermission('disciplinas.create');

        $validated = $request->validate(
            $this->disciplinaRules(),
            $this->disciplinaMessages()
        );

        if (! $request->has('leciona_10')
            && ! $request->has('leciona_11')
            && ! $request->has('leciona_12')) {
            return back()
                ->withErrors(['leciona_10' => 'Selecione pelo menos uma classe onde a disciplina sera lecionada.'])
                ->withInput();
        }

        $validated['leciona_10'] = $request->has('leciona_10');
        $validated['leciona_11'] = $request->has('leciona_11');
        $validated['leciona_12'] = $request->has('leciona_12');
        $validated['disciplina_terminal'] = $request->has('disciplina_terminal');
        $validated['ativo'] = $request->has('ativo');

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
            'coordenador',
            'turmas' => fn ($q) => $q->with(['curso', 'anoLetivo'])->latest(),
            'atribuicoes' => fn ($q) => $q->with(['professor', 'turma'])->latest(),
            'cursos' => fn ($q) => $q->orderBy('nome'),
        ]);

        return view('disciplinas.show', compact('disciplina'));
    }

    /**
     * Formulario de edicao
     */
    public function edit(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $disciplina->load('cursos');
        $cursos = Curso::ativos()->orderBy('nome')->get();
        $professores = $this->professoresCoordenadores();

        return view('disciplinas.form', compact('disciplina', 'cursos', 'professores'));
    }

    /**
     * Atualizar disciplina
     */
    public function update(Request $request, Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $validated = $request->validate(
            $this->disciplinaRules($disciplina),
            $this->disciplinaMessages()
        );

        if (! $request->has('leciona_10')
            && ! $request->has('leciona_11')
            && ! $request->has('leciona_12')) {
            return back()
                ->withErrors(['leciona_10' => 'Selecione pelo menos uma classe onde a disciplina sera lecionada.'])
                ->withInput();
        }

        $validated['leciona_10'] = $request->has('leciona_10');
        $validated['leciona_11'] = $request->has('leciona_11');
        $validated['leciona_12'] = $request->has('leciona_12');
        $validated['disciplina_terminal'] = $request->has('disciplina_terminal');
        $validated['ativo'] = $request->has('ativo');

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

        if ($disciplina->turmas()->exists()) {
            return back()->with('error', 'Nao e possivel deletar uma disciplina com turmas associadas!');
        }

        if ($disciplina->notas()->exists()) {
            return back()->with('error', 'Nao e possivel deletar uma disciplina com notas lancadas!');
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

        $disciplina->update(['ativo' => ! $disciplina->ativo]);

        $status = $disciplina->ativo ? 'ativada' : 'desativada';

        return back()->with('success', "Disciplina {$status} com sucesso!");
    }

    private function professoresCoordenadores()
    {
        return User::professores()
            ->ativos()
            ->orderBy('name')
            ->get();
    }

    private function disciplinaRules(?Disciplina $disciplina = null): array
    {
        return [
            'nome' => 'required|string|max:255',
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('disciplinas', 'codigo')->ignore($disciplina?->id),
            ],
            'descricao' => 'nullable|string|max:500',
            'coordenador_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('disciplinas', 'coordenador_id')->ignore($disciplina?->id),
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $professorValido = User::professores()
                        ->ativos()
                        ->whereKey($value)
                        ->exists();

                    if (! $professorValido) {
                        $fail('Selecione um professor ativo para coordenar a disciplina.');
                    }
                },
            ],
            'cursos_terminal' => 'nullable|array',
            'cursos_terminal.*' => 'nullable|in:10,11,12',
        ];
    }

    private function disciplinaMessages(): array
    {
        return [
            'coordenador_id.unique' => 'Este professor ja coordena outra disciplina.',
            'coordenador_id.exists' => 'O coordenador selecionado nao existe.',
        ];
    }

    /**
     * Sincroniza a configuracao de ano terminal por curso.
     *
     * Agora processa todos os cursos recebidos no form, mantendo ano_terminal = null
     * quando nao foi especificado, e so faz sync() se houver dados.
     *
     * @param  Disciplina  $disciplina
     * @param  array  $cursosTerminal  Array [curso_id => ano_terminal]
     */
    private function syncTerminalPorCurso(Disciplina $disciplina, array $cursosTerminal): void
    {
        $syncData = [];

        foreach ($cursosTerminal as $cursoId => $anoTerminal) {
            $syncData[(int) $cursoId] = [
                'ano_terminal' => $anoTerminal ? (int) $anoTerminal : null,
            ];
        }

        if (! empty($syncData)) {
            $disciplina->cursos()->sync($syncData);
        }
    }
}
