<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\Disciplina;
use App\Models\User;
use App\Models\AnoLetivo;
use Illuminate\Http\Request;

class NotaController extends Controller
{
    /**
     * Painel de lançamento de notas (Professor)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            return $this->professorIndex($request);
        }

        if ($user->isSecretaria() || $user->isAdmin()) {
            return $this->secretariaIndex($request);
        }

        if ($user->isAluno()) {
            return $this->alunoIndex();
        }

        abort(403);
    }

    /**
     * Painel do professor
     */
    public function professorIndex(Request $request)
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        // Turmas e disciplinas que leciona
        $atribuicoes = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id)
            ->with(['turma', 'disciplina'])
            ->get();

        // Filtros
        $turmaId = $request->turma_id;
        $disciplinaId = $request->disciplina_id;

        $notas = null;
        $turma = null;
        $disciplina = null;

        if ($turmaId && $disciplinaId) {
            // Verificar se o professor leciona esta disciplina nesta turma
            $temAtribuicao = $atribuicoes
                ->where('turma_id', $turmaId)
                ->where('disciplina_id', $disciplinaId)
                ->isNotEmpty();

            if (!$temAtribuicao) {
                return back()->with('error', 'Você não leciona esta disciplina nesta turma!');
            }

            $turma = Turma::findOrFail($turmaId);
            $disciplina = Disciplina::findOrFail($disciplinaId);

            // Buscar ou criar notas
            $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();

            $notas = collect();
            foreach ($alunos as $aluno) {
                $nota = Nota::firstOrCreate(
                    [
                        'aluno_id' => $aluno->id,
                        'turma_id' => $turma->id,
                        'disciplina_id' => $disciplina->id,
                        'ano_letivo_id' => $anoLetivo->id,
                    ],
                    ['status' => 'em_lancamento']
                );
                
                $nota->aluno = $aluno;
                $notas->push($nota);
            }
        }

        return view('notas.professor', compact('atribuicoes', 'notas', 'turma', 'disciplina'));
    }

    /**
     * Painel da secretaria/admin
     */
    public function secretariaIndex(Request $request)
    {
        $this->checkPermission('notas.view_all');

        $anoLetivo = AnoLetivo::ativo()->first();
        $turmas = Turma::anoAtivo()->with('curso')->get();
        $disciplinas = Disciplina::ativos()->get();

        $turmaId = $request->turma_id;
        $disciplinaId = $request->disciplina_id;

        $notas = null;
        $turma = null;
        $disciplina = null;

        if ($turmaId && $disciplinaId) {
            $turma = Turma::findOrFail($turmaId);
            $disciplina = Disciplina::findOrFail($disciplinaId);

            $notas = Nota::where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->with('aluno')
                ->get();
        }

        return view('notas.secretaria', compact('turmas', 'disciplinas', 'notas', 'turma', 'disciplina'));
    }

    /**
     * Painel do aluno
     */
