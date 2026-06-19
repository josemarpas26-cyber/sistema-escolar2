<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisciplinaController extends Controller
{
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

    public function create()
    {
        $this->checkPermission('disciplinas.create');

        $cursos = Curso::ativos()->orderBy('nome')->get();
        $professores = $this->professoresCoordenadores();

        return view('disciplinas.form', compact('cursos', 'professores'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('disciplinas.create');

        $validated = $request->validate($this->disciplinaRules(), $this->disciplinaMessages());

        $validated['leciona_10'] = false;
        $validated['leciona_11'] = false;
        $validated['leciona_12'] = false;
        $validated['leciona_13'] = false;
        $validated['disciplina_terminal'] = false;
        $validated['ativo'] = $request->has('ativo');

        $disciplina = Disciplina::create($validated);

        $this->syncTerminalPorCurso($disciplina, $request->input('cursos_terminal', []));

        return redirect()->route('disciplinas.show', $disciplina)->with('success', 'Disciplina criada com sucesso!');
    }

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

    public function edit(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $disciplina->load('cursos');
        $cursos = Curso::ativos()->orderBy('nome')->get();
        $professores = $this->professoresCoordenadores();

        return view('disciplinas.form', compact('disciplina', 'cursos', 'professores'));
    }

    public function update(Request $request, Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $validated = $request->validate($this->disciplinaRules($disciplina), $this->disciplinaMessages());

        $validated['leciona_10'] = false;
        $validated['leciona_11'] = false;
        $validated['leciona_12'] = false;
        $validated['leciona_13'] = false;
        $validated['disciplina_terminal'] = false;
        $validated['ativo'] = $request->has('ativo');

        $disciplina->update($validated);

        $this->syncTerminalPorCurso($disciplina, $request->input('cursos_terminal', []));

        return redirect()->route('disciplinas.show', $disciplina)->with('success', 'Disciplina atualizada com sucesso!');
    }

    public function destroy(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.delete');

        if ($disciplina->turmas()->exists()) {
            return back()->with('error', 'Não é possível eliminar uma disciplina com turmas associadas.');
        }

        if ($disciplina->notas()->exists()) {
            return back()->with('error', 'Não é possível eliminar uma disciplina com notas lançadas.');
        }

        $disciplina->delete();

        return redirect()->route('disciplinas.index')->with('success', 'Disciplina eliminada com sucesso!');
    }

    public function toggleStatus(Disciplina $disciplina)
    {
        $this->checkPermission('disciplinas.edit');

        $disciplina->update(['ativo' => ! $disciplina->ativo]);

        $status = $disciplina->ativo ? 'ativada' : 'desativada';

        return back()->with('success', "Disciplina {$status} com sucesso!");
    }

    private function professoresCoordenadores()
    {
        return User::professores()->ativos()->orderBy('name')->get();
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

                    $professorValido = User::professores()->ativos()->whereKey($value)->exists();

                    if (! $professorValido) {
                        $fail('Selecione um professor ativo para coordenar a disciplina.');
                    }
                },
            ],
            'cursos_terminal' => 'nullable|array',
            'cursos_terminal.*' => 'nullable|in:10,11,12,13',
        ];
    }

    private function disciplinaMessages(): array
    {
        return [
            'coordenador_id.unique' => 'Este professor já coordena outra disciplina.',
            'coordenador_id.exists' => 'O coordenador selecionado não existe.',
        ];
    }

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
