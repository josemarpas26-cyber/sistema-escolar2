<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\CalendarioEvento;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarioController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (! $user->isProfessor() && ! $user->isAluno()) {
            abort(403, 'Apenas professores e alunos podem aceder ao calendário.');
        }

        $anoLetivo = AnoLetivo::ativo()->first();

        $turmas = $user->isProfessor()
            ? $this->turmasDoProfessor($user->id, $anoLetivo?->id)
            : $this->turmasDoAluno($user->id, $anoLetivo?->id);

        $turmaId = $request->integer('turma_id');

        if (! $turmaId && $turmas->isNotEmpty()) {
            $turmaId = (int) $turmas->first()->id;
        }

        if ($turmaId && ! $turmas->pluck('id')->contains($turmaId)) {
            abort(403, 'Não tem acesso ao calendário desta turma.');
        }

        $mesSelecionado = $request->input('mes', now()->format('Y-m'));
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        if (preg_match('/^\d{4}-\d{2}$/', $mesSelecionado)) {
            $inicioMes = now()->setDate((int) substr($mesSelecionado, 0, 4), (int) substr($mesSelecionado, 5, 2), 1)->startOfMonth();
            $fimMes = (clone $inicioMes)->endOfMonth();
        }

        $eventos = collect();
        if ($turmaId) {
            $eventos = CalendarioEvento::query()
                ->with(['professor', 'turma'])
                ->where('turma_id', $turmaId)
                ->where(function ($query) use ($inicioMes, $fimMes) {
                    $query->whereBetween('inicio', [$inicioMes, $fimMes])
                        ->orWhereBetween('fim', [$inicioMes, $fimMes])
                        ->orWhere(function ($inner) use ($inicioMes, $fimMes) {
                            $inner->where('inicio', '<=', $inicioMes)
                                ->where('fim', '>=', $fimMes);
                        });
                })
                ->orderBy('inicio')
                ->get();
        }

        $eventosPorDia = $eventos
            ->groupBy(fn (CalendarioEvento $evento) => $evento->inicio->toDateString());

        $primeiroDiaSemana = $inicioMes->copy()->startOfMonth()->dayOfWeekIso; // 1 = segunda
        $totalDiasMes = $inicioMes->daysInMonth;
        $celulasCalendario = collect();

        for ($indice = 1; $indice < $primeiroDiaSemana; $indice++) {
            $celulasCalendario->push(null);
        }

        for ($dia = 1; $dia <= $totalDiasMes; $dia++) {
            $data = $inicioMes->copy()->day($dia);
            $chaveData = $data->toDateString();

            $celulasCalendario->push([
                'data' => $data,
                'eventos' => $eventosPorDia->get($chaveData, collect()),
            ]);
        }

        return view('calendario.index', [
            'turmas' => $turmas,
            'turmaId' => $turmaId,
            'mesSelecionado' => $mesSelecionado,
            'eventos' => $eventos,
            'inicioMes' => $inicioMes,
            'celulasCalendario' => $celulasCalendario,
            'isProfessor' => $user->isProfessor(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user->isProfessor()) {
            abort(403, 'Apenas professores podem criar eventos.');
        }

        $turmas = $this->turmasDoProfessor($user->id, AnoLetivo::ativo()->first()?->id);

        $dados = $request->validate([
            'turma_id' => ['required', 'integer', Rule::in($turmas->pluck('id')->all())],
            'titulo' => 'required|string|max:120',
            'descricao' => 'nullable|string|max:2000',
            'local' => 'nullable|string|max:120',
            'inicio' => 'required|date',
            'fim' => 'nullable|date|after_or_equal:inicio',
        ]);

        CalendarioEvento::create([
            'turma_id' => (int) $dados['turma_id'],
            'professor_id' => $user->id,
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? null,
            'local' => $dados['local'] ?? null,
            'inicio' => $dados['inicio'],
            'fim' => $dados['fim'] ?? null,
        ]);

        return back()->with('success', 'Evento criado com sucesso.');
    }

    public function update(Request $request, CalendarioEvento $evento)
    {
        $user = auth()->user();
        if (! $user->isProfessor()) {
            abort(403, 'Apenas professores podem editar eventos.');
        }

        if ((int) $evento->professor_id !== (int) $user->id) {
            abort(403, 'Só pode editar os seus próprios eventos.');
        }

        $turmas = $this->turmasDoProfessor($user->id, AnoLetivo::ativo()->first()?->id);

        $dados = $request->validate([
            'turma_id' => ['required', 'integer', Rule::in($turmas->pluck('id')->all())],
            'titulo' => 'required|string|max:120',
            'descricao' => 'nullable|string|max:2000',
            'local' => 'nullable|string|max:120',
            'inicio' => 'required|date',
            'fim' => 'nullable|date|after_or_equal:inicio',
        ]);

        $evento->update([
            'turma_id' => (int) $dados['turma_id'],
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? null,
            'local' => $dados['local'] ?? null,
            'inicio' => $dados['inicio'],
            'fim' => $dados['fim'] ?? null,
        ]);

        return back()->with('success', 'Evento atualizado com sucesso.');
    }

    public function destroy(CalendarioEvento $evento)
    {
        $user = auth()->user();
        if (! $user->isProfessor()) {
            abort(403, 'Apenas professores podem remover eventos.');
        }

        if ((int) $evento->professor_id !== (int) $user->id) {
            abort(403, 'Só pode remover os seus próprios eventos.');
        }

        $evento->delete();

        return back()->with('success', 'Evento removido com sucesso.');
    }

    private function turmasDoProfessor(int $professorId, ?int $anoLetivoId)
    {
        return Turma::query()
            ->whereHas('atribuicoes', function ($query) use ($professorId, $anoLetivoId) {
                $query->where('professor_id', $professorId);
                if ($anoLetivoId) {
                    $query->where('ano_letivo_id', $anoLetivoId);
                }
            })
            ->with('curso')
            ->orderBy('classe')
            ->orderBy('nome')
            ->get();
    }

    private function turmasDoAluno(int $alunoId, ?int $anoLetivoId)
    {
        return Turma::query()
            ->whereHas('alunos', function ($query) use ($alunoId) {
                $query->where('users.id', $alunoId)
                    ->where('turma_aluno.status', 'matriculado');
            })
            ->when($anoLetivoId, fn ($query) => $query->where('ano_letivo_id', $anoLetivoId))
            ->with('curso')
            ->orderBy('classe')
            ->orderBy('nome')
            ->get();
    }
}