public function alunoIndex()
{
    $this->checkPermission('notas.view_own');

    $aluno = auth()->user();
    $anoLetivo = AnoLetivo::ativo()->first();

    $notas = Nota::where('aluno_id', $aluno->id)
        ->where('ano_letivo_id', $anoLetivo?->id)
        ->with(['disciplina', 'turma'])
        ->get();

    // Turma atual (pegamos da primeira nota)
    $turmaAtual = $notas->first()?->turma;

    // Média geral (ignora null automaticamente)
    $mediaGeral = $notas->avg('cfd') ?? 0;

    // Aprovações e reprovações
    $aprovacoes = $notas->filter(fn($n) => $n->cfd !== null && $n->cfd >= 10)->count();
    $reprovacoes = $notas->filter(fn($n) => $n->cfd !== null && $n->cfd < 10)->count();

    return view('notas.aluno', compact(
        'notas',
        'turmaAtual',
        'mediaGeral',
        'aprovacoes',
        'reprovacoes'
    ));
}


    /**
     * Lançar notas (1º Trimestre)
     */
    public function lancarTrimestre1(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac1' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp1' => 'nullable|numeric|min:0|max:20',
            'notas.*.pt1' => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            // Se for professor, verificar se pode editar
            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            $nota->update([
                'mac1' => $notaData['mac1'] ?? null,
                'pp1' => $notaData['pp1'] ?? null,
                'pt1' => $notaData['pt1'] ?? null,
            ]);

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 1º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar notas (2º Trimestre)
     */
    public function lancarTrimestre2(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac2' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp2' => 'nullable|numeric|min:0|max:20',
            'notas.*.pt2' => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            $nota->update([
                'mac2' => $notaData['mac2'] ?? null,
                'pp2' => $notaData['pp2'] ?? null,
                'pt2' => $notaData['pt2'] ?? null,
            ]);

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 2º trimestre lançadas com sucesso!');
    }

    /**
     * Lançar notas (3º Trimestre)
     */
    public function lancarTrimestre3(Request $request)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'notas' => 'required|array',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac3' => 'nullable|numeric|min:0|max:20',
            'notas.*.pp3' => 'nullable|numeric|min:0|max:20',
            'notas.*.pg' => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['notas'] as $notaData) {
            $nota = Nota::findOrFail($notaData['id']);

            if ($user->isProfessor()) {
                $this->verificarPermissaoProfessor($nota);
            }

            $nota->update([
                'mac3' => $notaData['mac3'] ?? null,
                'pp3' => $notaData['pp3'] ?? null,
                'pg' => $notaData['pg'] ?? null,
            ]);

            $nota->recalcular();
            $nota->save();
        }

        return back()->with('success', 'Notas do 3º trimestre lançadas com sucesso!');
    }

    /**
     * Editar nota individual (modal/form)
     */
    public function edit(Nota $nota)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->verificarPermissaoProfessor($nota);
        } else {
            $this->checkPermission('notas.editar');
        }

        $nota->load(['aluno', 'turma', 'disciplina']);

        return view('notas.edit', compact('nota'));
    }

    /**
     * Atualizar nota individual
     */
    public function update(Request $request, Nota $nota)
    {
        $user = auth()->user();

        if ($user->isProfessor()) {
            $this->checkPermission('notas.lancar');
            $this->verificarPermissaoProfessor($nota);
        } else {
            $this->checkPermission('notas.editar');
        }

        $validated = $request->validate([
            'mac1' => 'nullable|numeric|min:0|max:20',
            'pp1' => 'nullable|numeric|min:0|max:20',
            'pt1' => 'nullable|numeric|min:0|max:20',
            'mac2' => 'nullable|numeric|min:0|max:20',
            'pp2' => 'nullable|numeric|min:0|max:20',
            'pt2' => 'nullable|numeric|min:0|max:20',
            'mac3' => 'nullable|numeric|min:0|max:20',
            'pp3' => 'nullable|numeric|min:0|max:20',
            'pg' => 'nullable|numeric|min:0|max:20',
            'ca_10' => 'nullable|numeric|min:0|max:20',
            'ca_11' => 'nullable|numeric|min:0|max:20',
            'observacoes' => 'nullable|string',
        ]);

        $nota->update($validated);
        $nota->recalcular();
        $nota->save();

        return redirect()
            ->route('notas.index', [
                'turma_id' => $nota->turma_id,
                'disciplina_id' => $nota->disciplina_id,
            ])
            ->with('success', 'Nota atualizada com sucesso!');
    }

    /**
     * Importar CAs de anos anteriores (11ª e 12ª)
     */
    public function importarCAs(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        $turma = Turma::findOrFail($validated['turma_id']);
        $disciplina = Disciplina::findOrFail($validated['disciplina_id']);
        $anoLetivo = $turma->anoLetivo;

        if ($turma->classe == '10') {
            return back()->with('error', 'Não há CAs para importar na 10ª classe!');
        }

        // Buscar notas do ano/classe anterior
        $classeAnterior = (string) ((int) $turma->classe - 1);

        $alunosDaTurma = $turma->alunos()->wherePivot('status', 'matriculado')->get();

        $importados = 0;
        foreach ($alunosDaTurma as $aluno) {
            // Buscar nota da classe anterior
            $notaAnterior = Nota::where('aluno_id', $aluno->id)
                ->where('disciplina_id', $disciplina->id)
                ->whereHas('turma', fn($q) => $q->where('classe', $classeAnterior))
                ->whereNotNull('ca')
                ->first();

            if (!$notaAnterior) {
                continue;
            }

            // Atualizar nota atual
            $notaAtual = Nota::where('aluno_id', $aluno->id)
                ->where('turma_id', $turma->id)
                ->where('disciplina_id', $disciplina->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->first();

            if ($notaAtual) {
                if ($turma->classe == '11') {
                    $notaAtual->update(['ca_10' => $notaAnterior->ca]);
                } elseif ($turma->classe == '12') {
                    $notaAtual->update(['ca_11' => $notaAnterior->ca]);
                }
                
                $notaAtual->recalcular();
                $notaAtual->save();
                $importados++;
            }
        }

        return back()->with('success', "CAs importados com sucesso! ({$importados} alunos)");
    }

    /**
     * Verificar se professor pode editar esta nota
     */
    public function verificarPermissaoProfessor(Nota $nota): void
    {
        $professor = auth()->user();

        $temAtribuicao = $professor->atribuicoes()
            ->where('turma_id', $nota->turma_id)
            ->where('disciplina_id', $nota->disciplina_id)
            ->where('ano_letivo_id', $nota->ano_letivo_id)
            ->exists();

        if (!$temAtribuicao) {
            abort(403, 'Você não tem permissão para editar esta nota!');
        }
    }

    /**
     * Finalizar lançamento de notas
     */
    public function finalizar(Request $request)
    {
        $this->checkPermission('notas.editar');

        $validated = $request->validate([
            'turma_id' => 'required|exists:turmas,id',
            'disciplina_id' => 'required|exists:disciplinas,id',
        ]);

        Nota::where('turma_id', $validated['turma_id'])
            ->where('disciplina_id', $validated['disciplina_id'])
            ->update(['status' => 'finalizado']);

        return back()->with('success', 'Notas finalizadas com sucesso!');
    }
}
