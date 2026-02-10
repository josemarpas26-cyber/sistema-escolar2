<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use Illuminate\Http\Request;

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

        $anoLetivo->load(['turmas' => fn($q) => $q->with(['curso', 'alunos'])]);

        $stats = [
            'total_turmas' => $anoLetivo->turmas->count(),
            'total_alunos' => $anoLetivo->turmas->sum(fn($t) => 
                $t->alunos()->wherePivot('status', 'matriculado')->count()
            ),
            'total_notas' => $anoLetivo->notas()->count(),
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

        // TODO: Aqui você pode adicionar validações:
        // - Verificar se todas as notas foram lançadas
        // - Gerar históricos académicos
        // - Etc.

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
