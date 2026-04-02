<form method="GET" action="{{ route('ranking.index') }}" class="bg-white rounded-xl border border-gray-200 p-4">
    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Configurar rankings</h3>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
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

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Ordenar por</label>
            <select name="ordem" class="w-full rounded-lg border-gray-300 text-sm">
                <option value="media" @selected(($filtros_ativos['ordem'] ?? 'media') === 'media')>Média Geral</option>
                <option value="positivas" @selected(($filtros_ativos['ordem'] ?? null) === 'positivas')>% Positivas</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Quantidade</label>
            <select name="limite" class="w-full rounded-lg border-gray-300 text-sm">
                <option value="">Padrão</option>
                <option value="5" @selected(($filtros_ativos['limite'] ?? null) === '5')>Top 5</option>
                <option value="10" @selected(($filtros_ativos['limite'] ?? null) === '10')>Top 10</option>
                <option value="20" @selected(($filtros_ativos['limite'] ?? null) === '20')>Top 20</option>
                <option value="completo" @selected(($filtros_ativos['limite'] ?? null) === 'completo')>Completo</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Mín. avaliações</label>
            <input type="number" min="1" max="50" name="minimo_avaliacoes" value="{{ $filtros_ativos['minimo_avaliacoes'] ?? 1 }}" class="w-full rounded-lg border-gray-300 text-sm">
        </div>
    </div>

    <div class="mt-3 flex gap-2">
        <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm hover:bg-primary-700">Aplicar requisitos</button>
        <a href="{{ route('ranking.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Limpar</a>
    </div>
</form>
