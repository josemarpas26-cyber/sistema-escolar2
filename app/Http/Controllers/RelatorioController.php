<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Turma;
use App\Models\Disciplina;
use App\Models\User;
use App\Models\AnoLetivo;
use App\Models\HistoricoAcademico;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    /**
     * Página inicial de relatórios
     */
    public function index()
    {
        $this->checkPermission('relatorios.boletins');

        $anoLetivo = AnoLetivo::ativo()->first();
        $turmas = Turma::anoAtivo()->with('curso')->get();
        $disciplinas = Disciplina::ativos()->get();

        return view('relatorios.index', compact('anoLetivo', 'turmas', 'disciplinas'));
    }

    /**
     * Boletim individual do aluno
     */
    public function boletimAluno(Request $request, ?User $aluno = null)
    {
        // Se não passou aluno, usar o próprio usuário logado
        if (!$aluno) {
            $aluno = auth()->user();
            $this->checkPermission('notas.view_own');
        } else {
            $this->checkPermission('relatorios.boletins');
        }

        $anoLetivoId = $request->ano_letivo_id ?? AnoLetivo::ativo()->first()?->id;

        if (!$anoLetivoId) {
            return back()->with('error', 'Nenhum ano letivo ativo encontrado!');
        }

        $anoLetivo = AnoLetivo::findOrFail($anoLetivoId);
        $turma = $aluno->turmas()->where('ano_letivo_id', $anoLetivo->id)->first();

        if (!$turma) {
            return back()->with('error', 'Aluno não possui turma no ano letivo selecionado!');
        }

        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with('disciplina')
            ->get();

        // Calcular estatísticas
        $notasComCFD = $notas->whereNotNull('cfd');
        $mediaGeral = $notasComCFD->avg('cfd');
        $aprovacoes = $notasComCFD->filter(fn($n) => $n->isAprovado())->count();
        $reprovacoes = $notasComCFD->count() - $aprovacoes;

        $dados = [
            'aluno' => $aluno,
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'notas' => $notas,
            'mediaGeral' => round($mediaGeral, 2),
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
        ];

        // Se for para PDF
        if ($request->formato === 'pdf') {
            return $this->gerarBoletimPDF($dados);
        }

        // Se for para Excel
        if ($request->formato === 'excel') {
            return $this->gerarBoletimExcel($dados);
        }

        // View HTML
        return view('relatorios.boletim-aluno', $dados);
    }

    /**
     * Pauta da turma por disciplina
     */
    public function pautaTurma(Request $request, Turma $turma, ?Disciplina $disciplina = null)
    {
        $this->checkPermission('relatorios.pautas');

        // Se não especificou disciplina, mostrar todas
        if (!$disciplina) {
            $notas = Nota::where('turma_id', $turma->id)
                ->where('ano_letivo_id', $turma->ano_letivo_id)
                ->with(['aluno', 'disciplina'])
                ->get()
                ->groupBy('disciplina_id');

            $dados = [
                'turma' => $turma,
                'notasPorDisciplina' => $notas,
            ];

            if ($request->formato === 'pdf') {
                return $this->gerarPautaGeralPDF($dados);
            }

            return view('relatorios.pauta-geral', $dados);
        }

        // Pauta de uma disciplina específica
        $notas = Nota::where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->where('ano_letivo_id', $turma->ano_letivo_id)
            ->with('aluno')
            ->orderBy(
                User::select('name')->whereColumn('users.id', 'notas.aluno_id')
            )
            ->get();

        // Calcular estatísticas
        $notasComCFD = $notas->whereNotNull('cfd');
        $mediaGeral = $notasComCFD->avg('cfd');
        $aprovacoes = $notasComCFD->filter(fn($n) => $n->isAprovado())->count();
        $reprovacoes = $notasComCFD->count() - $aprovacoes;

        $dados = [
            'turma' => $turma,
            'disciplina' => $disciplina,
            'notas' => $notas,
            'mediaGeral' => round($mediaGeral, 2),
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'totalAlunos' => $notas->count(),
        ];

        // Se for para PDF
        if ($request->formato === 'pdf') {
            return $this->gerarPautaDisciplinaPDF($dados);
        }

        // Se for para Excel
        if ($request->formato === 'excel') {
            return $this->gerarPautaExcel($dados);
        }

        // View HTML
        return view('relatorios.pauta-disciplina', $dados);
    }

    /**
     * Histórico académico do aluno
     */
    public function historicoAcademico(Request $request, ?User $aluno = null)
    {
        // Se não passou aluno, usar o próprio usuário logado
        if (!$aluno) {
            $aluno = auth()->user();
            $this->checkPermission('relatorios.historico');
        } else {
            $this->checkPermission('relatorios.historico');
        }

        $historico = HistoricoAcademico::porAluno($aluno->id)
            ->with(['disciplina', 'turma', 'anoLetivo'])
            ->get()
            ->groupBy('ano_letivo_id');

        $dados = [
            'aluno' => $aluno,
            'historico' => $historico,
        ];

        // Se for para PDF
        if ($request->formato === 'pdf') {
            return $this->gerarHistoricoPDF($dados);
        }

        // View HTML
        return view('relatorios.historico-academico', $dados);
    }

    /**
     * Relatório consolidado da turma
     */
    public function consolidadoTurma(Turma $turma)
    {
        $this->checkPermission('relatorios.pautas');

        $alunos = $turma->alunos()->wherePivot('status', 'matriculado')->get();

        $dadosAlunos = [];
        foreach ($alunos as $aluno) {
            $notas = Nota::where('aluno_id', $aluno->id)
                ->where('turma_id', $turma->id)
                ->where('ano_letivo_id', $turma->ano_letivo_id)
                ->with('disciplina')
                ->get();

            $notasComCFD = $notas->whereNotNull('cfd');
            $mediaGeral = $notasComCFD->avg('cfd');
            $aprovacoes = $notasComCFD->filter(fn($n) => $n->isAprovado())->count();

            $dadosAlunos[] = [
                'aluno' => $aluno,
                'notas' => $notas,
                'media' => round($mediaGeral, 2),
                'aprovacoes' => $aprovacoes,
                'reprovacoes' => $notasComCFD->count() - $aprovacoes,
            ];
        }

        // Ordenar por média geral
        usort($dadosAlunos, fn($a, $b) => $b['media'] <=> $a['media']);

        $dados = [
            'turma' => $turma,
            'dadosAlunos' => $dadosAlunos,
        ];

        return view('relatorios.consolidado-turma', $dados);
    }

    /**
     * Gerar Boletim em PDF
     * TODO: Implementar com DomPDF ou mPDF
     */
    private function gerarBoletimPDF(array $dados)
    {
        // Placeholder - será implementado na Fase 5
        return response()->json([
            'message' => 'Geração de PDF será implementada na próxima fase',
            'dados' => $dados,
        ]);
    }

    /**
     * Gerar Boletim em Excel
     * TODO: Implementar com Laravel Excel
     */
    private function gerarBoletimExcel(array $dados)
    {
        // Placeholder - será implementado na Fase 5
        return response()->json([
            'message' => 'Geração de Excel será implementada na próxima fase',
            'dados' => $dados,
        ]);
    }

    /**
     * Gerar Pauta Geral em PDF
     */
    private function gerarPautaGeralPDF(array $dados)
    {
        return response()->json([
            'message' => 'Geração de PDF será implementada na próxima fase',
            'dados' => $dados,
        ]);
    }

    /**
     * Gerar Pauta de Disciplina em PDF
     */
    private function gerarPautaDisciplinaPDF(array $dados)
    {
        return response()->json([
            'message' => 'Geração de PDF será implementada na próxima fase',
            'dados' => $dados,
        ]);
    }

    /**
     * Gerar Pauta em Excel
     */
    private function gerarPautaExcel(array $dados)
    {
        return response()->json([
            'message' => 'Geração de Excel será implementada na próxima fase',
            'dados' => $dados,
        ]);
    }

    /**
     * Gerar Histórico em PDF
     */
    private function gerarHistoricoPDF(array $dados)
    {
        return response()->json([
            'message' => 'Geração de PDF será implementada na próxima fase',
            'dados' => $dados,
        ]);
    }
}
