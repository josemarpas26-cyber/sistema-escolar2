<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\MetaDisciplina;
use App\Models\Nota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MetaDisciplinaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $aluno = $request->user();

        abort_unless($aluno?->isAluno(), 403);

        $anoLetivo = AnoLetivo::ativo()->firstOrFail();

        $data = $request->validate([
            'disciplina_id' => ['required', 'exists:disciplinas,id'],
            'meta_nota' => ['required', 'numeric', 'min:0', 'max:20'],
            'data_conclusao_prevista' => ['nullable', 'date'],
        ]);

        $temNota = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->where('disciplina_id', $data['disciplina_id'])
            ->exists();

        if (!$temNota) {
            return back()->withErrors([
                'disciplina_id' => 'Só é possível definir meta para disciplinas onde já existe pauta inicial.',
            ]);
        }

        MetaDisciplina::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->where('disciplina_id', $data['disciplina_id'])
            ->where('status', 'ativa')
            ->update(['status' => 'desativada']);

        MetaDisciplina::create([
            'aluno_id' => $aluno->id,
            'disciplina_id' => $data['disciplina_id'],
            'ano_letivo_id' => $anoLetivo->id,
            'meta_nota' => $data['meta_nota'],
            'data_definicao' => now()->toDateString(),
            'data_conclusao_prevista' => $data['data_conclusao_prevista'] ?? null,
            'status' => 'ativa',
        ]);

        return back()->with('success', 'Meta guardada com sucesso.');
    }

    public function desativar(MetaDisciplina $meta): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user?->isAluno() && $meta->aluno_id === $user->id, 403);

        $meta->update(['status' => 'desativada']);

        return back()->with('success', 'Meta desativada.');
    }
}
