<?php

namespace App\Http\Controllers;

use App\Models\NotaLog;
use App\Models\Turma;
use App\Models\Disciplina;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Listar logs de alterações
     */
    public function index(Request $request)
    {
        $this->checkPermission('logs.view');

        $query = NotaLog::with(['usuario', 'aluno', 'turma', 'disciplina'])
            ->latest('data_alteracao');

        // Filtros
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('aluno_id')) {
            $query->where('aluno_id', $request->aluno_id);
        }

        if ($request->filled('turma_id')) {
            $query->where('turma_id', $request->turma_id);
        }

        if ($request->filled('disciplina_id')) {
            $query->where('disciplina_id', $request->disciplina_id);
        }

        if ($request->filled('acao')) {
            $query->where('acao', $request->acao);
        }

        if ($request->filled('trimestre')) {
            $query->where('trimestre', $request->trimestre);
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_alteracao', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_alteracao', '<=', $request->data_fim);
        }

        $logs = $query->paginate(50);

        // Para os filtros
        $usuarios = User::whereIn('id', 
            NotaLog::distinct('usuario_id')->pluck('usuario_id')
        )->get();

        $turmas = Turma::anoAtivo()->with('curso')->get();
        $disciplinas = Disciplina::ativos()->get();

        return view('logs.index', compact('logs', 'usuarios', 'turmas', 'disciplinas'));
    }

    /**
     * Logs de um aluno específico
     */
    public function porAluno(User $aluno)
    {
        $this->checkPermission('logs.view');

        $logs = NotaLog::where('aluno_id', $aluno->id)
            ->with(['usuario', 'disciplina', 'turma'])
            ->latest('data_alteracao')
            ->paginate(30);

        return view('logs.por-aluno', compact('logs', 'aluno'));
    }

    /**
     * Logs de uma nota específica
     */
    public function porNota($notaId)
    {
        $this->checkPermission('logs.view');

        $logs = NotaLog::where('nota_id', $notaId)
            ->with(['usuario', 'aluno', 'disciplina'])
            ->latest('data_alteracao')
            ->get();

        return view('logs.por-nota', compact('logs'));
    }

    /**
     * Logs de uma turma e disciplina
     */
    public function porTurmaDisciplina(Turma $turma, Disciplina $disciplina)
    {
        $this->checkPermission('logs.view');

        $logs = NotaLog::where('turma_id', $turma->id)
            ->where('disciplina_id', $disciplina->id)
            ->with(['usuario', 'aluno'])
            ->latest('data_alteracao')
            ->paginate(50);

        return view('logs.por-turma-disciplina', compact('logs', 'turma', 'disciplina'));
    }

    /**
     * Logs de um usuário (quem fez alterações)
     */
    public function porUsuario(User $usuario)
    {
        $this->checkPermission('logs.view');

        $logs = NotaLog::where('usuario_id', $usuario->id)
            ->with(['aluno', 'disciplina', 'turma'])
            ->latest('data_alteracao')
            ->paginate(50);

        return view('logs.por-usuario', compact('logs', 'usuario'));
    }

    /**
     * Dashboard de logs (estatísticas)
     */
    public function dashboard()
    {
        $this->checkPermission('logs.view');

        $stats = [
            'total_logs' => NotaLog::count(),
            'logs_hoje' => NotaLog::whereDate('data_alteracao', today())->count(),
            'logs_semana' => NotaLog::where('data_alteracao', '>=', now()->subWeek())->count(),
            'logs_mes' => NotaLog::where('data_alteracao', '>=', now()->subMonth())->count(),
            
            'por_acao' => NotaLog::selectRaw('acao, COUNT(*) as total')
                ->groupBy('acao')
                ->pluck('total', 'acao'),
            
            'por_trimestre' => NotaLog::whereNotNull('trimestre')
                ->selectRaw('trimestre, COUNT(*) as total')
                ->groupBy('trimestre')
                ->pluck('total', 'trimestre'),
            
            'usuarios_mais_ativos' => NotaLog::selectRaw('usuario_id, COUNT(*) as total')
                ->groupBy('usuario_id')
                ->orderByDesc('total')
                ->take(10)
                ->with('usuario')
                ->get(),
            
            'logs_recentes' => NotaLog::with(['usuario', 'aluno', 'disciplina'])
                ->latest('data_alteracao')
                ->take(20)
                ->get(),
        ];

        return view('logs.dashboard', $stats);
    }

    /**
     * Exportar logs para CSV
     */
    public function exportar(Request $request)
    {
        $this->checkPermission('logs.view');

        $query = NotaLog::with(['usuario', 'aluno', 'turma', 'disciplina'])
            ->latest('data_alteracao');

        // Aplicar mesmos filtros do index
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_alteracao', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_alteracao', '<=', $request->data_fim);
        }

        $logs = $query->get();

        // Gerar CSV
        $filename = 'logs-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalho
            fputcsv($file, [
                'Data/Hora',
                'Usuário',
                'Ação',
                'Aluno',
                'Turma',
                'Disciplina',
                'Campo',
                'Valor Anterior',
                'Valor Novo',
                'Trimestre',
                'IP',
            ]);

            // Dados
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->data_alteracao->format('d/m/Y H:i:s'),
                    $log->usuario->name,
                    $log->descricao_acao,
                    $log->aluno->name,
                    $log->turma->nome_completo,
                    $log->disciplina->nome,
                    $log->descricao_campo,
                    $log->valor_anterior,
                    $log->valor_novo,
                    $log->trimestre,
                    $log->ip_address,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
