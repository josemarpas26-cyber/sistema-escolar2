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

         if ($request->filled('aluno')) {
            $query->whereHas('aluno', function ($alunoQuery) use ($request) {
                $alunoQuery->where('name', 'like', '%' . $request->aluno . '%');
            });
        }

        if ($request->filled('turma')) {
            $query->whereHas('turma', function ($turmaQuery) use ($request) {
                $turmaQuery
                    ->where('nome', 'like', '%' . $request->turma . '%')
                    ->orWhere('classe', 'like', '%' . $request->turma . '%');
            });
        }

        if ($request->filled('curso')) {
            $query->whereHas('turma.curso', function ($cursoQuery) use ($request) {
                $cursoQuery->where('nome', 'like', '%' . $request->curso . '%');
            });
        }

        if ($request->filled('disciplina')) {
            $query->whereHas('disciplina', function ($disciplinaQuery) use ($request) {
                $disciplinaQuery
                    ->where('nome', 'like', '%' . $request->disciplina . '%')
                    ->orWhere('codigo', 'like', '%' . $request->disciplina . '%');
            });
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

        $logs = $query->paginate(50)->withQueryString();
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

    $totalLogs = NotaLog::count();
    $logsHoje = NotaLog::whereDate('data_alteracao', today())->count();
    $logsSemana = NotaLog::where('data_alteracao', '>=', now()->subWeek())->count();
    $logsMes = NotaLog::where('data_alteracao', '>=', now()->subMonth())->count();

    $logsPorAcao = NotaLog::selectRaw('acao, COUNT(*) as total')
    ->groupBy('acao')
    ->pluck('total', 'acao');


    $logsPorTrimestre = NotaLog::whereNotNull('trimestre')
        ->selectRaw('trimestre, COUNT(*) as total')
        ->groupBy('trimestre')
        ->pluck('total', 'trimestre');

    $topUsuarios = NotaLog::selectRaw('usuario_id, COUNT(*) as total')
    ->groupBy('usuario_id')
    ->orderByDesc('total')
    ->take(10)
    ->with('usuario.role') // já aproveita e carrega role junto
    ->get();

    $topDisciplinas = NotaLog::selectRaw('disciplina_id, COUNT(*) as total')
    ->whereNotNull('disciplina_id')
    ->groupBy('disciplina_id')
    ->orderByDesc('total')
    ->take(10)
    ->with('disciplina')
    ->get();

    $atividadeSemanalRaw = NotaLog::selectRaw('DATE(data_alteracao) as dia, COUNT(*) as total')
        ->where('data_alteracao', '>=', now()->subDays(6))
        ->groupBy('dia')
        ->pluck('total', 'dia');

    $atividadeSemanal = collect();

    for ($i = 6; $i >= 0; $i--) {
        $data = now()->subDays($i)->format('Y-m-d');
        $atividadeSemanal[$data] = $atividadeSemanalRaw[$data] ?? 0;
    }

    $logsRecentes = NotaLog::with(['usuario', 'aluno', 'disciplina'])
        ->latest('data_alteracao')
        ->take(20)
        ->get();

    return view('logs.dashboard', compact(
    'totalLogs',
    'logsHoje',
    'logsSemana',
    'logsMes',
    'logsPorAcao',
    'logsPorTrimestre',
    'topUsuarios',
    'topDisciplinas',
    'logsRecentes',
    'atividadeSemanal'
));

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

        if ($request->filled('aluno')) {
            $query->whereHas('aluno', function ($alunoQuery) use ($request) {
                $alunoQuery->where('name', 'like', '%' . $request->aluno . '%');
            });
        }

        if ($request->filled('turma')) {
            $query->whereHas('turma', function ($turmaQuery) use ($request) {
                $turmaQuery
                    ->where('nome', 'like', '%' . $request->turma . '%')
                    ->orWhere('classe', 'like', '%' . $request->turma . '%');
            });
        }

        if ($request->filled('curso')) {
            $query->whereHas('turma.curso', function ($cursoQuery) use ($request) {
                $cursoQuery->where('nome', 'like', '%' . $request->curso . '%');
            });
        }

        if ($request->filled('disciplina')) {
            $query->whereHas('disciplina', function ($disciplinaQuery) use ($request) {
                $disciplinaQuery
                    ->where('nome', 'like', '%' . $request->disciplina . '%')
                    ->orWhere('codigo', 'like', '%' . $request->disciplina . '%');
            });
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
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

            $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
        
        fwrite($file, "\xEF\xBB\xBF");
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
            ], ';');

            // Dados
            foreach ($logs as $log) {
                 $valorAnterior = $log->valor_anterior !== null
                    ? number_format((float) $log->valor_anterior, 2, ',', '.')
                    : '-';

                $valorNovo = $log->valor_novo !== null
                    ? number_format((float) $log->valor_novo, 2, ',', '.')
                    : '-';

                $trimestre = $log->trimestre ? $log->trimestre . 'º' : '-';
                
                fputcsv($file, [
                     optional($log->data_alteracao)->format('d/m/Y H:i:s') ?? '-',
                    $log->usuario->name ?? '-',
                    $log->descricao_acao,
                    $log->aluno->name ?? '-',
                    $log->turma->nome_completo ?? '-',
                    $log->disciplina->nome ?? '-',
                    $log->descricao_campo,
                    $valorAnterior,
                    $valorNovo,
                    $trimestre,
                    $log->ip_address ?? '-',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
