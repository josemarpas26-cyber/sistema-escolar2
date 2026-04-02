<x-card title="Ranking de Alunos" icon="fas fa-trophy" class="mb-8">
    <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Curso</label>
            <select name="curso_id" class="w-full rounded-lg border-gray-300 text-sm">
                <option value="">Todos</option>
                @foreach(($filtros_ranking['cursos'] ?? collect()) as $curso)
                    <option value="{{ $curso->id }}" @selected(($filtros_ativos['curso_id'] ?? null) == $curso->id)>{{ $curso->nome }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Turma</label>
            <select name="turma_id" class="w-full rounded-lg border-gray-300 text-sm">
                <option value="">Todas</option>
                @foreach(($filtros_ranking['turmas'] ?? collect()) as $turma)
                    <option value="{{ $turma->id }}" @selected(($filtros_ativos['turma_id'] ?? null) == $turma->id)>{{ $turma->nome }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Disciplina</label>
            <select name="disciplina_id" class="w-full rounded-lg border-gray-300 text-sm">
                <option value="">Todas</option>
                @foreach(($filtros_ranking['disciplinas'] ?? collect()) as $disciplina)
                    <option value="{{ $disciplina->id }}" @selected(($filtros_ativos['disciplina_id'] ?? null) == $disciplina->id)>{{ $disciplina->nome }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm hover:bg-primary-700">Filtrar</button>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Limpar</a>
        </div>
    </form>

        @if(isset($ranking_alunos) && $ranking_alunos->count())
            <div class="space-y-2">
                @foreach($ranking_alunos as $index => $alunoRanking)
                <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center">
                            #{{ $index + 1 }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $alunoRanking->aluno_nome }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $alunoRanking->curso_nome }} · {{ $alunoRanking->turma_nome }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-primary-700">{{ number_format($alunoRanking->media_geral, 2) }}</p>
                        <p class="text-[11px] text-gray-500">{{ number_format($alunoRanking->desempenho_percentual, 2) }}% positivas</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if(!auth()->user()->isAluno() && !auth()->user()->isProfessor())
            <p class="mt-3 text-xs text-gray-500">Exibindo apenas o Top 10.</p>
        @else
            <p class="mt-3 text-xs text-gray-500">Ranking completo da(s) turma(s) atual(is).</p>
        @endif

        @isset($posicao_turma)
            <p class="mt-3 text-sm text-gray-600">Sua posição no ranking atual: <span class="font-semibold text-primary-700">#{{ $posicao_turma }}</span>.</p>
        @endisset
    @else
        <p class="text-gray-500 text-sm">Nenhum aluno encontrado com os filtros selecionados.</p>
    @endif
</x-card>
