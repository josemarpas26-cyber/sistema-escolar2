<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Turma;
use App\Models\Nota;
use App\Models\AnoLetivo;
use App\Models\NotaLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return match(true) {
            $user->isAdmin()      => $this->adminDashboard(),
            $user->isSecretaria() => $this->secretariaDashboard(),
            $user->isProfessor()  => $this->professorDashboard(),
            $user->isAluno()      => $this->alunoDashboard(),
            default               => abort(403, 'Papel de usuário não reconhecido'),
        };
    }

    private function adminDashboard()
    {
        $anoLetivoAtivo = AnoLetivo::ativo()->first();

        $dias_restantes = null;

        if ($anoLetivoAtivo && !$anoLetivoAtivo->encerrado) {
            $dias_restantes = (int) now()->diffInDays($anoLetivoAtivo->data_fim, false);
        }

        $stats = [
            'total_usuarios'   => User::count(),
            'total_alunos'     => User::alunos()->count(),
            'total_professores' => User::professores()->count(),
            'total_turmas'     => Turma::count(),
            'ano_letivo_ativo' => $anoLetivoAtivo,
            'dias_restantes'   => $dias_restantes,
            'logs_recentes'    => NotaLog::with(['usuario', 'aluno', 'disciplina'])
                ->latest('data_alteracao')
                ->take(10)
                ->get(),
        ];

        return view('dashboard.admin', $stats);
    }

    private function secretariaDashboard()
    {
        $anoLetivo = AnoLetivo::ativo()->first();

        $stats = [
            'total_alunos'   => User::alunos()->ativos()->count(),
            'total_turmas'   => Turma::anoAtivo()->count(),
            'ano_letivo'     => $anoLetivo,
            'turmas_recentes' => Turma::anoAtivo()
                ->with([
                    'curso',
                    'alunos' => fn($q) => $q->wherePivot('status', 'matriculado'),
                ])
                ->latest()
                ->take(5)
                ->get(),
            'logs_hoje' => NotaLog::whereDate('data_alteracao', today())->count(),
        ];

        return view('dashboard.secretaria', $stats);
    }

    private function professorDashboard()
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        // Eager-load alunos matriculados junto com turma para evitar N+1
        $turmas = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with([
                'turma.curso',
                'turma.alunos' => fn($q) => $q->wherePivot('status', 'matriculado'),
                'disciplina',
            ])
            ->get()
            ->groupBy('turma_id');

        // Contagem sem queries adicionais
        $totalAlunos = 0;
        foreach ($turmas as $atribuicoes) {
            $totalAlunos += $atribuicoes->first()->turma->alunos->count();
        }

        // Notas pendentes filtradas pelas atribuições reais do professor
        $atribuicoes = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->get(['turma_id', 'disciplina_id']);

        $notasPendentes = 0;

        if ($atribuicoes->isNotEmpty()) {
            $notasPendentes = Nota::where('ano_letivo_id', $anoLetivo->id)
                ->where(function ($q) use ($atribuicoes) {
                    foreach ($atribuicoes as $a) {
                        $q->orWhere(fn($sub) => $sub
                            ->where('turma_id',      $a->turma_id)
                            ->where('disciplina_id', $a->disciplina_id)
                        );
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('mac1')->orWhereNull('pp1')->orWhereNull('pt1')
                      ->orWhereNull('mac2')->orWhereNull('pp2')->orWhereNull('pt2')
                      ->orWhereNull('mac3')->orWhereNull('pp3')->orWhereNull('pg');
                })
                ->count();
        }

        $stats = [
            'total_turmas'    => $turmas->count(),
            'total_alunos'    => $totalAlunos,
            'notas_pendentes' => $notasPendentes,
            'turmas'          => $turmas,
            'ano_letivo'      => $anoLetivo,
        ];

        return view('dashboard.professor', $stats);
    }

    private function alunoDashboard()
    {
        $aluno     = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (!$anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $notas = Nota::where('aluno_id',     $aluno->id)
            ->where('ano_letivo_id', $anoLetivo->id)
            ->with(['disciplina', 'turma'])
            ->get();

        $notasComCFD = $notas->whereNotNull('cfd');
        $mediaGeral  = $notasComCFD->isNotEmpty()
            ? round($notasComCFD->avg('cfd'), 2)
            : 0;

        $aprovacoes  = $notasComCFD->filter(fn($n) => $n->isAprovado())->count();
        $reprovacoes = $notasComCFD->filter(fn($n) => !$n->isAprovado())->count();

        $turmaAtual = $aluno->turmas()
            ->wherePivot('status', 'matriculado')
            ->with(['curso', 'anoLetivo'])
            ->first();

        $stats = [
            'turma'             => $turmaAtual,
            'notas'             => $notas,
            'media_geral'       => $mediaGeral,
            'aprovacoes'        => $aprovacoes,
            'reprovacoes'       => $reprovacoes,
            'total_disciplinas' => $notas->count(),
            'ano_letivo'        => $anoLetivo,
        ];

        return view('dashboard.aluno', $stats);
    }
}