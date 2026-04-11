<?php

namespace App\Http\Controllers;

use App\Models\AnoLetivo;
use App\Models\AvaliacaoDinamica;
use App\Models\Disciplina;
use App\Models\FormulaAvaliacao;
use App\Models\FormulaAvaliacaoVersao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormulaAvaliacaoController extends Controller
{
    public function index()
    {
        $this->checkPermission('anos.create');

        $formulas = FormulaAvaliacao::with('anoLetivo')
            ->withCount(['versoes', 'avaliacoes'])
            ->orderByDesc('id')
            ->get();

        return view('formulas.index', compact('formulas'));
    }

    public function create()
    {
        $this->checkPermission('anos.create');

        $anosLetivos = AnoLetivo::orderByDesc('id')->get();

        return view('formulas.create', compact('anosLetivos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('anos.create');

        $dados = $this->validarFormula($request);
        $this->validarPesos($dados['componentes']);

        $ano = AnoLetivo::findOrFail($dados['ano_letivo_id']);
        $this->garantirAnoEditavel($ano);

        $formula = FormulaAvaliacao::create([
            'ano_letivo_id' => $dados['ano_letivo_id'],
            'nome' => $dados['nome'],
            'componentes' => $dados['componentes'],
            'regras' => $dados['regras'] ?? null,
            'ativa' => true,
        ]);

        $formula->versoes()->create([
            'versao' => 1,
            'componentes' => $dados['componentes'],
            'regras' => $dados['regras'] ?? null,
            'motivo' => 'Versão inicial',
            'criado_por' => auth()->id(),
        ]);

        return redirect()->route('formulas.show', $formula)
            ->with('success', 'Fórmula criada com sucesso.');
    }

    public function show(FormulaAvaliacao $formula)
    {
        $this->checkPermission('anos.create');

        $formula->load(['anoLetivo', 'versoes.autor', 'avaliacoes.disciplina']);
        $disciplinas = Disciplina::ativos()->orderBy('nome')->get();

        return view('formulas.show', compact('formula', 'disciplinas'));
    }

    public function edit(FormulaAvaliacao $formula)
    {
        $this->checkPermission('anos.create');
        $formula->load('anoLetivo');
        $this->garantirAnoEditavel($formula->anoLetivo);

        return view('formulas.edit', compact('formula'));
    }

    public function update(Request $request, FormulaAvaliacao $formula): RedirectResponse
    {
        $this->checkPermission('anos.create');
        $formula->load('anoLetivo');
        $this->garantirAnoEditavel($formula->anoLetivo);

        $dados = $this->validarFormula($request, $formula->id, false);
        $this->validarPesos($dados['componentes']);

        $formula->update([
            'nome' => $dados['nome'],
            'componentes' => $dados['componentes'],
            'regras' => $dados['regras'] ?? null,
        ]);

        $formula->versoes()->create([
            'versao' => $formula->proximaVersao(),
            'componentes' => $dados['componentes'],
            'regras' => $dados['regras'] ?? null,
            'motivo' => $request->string('motivo')->toString() ?: 'Edição manual',
            'criado_por' => auth()->id(),
        ]);

        return redirect()->route('formulas.show', $formula)
            ->with('success', 'Fórmula atualizada e versionada com sucesso.');
    }

    public function restaurarVersao(FormulaAvaliacao $formula, FormulaAvaliacaoVersao $versao): RedirectResponse
    {
        $this->checkPermission('anos.create');
        $formula->load('anoLetivo');
        $this->garantirAnoEditavel($formula->anoLetivo);

        abort_if($versao->formula_avaliacao_id !== $formula->id, 404);

        $formula->update([
            'componentes' => $versao->componentes,
            'regras' => $versao->regras,
        ]);

        $formula->versoes()->create([
            'versao' => $formula->proximaVersao(),
            'componentes' => $versao->componentes,
            'regras' => $versao->regras,
            'motivo' => "Reversão para versão #{$versao->versao}",
            'criado_por' => auth()->id(),
        ]);

        return back()->with('success', 'Versão restaurada com sucesso.');
    }

    public function storeAvaliacao(Request $request, FormulaAvaliacao $formula): RedirectResponse
    {
        $this->checkPermission('anos.create');
        $formula->load('anoLetivo');
        $this->garantirAnoEditavel($formula->anoLetivo);

        $dados = $request->validate([
            'disciplina_id' => 'required|exists:disciplinas,id',
            'nome' => 'required|string|max:120',
            'tipo' => 'required|in:prova_escrita,trabalho,avaliacao_continua,exame_final',
            'peso' => 'required|numeric|min:0|max:100',
            'excecoes' => 'nullable|string',
        ]);

        $formula->avaliacoes()->create([
            'ano_letivo_id' => $formula->ano_letivo_id,
            'disciplina_id' => $dados['disciplina_id'],
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
            'peso' => $dados['peso'],
            'excecoes' => filled($dados['excecoes'] ?? null) ? ['descricao' => $dados['excecoes']] : null,
        ]);

        return back()->with('success', 'Avaliação dinâmica criada.');
    }

    public function updateAvaliacao(Request $request, FormulaAvaliacao $formula, AvaliacaoDinamica $avaliacao): RedirectResponse
    {
        $this->checkPermission('anos.create');
        $formula->load('anoLetivo');
        $this->garantirAnoEditavel($formula->anoLetivo);
        abort_if($avaliacao->formula_avaliacao_id !== $formula->id, 404);

        $dados = $request->validate([
            'nome' => 'required|string|max:120',
            'tipo' => 'required|in:prova_escrita,trabalho,avaliacao_continua,exame_final',
            'peso' => 'required|numeric|min:0|max:100',
            'excecoes' => 'nullable|string',
        ]);

        $avaliacao->update([
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
            'peso' => $dados['peso'],
            'excecoes' => filled($dados['excecoes'] ?? null) ? ['descricao' => $dados['excecoes']] : null,
        ]);

        return back()->with('success', 'Avaliação dinâmica atualizada.');
    }

    public function destroyAvaliacao(FormulaAvaliacao $formula, AvaliacaoDinamica $avaliacao): RedirectResponse
    {
        $this->checkPermission('anos.create');
        $formula->load('anoLetivo');
        $this->garantirAnoEditavel($formula->anoLetivo);
        abort_if($avaliacao->formula_avaliacao_id !== $formula->id, 404);

        $avaliacao->delete();

        return back()->with('success', 'Avaliação dinâmica removida.');
    }

    private function validarFormula(Request $request, ?int $id = null, bool $exigirAno = true): array
    {
        $regras = [
            'nome' => 'required|string|max:120',
            'componentes' => 'required|array|min:1',
            'componentes.*.nome' => 'required|string|max:120',
            'componentes.*.peso' => 'required|numeric|min:0|max:100',
            'regras.nota_minima' => 'nullable|numeric|min:0|max:20',
            'regras.arredondamento' => 'nullable|in:baixo,cima,normal',
        ];

        if ($exigirAno) {
            $regras['ano_letivo_id'] = 'required|exists:anos_letivos,id';
            $regras['nome'] .= '|unique:formulas_avaliacao,nome,' . ($id ?? 'NULL') . ',id,ano_letivo_id,' . $request->input('ano_letivo_id');
        }

        return $request->validate($regras);
    }

    private function validarPesos(array $componentes): void
    {
        $soma = collect($componentes)->sum(fn ($item) => (float) ($item['peso'] ?? 0));

        if (round($soma, 2) !== 100.0) {
            abort(422, 'A soma dos pesos da fórmula deve ser exatamente 100%.');
        }
    }

    private function garantirAnoEditavel(AnoLetivo $ano): void
    {
        if ($ano->encerrado) {
            abort(403, 'Ano letivo encerrado. Configurações em modo somente leitura.');
        }
    }
}
