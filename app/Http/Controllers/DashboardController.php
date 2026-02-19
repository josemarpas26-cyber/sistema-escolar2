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
    /**
     * Dashboard principal - redireciona baseado no papel
     */
    public function index()
    {
        $user = auth()->user();

        return match(true) {
            $user->isAdmin() => $this->adminDashboard(),
            $user->isSecretaria() => $this->secretariaDashboard(),
            $user->isProfessor() => $this->professorDashboard(),
            $user->isAluno() => $this->alunoDashboard(),
            default => abort(403, 'Papel de usuário não reconhecido'),
        };
    }

    /**
     * Dashboard do Administrador
     */
private function adminDashboard()
{
    $anoLetivoAtivo = AnoLetivo::ativo()->first();

    $dias_restantes = null;

    if ($anoLetivoAtivo && !$anoLetivoAtivo->encerrado) {
        $dias_restantes = now()->diffInDays($anoLetivoAtivo->data_fim, false);
        $dias_restantes =  (int) $dias_restantes;
    }

    $stats = [
        'total_usuarios' => User::count(),
        'total_alunos' => User::alunos()->count(),
        'total_professores' => User::professores()->count(),
        'total_turmas' => Turma::count(),
        'ano_letivo_ativo' => $anoLetivoAtivo,
        'dias_restantes' => $dias_restantes,
        'logs_recentes' => NotaLog::with(['usuario', 'aluno', 'disciplina'])
            ->latest('data_alteracao')
            ->take(10)
            ->get(),
    ];

    return view('dashboard.admin', $stats);
}

    /**
     * Dashboard da Secretaria
     */
    private function secretariaDashboard()
    {
        $anoLetivo = AnoLetivo::ativo()->first();
        
        $stats = [
            'total_alunos' => User::alunos()->ativos()->count(),
            'total_turmas' => Turma::anoAtivo()->count(),
            'ano_letivo' => $anoLetivo,
            'turmas_recentes' => Turma::anoAtivo()
                ->with(['curso', 'alunos'])
                ->latest()
                ->take(5)
                ->get(),
            'logs_hoje' => NotaLog::whereDate('data_alteracao', today())
                ->count(),
        ];

        return view('dashboard.secretaria', $stats);
    }

    /**
     * Dashboard do Professor
     */
    private function professorDashboard()
    {
        $professor = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        // Turmas que leciona
        $turmas = $professor->atribuicoes()
            ->where('ano_letivo_id', $anoLetivo?->id)
            ->with(['turma.curso', 'disciplina'])
            ->get()
            ->groupBy('turma_id');

        // Total de alunos
        $totalAlunos = 0;
        foreach ($turmas as $atribuicoes) {
            $turma = $atribuicoes->first()->turma;
            $totalAlunos += $turma->alunos()->wherePivot('status', 'matriculado')->count();
        }

        // Notas pendentes (MAC, PP, PT vazios)
        $notasPendentes = Nota::whereHas('turma.professores', fn($q) => 
            $q->where('users.id', $professor->id)
        )->where(function($q) {
            $q->whereNull('mac1')
              ->orWhereNull('pp1')
              ->orWhereNull('pt1')
              ->orWhereNull('mac2')
              ->orWhereNull('pp2')
              ->orWhereNull('pt2')
              ->orWhereNull('mac3')
              ->orWhereNull('pp3')
              ->orWhereNull('pg');
        })->count();

        $stats = [
            'total_turmas' => $turmas->count(),
            'total_alunos' => $totalAlunos,
            'notas_pendentes' => $notasPendentes,
            'turmas' => $turmas,
            'ano_letivo' => $anoLetivo,
        ];

        return view('dashboard.professor', $stats);
    }

    /**
     * Dashboard do Aluno
     */
    private function alunoDashboard()
    {
        $aluno = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        // Notas do ano atual
        $notas = Nota::where('aluno_id', $aluno->id)
            ->where('ano_letivo_id', $anoLetivo?->id)
            ->with(['disciplina', 'turma'])
            ->get();

        // Calcular estatísticas
        $notasComCFD = $notas->whereNotNull('cfd');
        $mediaGeral = $notasComCFD->avg('cfd');
        $aprovacoes = $notasComCFD->filter(fn($n) => $n->isAprovado())->count();
        $reprovacoes = $notasComCFD->filter(fn($n) => !$n->isAprovado())->count();

        // Turma atual
        $turmaAtual = $aluno->turmas()
            ->wherePivot('status', 'matriculado')
            ->with(['curso', 'anoLetivo'])
            ->first();

        $stats = [
            'turma' => $turmaAtual,
            'notas' => $notas,
            'media_geral' => round($mediaGeral, 2),
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'total_disciplinas' => $notas->count(),
            'ano_letivo' => $anoLetivo,
        ];

        return view('dashboard.aluno', $stats);
    }
}
