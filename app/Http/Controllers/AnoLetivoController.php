<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\ConfiguracaoAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AnoLetivoController extends Controller
{
    public function index()
    {
        $this->checkPermission('anos.create');

        $anosLetivos = AnoLetivo::withCount('turmas')
            ->orderBy('nome', 'desc')
            ->get();

        return view('anos-letivos.index', compact('anosLetivos'));
    }

    public function create()
    {
        $this->checkPermission('anos.create');

        $anoAtivo = AnoLetivo::ativo()->first();

        if ($anoAtivo && ! $anoAtivo->encerrado) {
            return redirect()
                ->route('anos-letivos.index')
                ->with('error', 'O ano letivo atual ainda não foi encerrado! Encerre-o antes de criar um novo.');
        }

        $configuracaoPadrao = $this->configuracaoInicial();

        return view('anos-letivos.create', compact('configuracaoPadrao'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('anos.create');

        $anoAtivo = AnoLetivo::ativo()->first();
        if ($anoAtivo && ! $anoAtivo->encerrado) {
            return back()->with('error', 'O ano letivo atual ainda não foi encerrado!');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:20|unique:anos_letivos,nome',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
            'peso_pg' => 'required|numeric|gt:0|lt:100',
            'nota_minima_aprovacao' => 'required|numeric|min:0|max:20',
            'provas' => 'required|array',
            'provas.*' => 'required|array',
            'provas.*.*.nome' => 'required|string|max:120',
            'provas.*.*.codigo' => ['required', 'regex:/^[a-z][a-z0-9_]*$/'],
            'provas.*.*.peso' => 'required|numeric|gt:0',
            'provas.*.*.ativo' => 'nullable|boolean',
        ]);

        [$anoInicio, $anoFim] = explode('/', $validated['nome']);

        $dataInicio = \Carbon\Carbon::parse($validated['data_inicio']);
        $dataFim = \Carbon\Carbon::parse($validated['data_fim']);

        if ($dataInicio->year != $anoInicio || $dataFim->year != $anoFim) {
            return back()->withInput()->with(
                'error',
                'As datas devem corresponder ao intervalo do ano letivo (' . $validated['nome'] . ').'
            );
        }

        $this->validarConfiguracaoAvaliacao($validated['provas']);

        $ano = DB::transaction(function () use ($validated) {
            AnoLetivo::query()->update(['ativo' => false]);

            $ano = AnoLetivo::create([
                'nome' => $validated['nome'],
                'data_inicio' => $validated['data_inicio'],
                'data_fim' => $validated['data_fim'],
                'ativo' => true,
                'encerrado' => false,
            ]);

            $configuracao = ConfiguracaoAvaliacao::create([
                'ano_letivo_id' => $ano->id,
                'peso_pg' => $validated['peso_pg'],
                'nota_minima_aprovacao' => $validated['nota_minima_aprovacao'],
            ]);

            foreach ($validated['provas'] as $periodo => $provas) {
                foreach (array_values($provas) as $index => $prova) {
                    $configuracao->provas()->create([
                        'periodo' => (int) $periodo,
                        'nome' => $prova['nome'],
                        'codigo' => $prova['codigo'],
                        'peso' => $prova['peso'],
                        'ativo' => (bool) ($prova['ativo'] ?? false),
                        'ordem' => $index + 1,
                    ]);
                }
            }

            return $ano;
        });

        return redirect()
            ->route('anos-letivos.show', $ano)
            ->with('success', 'Ano letivo criado com sucesso!');
    }

    public function show(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        $anoLetivo->load(['turmas.curso', 'configuracaoAvaliacao.provas']);

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

    public function edit(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        return view('anos-letivos.edit', compact('anoLetivo'));
    }

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

    public function encerrar(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.encerrar');

        if ($anoLetivo->encerrado) {
            return back()->with('error', 'Este ano letivo já está encerrado!');
        }

        $anoLetivo->load([
            'turmas.disciplinas',
            'turmas.alunos' => fn($q) => $q->wherePivot('status', 'matriculado'),
        ]);

        $turmaIds = $anoLetivo->turmas->pluck('id');

        $notasPorTurma = \App\Models\Nota::where('ano_letivo_id', $anoLetivo->id)
            ->whereIn('turma_id', $turmaIds)
            ->whereNotNull('cfd')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('turma_aluno')
                    ->whereColumn('turma_aluno.aluno_id', 'notas.aluno_id')
                    ->whereColumn('turma_aluno.turma_id', 'notas.turma_id')
                    ->where('turma_aluno.status', 'matriculado');
            })
            ->groupBy('turma_id')
            ->select('turma_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'turma_id');

        foreach ($anoLetivo->turmas as $turma) {
            $totalAlunos      = $turma->alunos->count();
            $totalDisciplinas = $turma->disciplinas->count();
            $totalEsperado    = $totalAlunos * $totalDisciplinas;

            $totalNotasLancadas = $notasPorTurma[$turma->id] ?? 0;

            if ($totalNotasLancadas < $totalEsperado) {
                return back()->with(
                    'error',
                    "A turma {$turma->nome_completo} ainda possui notas pendentes."
                );
            }
        }

        $anoLetivo->update([
            'encerrado' => true,
            'ativo'     => false,
        ]);

        return back()->with('success', 'Ano letivo encerrado com sucesso!');
    }

    public function reativar(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        if ($anoLetivo->ativo && ! $anoLetivo->encerrado) {
            return back()->with('error', 'Este ano letivo já está ativo!');
        }

        DB::transaction(function () use ($anoLetivo) {
            AnoLetivo::lockForUpdate()->get();

            AnoLetivo::query()->update([
                'ativo' => false,
            ]);

            $anoLetivo->update([
                'ativo' => true,
                'encerrado' => false,
            ]);
        });

        return back()->with('success', 'Ano letivo ativado com sucesso!');
    }

    public function destroy(AnoLetivo $anoLetivo)
    {
        $this->checkPermission('anos.create');

        if ($anoLetivo->turmas()->count() > 0) {
            return back()->with('error', 'Não é possível deletar um ano letivo com turmas associadas!');
        }

        $anoLetivo->delete();

        return redirect()
            ->route('anos-letivos.index')
            ->with('success', 'Ano letivo deletado com sucesso!');
    }

    private function configuracaoInicial(): array
    {
        $ultimoAno = AnoLetivo::with('configuracaoAvaliacao.provas')->latest('id')->first();

        if ($ultimoAno?->configuracaoAvaliacao) {
            $config = $ultimoAno->configuracaoAvaliacao;

            $provasPorPeriodo = [1 => [], 2 => [], 3 => []];
            foreach ($config->provas as $prova) {
                $provasPorPeriodo[$prova->periodo][] = [
                    'nome' => $prova->nome,
                    'codigo' => $prova->codigo,
                    'peso' => (float) $prova->peso,
                    'ativo' => (bool) $prova->ativo,
                ];
            }

            return [
                'peso_pg' => (float) $config->peso_pg,
                'nota_minima_aprovacao' => (float) $config->nota_minima_aprovacao,
                'provas' => $provasPorPeriodo,
            ];
        }

        return ConfiguracaoAvaliacao::estruturaPadrao();
    }

    private function validarConfiguracaoAvaliacao(array $provasPorPeriodo): void
    {
        $colunasNota = Schema::getColumnListing('notas');
        $codigos = [];

        foreach ([1, 2, 3] as $periodo) {
            $provas = collect($provasPorPeriodo[$periodo] ?? []);
            $ativas = $provas->filter(fn (array $prova) => (bool) ($prova['ativo'] ?? false));

            if ($ativas->isEmpty()) {
                throw ValidationException::withMessages([
                    "provas.$periodo" => "O período {$periodo} deve ter pelo menos uma prova ativa.",
                ]);
            }

            foreach ($provas as $index => $prova) {
                $codigo = $prova['codigo'] ?? null;
                if (! in_array($codigo, $colunasNota, true)) {
                    throw ValidationException::withMessages([
                        "provas.$periodo.$index.codigo" => "O código '{$codigo}' não existe na tabela de notas.",
                    ]);
                }

                if (in_array($codigo, $codigos, true)) {
                    throw ValidationException::withMessages([
                        "provas.$periodo.$index.codigo" => "Código de prova duplicado: {$codigo}.",
                    ]);
                }

                $codigos[] = $codigo;
            }
        }
    }
}
