<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnoLetivoController extends Controller
{
    /**
     * Listar anos letivos
     */
    public function index()
    {
        $this->checkPermission('anos.create');

        $anosLetivos = AnoLetivo::withCount('turmas')
            ->orderBy('nome', 'desc')
            ->get();

        return view('anos-letivos.index', compact('anosLetivos'));
    }


    /**
     * Formulário de criação
     */
    public function create()
    {
        $this->checkPermission('anos.create');

        // Verificar se há ano ativo não encerrado
        $anoAtivo = AnoLetivo::ativo()->first();
        
        if ($anoAtivo && !$anoAtivo->encerrado) {
            return redirect()
                ->route('anos-letivos.index')
                ->with('error', 'O ano letivo atual ainda não foi encerrado! Encerre-o antes de criar um novo.');
        }

        return view('anos-letivos.create');
    }

    /**
     * Salvar novo ano letivo
     */
    public function store(Request $request)
    {
        $this->checkPermission('anos.create');

        // Verificar novamente
        $anoAtivo = AnoLetivo::ativo()->first();
        if ($anoAtivo && !$anoAtivo->encerrado) {
            return back()->with('error', 'O ano letivo atual ainda não foi encerrado!');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:20|unique:anos_letivos,nome',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
        ]);


        // Extrair anos do nome (ex: 2024/2025)
        [$anoInicio, $anoFim] = explode('/', $validated['nome']);

        $dataInicio = \Carbon\Carbon::parse($validated['data_inicio']);
        $dataFim = \Carbon\Carbon::parse($validated['data_fim']);

        // Validar se pertencem ao intervalo correto
        if ($dataInicio->year != $anoInicio || $dataFim->year != $anoFim) {
            return back()->withInput()->with(
                'error',
                'As datas devem corresponder ao intervalo do ano letivo (' . $validated['nome'] . ').'
            );
        }

        // Desativar todos os anos anteriores
        AnoLetivo::query()->update(['ativo' => false]);

        // Criar novo ano ativo
        $ano = AnoLetivo::create([
            ...$validated,
            'ativo' => true,
            'encerrado' => false,
        ]);

        return redirect()
            ->route('anos-letivos.show', $ano)
            ->with('success', 'Ano letivo criado com sucesso!');
    }

    /**
     * Exibir ano letivo
     */
public function show(AnoLetivo $anoLetivo)
{
    $this->checkPermission('anos.create');

    $anoLetivo->load(['turmas.curso']);

    $turmaIds = $anoLetivo->turmas->pluck('id');

    $stats = [
        'total_turmas' => $anoLetivo->turmas->count(),
        'total_alunos' => DB::table('turma_aluno')
            ->whereIn('turma_id', $turmaIds)
            ->where('status', 'matriculado')
            ->count(),
        'total_notas'  => $anoLetivo->notas()->count(),
    ];

    return view('anos-letivos.show', compact('anoLetivo', 'stats'));
}

    /**
     * Formulário de edição
     */
    public function edit(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        return view('anos-letivos.edit', compact('anoLetivo'));
    }

    /**
     * Atualizar ano letivo
     */
    public function update(Request $request, AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        $validated = $request->validate([
            'nome' => 'required|string|max:20|unique:anos_letivos,nome,' . $anoLetivo->id,
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
        ]);

        $anoLetivo->update($validated);

        return redirect()
            ->route('anos-letivos.show', $anoLetivo)
            ->with('success', 'Ano letivo atualizado com sucesso!');
    }

    /**
     * Encerrar ano letivo
     */
    public function encerrar(AnoLetivo $anoLetivo)
{
    $this->checkPermission('anos.encerrar');

    if ($anoLetivo->encerrado) {
        return back()->with('error', 'Este ano letivo já está encerrado!');
    }

    
      foreach ($anoLetivo->turmas as $turma) {

        $totalAlunos = $turma->alunos()
            ->wherePivot('status', 'matriculado')
            ->count();

        $totalDisciplinas = $turma->disciplinas()->count();

        $totalEsperado = $totalAlunos * $totalDisciplinas;

        $totalNotasLancadas = \App\Models\Nota::where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->whereNotNull('cfd')
            ->count();

        if ($totalNotasLancadas < $totalEsperado) {
            return back()->with(
                'error',
                "A turma {$turma->nome_completo} ainda possui notas pendentes."
            );
        }
    }

    // 🔒 Impedir encerramento antes da data de fim
    if (now()->startOfDay()->lt($anoLetivo->data_fim->startOfDay())) {
        return back()->with('error', 
            'Não é possível encerrar o ano letivo antes de ' 
            . $anoLetivo->data_fim->format('d/m/Y') . '.'
        );
    }

    $anoLetivo->update([
        'encerrado' => true,
        'ativo' => false,
    ]);

    return back()->with('success', 'Ano letivo encerrado com sucesso!');
}


    /**
     * Reativar ano letivo
     */
    public function reativar(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        if (!$anoLetivo->encerrado) {
            return back()->with('error', 'Este ano letivo não está encerrado!');
        }

        // Desativar todos os outros anos
        AnoLetivo::query()->update(['ativo' => false]);

        $anoLetivo->update([
            'ativo' => true,
            'encerrado' => false,
        ]);

        return back()->with('success', 'Ano letivo reativado com sucesso!');
    }

    /**
     * Deletar ano letivo
     */
    public function destroy(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        // Verificar se há turmas associadas
        if ($anoLetivo->turmas()->count() > 0) {
            return back()->with('error', 'Não é possível deletar um ano letivo com turmas associadas!');
        }

        $anoLetivo->delete();

        return redirect()
            ->route('anos-letivos.index')
            ->with('success', 'Ano letivo deletado com sucesso!');
    }
}
