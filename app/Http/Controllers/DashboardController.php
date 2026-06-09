<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Turma;
use App\Models\Nota;
use App\Models\AnoLetivo;
use App\Models\HistoricoAcademico;
use App\Models\NotaLog;
use App\Models\ProfessorTurmaDisciplina;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
            'total_alunos'      => User::alunos()->ativos()->count(),
            'total_professores' => User::professores()->ativos()->count(), // ← estava em falta
            'total_turmas'      => Turma::anoAtivo()->count(),
            'ano_letivo'        => $anoLetivo,
            'turmas_recentes'   => Turma::anoAtivo()
                ->with(['curso', 'alunos'])
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
                      ->orWhereNull('mac3')->orWhereNull('pp3')
                      ->orWhere(fn ($sub) => $sub
                          ->whereHas('turma', fn ($turma) => $turma->where('classe', '!=', '12'))
                          ->whereNull('pt3'))
                      ->orWhere(fn ($sub) => $sub
                          ->whereHas('turma', fn ($turma) => $turma->where('classe', '12'))
                          ->whereNull('pg'));
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
        $aluno = auth()->user();
        $anoLetivo = AnoLetivo::ativo()->first();

        if (! $anoLetivo) {
            return view('dashboard.sem-ano-letivo');
        }

        $turmaAtual = $aluno->turmas()
            ->where('ano_letivo_id', $anoLetivo->id)
            ->wherePivot('status', 'matriculado')
            ->with([
                'curso',
                'anoLetivo',
                'coordenador',
                'disciplinas.coordenador',
            ])
            ->first();

        $disciplinasTurma = $turmaAtual?->disciplinas
            ? $turmaAtual->disciplinas->sortBy('nome')->values()
            : collect();

        $notas = ($turmaAtual && $disciplinasTurma->isNotEmpty())
            ? Nota::query()
                ->where('aluno_id', $aluno->id)
                ->where('ano_letivo_id', $anoLetivo->id)
                ->where('turma_id', $turmaAtual->id)
                ->whereIn('disciplina_id', $disciplinasTurma->pluck('id'))
                ->with(['disciplina.coordenador', 'turma.curso', 'anoLetivo', 'aluno.turmas'])
                ->get()
            : collect();

        $notasPorDisciplina = $notas->keyBy('disciplina_id');
        $atribuicoes = $this->buscarAtribuicoesDaTurma($turmaAtual, $anoLetivo->id);
        $disciplinasDetalhadas = $disciplinasTurma->map(function ($disciplina) use ($notasPorDisciplina, $atribuicoes) {
            $nota = $notasPorDisciplina->get($disciplina->id);
            $atribuicao = $atribuicoes->get($disciplina->id);
            $indicador = $this->resolverIndicadorDisciplina($nota);

            return [
                'disciplina' => $disciplina,
                'nota' => $nota,
                'professor' => $atribuicao?->professor,
                'coordenador' => $disciplina->coordenador,
                'indicador' => $indicador,
            ];
        })->values();

        $notasComCFD = $notas->whereNotNull('cfd');
        $mediaGeral = $notasComCFD->isNotEmpty()
            ? round($notasComCFD->avg('cfd'), 2)
            : 0;

        $aprovacoes = $notasComCFD->filter(fn ($nota) => $nota->isAprovado())->count();
        $reprovacoes = $notasComCFD->filter(fn ($nota) => ! $nota->isAprovado())->count();
        $indicadoresAtuais = $disciplinasDetalhadas
            ->pluck('indicador')
            ->filter(fn ($indicador) => $indicador['valor'] !== null);
        $mediaAtual = $indicadoresAtuais->isNotEmpty()
            ? round($indicadoresAtuais->avg('valor'), 2)
            : null;

        $desempenhoDisciplinas = $disciplinasDetalhadas
            ->map(function (array $item) {
                $valor = $item['indicador']['valor'];
                $percentual = $valor !== null ? min(100, max(0, ($valor / 20) * 100)) : 0;

                return [
                    'disciplina' => $item['disciplina'],
                    'professor' => $item['professor'],
                    'coordenador' => $item['coordenador'],
                    'indicador' => $item['indicador'],
                    'percentual' => round($percentual, 1),
                ];
            })
            ->sortBy([
                fn (array $item) => $item['indicador']['valor'] === null ? 1 : 0,
                fn (array $item) => strtolower($item['disciplina']->nome),
            ])
            ->values();

        $evolucaoTemporal = collect([
            $this->criarPontoEvolucao('1º Trimestre', 'mt1', $notas),
            $this->criarPontoEvolucao('2º Trimestre', 'mt2', $notas),
            $this->criarPontoEvolucao('3º Trimestre', 'mt3', $notas),
            $this->criarPontoEvolucao('Resultado Final', 'cfd', $notas),
        ]);

        $historicoPorAno = HistoricoAcademico::porAluno($aluno->id)
            ->where('ano_letivo_id', '!=', $anoLetivo->id)
            ->with([
                'anoLetivo',
                'disciplina.coordenador',
                'turma.curso',
                'turma.coordenador',
            ])
            ->get()
            ->groupBy(fn ($registo) => $registo->anoLetivo?->nome ?? 'Sem ano letivo')
            ->map(function (Collection $registos, string $nomeAno) {
                $ordenados = $registos->sortBy([
                    fn ($registo) => (int) ($registo->classe ?? 0),
                    fn ($registo) => strtolower($registo->disciplina->nome ?? ''),
                ])->values();

                $media = $ordenados->whereNotNull('classificacao_final')->avg('classificacao_final');

                return [
                    'ano' => $nomeAno,
                    'registos' => $ordenados,
                    'media' => $media !== null ? round($media, 2) : null,
                    'aprovadas' => $ordenados->filter(fn ($registo) => strtolower((string) $registo->resultado) === 'aprovado')->count(),
                    'reprovadas' => $ordenados->filter(fn ($registo) => strtolower((string) $registo->resultado) === 'reprovado')->count(),
                ];
            })
            ->sortByDesc(fn (array $ano) => $ano['registos']->first()?->ano_letivo_id ?? 0)
            ->values();

        $stats = [
            'turma' => $turmaAtual,
            'notas' => $notas,
            'disciplinas_detalhadas' => $disciplinasDetalhadas,
            'desempenho_disciplinas' => $desempenhoDisciplinas,
            'evolucao_temporal' => $evolucaoTemporal,
            'historico_por_ano' => $historicoPorAno,
            'media_geral' => $mediaGeral,
            'media_atual' => $mediaAtual,
            'aprovacoes' => $aprovacoes,
            'reprovacoes' => $reprovacoes,
            'total_disciplinas' => $disciplinasTurma->count(),
            'disciplinas_com_resultado' => $notasComCFD->count(),
            'ano_letivo' => $anoLetivo,
        ];

        return view('dashboard.aluno', $stats);
    }

    private function buscarAtribuicoesDaTurma(?Turma $turma, int $anoLetivoId): Collection
    {
        if (! $turma) {
            return collect();
        }

        return ProfessorTurmaDisciplina::query()
            ->where('turma_id', $turma->id)
            ->where('ano_letivo_id', $anoLetivoId)
            ->with(['professor', 'disciplina'])
            ->get()
            ->keyBy('disciplina_id');
    }

    private function resolverIndicadorDisciplina(?Nota $nota): array
    {
        if (! $nota) {
            return [
                'label' => 'Sem lancamento',
                'valor' => null,
            ];
        }

        foreach ([
            'cfd' => 'CFD',
            'ca' => 'CA',
            'cf' => 'CF',
            'mt3' => 'MT3',
            'mft2' => 'MFT2',
            'mt2' => 'MT2',
            'mt1' => 'MT1',
        ] as $campo => $label) {
            if ($nota->{$campo} !== null) {
                return [
                    'label' => $label,
                    'valor' => (float) $nota->{$campo},
                ];
            }
        }

        return [
            'label' => 'Em preenchimento',
            'valor' => null,
        ];
    }

    private function criarPontoEvolucao(string $label, string $campo, Collection $notas): array
    {
        $notasComValor = $notas->whereNotNull($campo)->values();
        $media = $notasComValor->isNotEmpty() ? round($notasComValor->avg($campo), 2) : null;
        $aprovadas = $notasComValor->filter(fn ($nota) => (float) $nota->{$campo} >= 10)->count();
        $taxa = $notasComValor->isNotEmpty()
            ? round(($aprovadas / $notasComValor->count()) * 100, 1)
            : null;

        return [
            'label' => $label,
            'campo' => $campo,
            'media' => $media,
            'total' => $notasComValor->count(),
            'taxa_aprovacao' => $taxa,
        ];
    }
}
