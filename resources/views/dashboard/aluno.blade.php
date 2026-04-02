@extends('layouts.app')

@section('page-title', 'Meu Dashboard')

@section('content')

{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <x-dashboard.stats-card
        title="Média Geral"
        :value="number_format($media_geral, 2)"
        subtitle="Desempenho global"
        icon="fas fa-chart-line"
        color="primary"
    />

    <x-dashboard.stats-card
        title="Disciplinas"
        :value="$total_disciplinas"
        subtitle="Com pauta ativa"
        icon="fas fa-book"
        color="info"
    />

    <x-dashboard.stats-card
        title="Aprovações"
        :value="$aprovacoes"
        subtitle="Notas finais >= 10"
        icon="fas fa-check-circle"
        color="success"
    />

    <x-dashboard.stats-card
        title="Reprovações"
        :value="$reprovacoes"
        subtitle="Requer plano de estudo"
        icon="fas fa-exclamation-circle"
        color="warning"
    />
</div>
<div class="mb-8 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-2xl p-6 shadow-lg">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-bold">Metas Académicas</h2>
            <p class="text-primary-100 mt-1">Defina a nota-alvo por disciplina e acompanhe o progresso em tempo real.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('metas-disciplina.store') }}" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-xs uppercase tracking-wide text-primary-100 mb-1">Disciplina</label>
            <select name="disciplina_id" class="w-full rounded-lg border-0 text-gray-800 focus:ring-2 focus:ring-white" required>
                <option value="">Selecionar...</option>
                @foreach($notas->unique('disciplina_id') as $nota)
                    <option value="{{ $nota->disciplina_id }}">{{ $nota->disciplina->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs uppercase tracking-wide text-primary-100 mb-1">Meta (0-20)</label>
            <input type="number" min="0" max="20" step="0.1" name="meta_nota" class="w-full rounded-lg border-0 text-gray-800 focus:ring-2 focus:ring-white" required>
        </div>
        <div>
            <label class="block text-xs uppercase tracking-wide text-primary-100 mb-1">Conclusão prevista</label>
            <input type="date" name="data_conclusao_prevista" class="w-full rounded-lg border-0 text-gray-800 focus:ring-2 focus:ring-white">
        </div>
        <div class="md:col-span-4">
            <button class="px-4 py-2 rounded-lg bg-white text-primary-700 font-semibold hover:bg-primary-50 transition">Guardar Meta</button>
        </div>
    </form>

    @error('disciplina_id')
        <p class="mt-3 text-sm text-red-100">{{ $message }}</p>
    @enderror

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($disciplinas_com_progresso as $item)
            <div class="bg-white/10 border border-white/20 rounded-xl p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold">{{ $item['nota']->disciplina->nome }}</h3>
                    @if($item['meta'])
                        <form method="POST" action="{{ route('metas-disciplina.desativar', $item['meta']) }}">
                            @csrf
                            @method('PATCH')
                            <button class="text-xs px-2 py-1 rounded bg-white/20 hover:bg-white/30">Desativar</button>
                        </form>
                    @endif
                </div>

                @if($item['meta'])
                    <p class="text-sm text-primary-100 mt-1">
                        Nota atual: <span class="font-semibold text-white">{{ $item['nota_atual'] !== null ? number_format($item['nota_atual'], 2) : '—' }}</span>
                        · Meta: <span class="font-semibold text-white">{{ number_format($item['meta']->meta_nota, 2) }}</span>
                    </p>
                    <div class="mt-3 w-full h-2 rounded-full bg-white/20 overflow-hidden">
                        <div class="h-2 bg-emerald-300" style="width: {{ $item['progresso'] }}%"></div>
                    </div>
                    <p class="text-xs mt-2 text-primary-100">
                        Progresso: {{ $item['progresso'] }}%
                        @if($item['diferenca'] !== null)
                            ·
                            @if($item['diferenca'] > 0)
                                faltam {{ number_format($item['diferenca'], 2) }} valores
                            @else
                                meta atingida ✅
                            @endif
                        @endif
                    </p>
                @else
                    <p class="text-sm text-primary-100 mt-2">Ainda não definiu meta para esta disciplina.</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-primary-100">Sem disciplinas disponíveis para metas neste período.</p>
        @endforelse
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Minha Turma --}}
    <x-card title="Minha Turma" icon="fas fa-school">
        @if($turma)
        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">Turma</p>
                <p class="text-lg font-bold text-gray-900">{{ $turma->nome_completo }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Curso</p>
                <p class="font-semibold text-gray-900">{{ $turma->curso->nome }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Classe</p>
                <x-badge type="primary" class="text-base">{{ $turma->classe }}ª Classe</x-badge>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Ano Letivo</p>
                <p class="font-semibold text-gray-900">{{ $turma->anoLetivo->nome }}</p>
            </div>
            @if($turma->coordenador)
            <div>
                <p class="text-sm text-gray-600 mb-1">Coordenador</p>
                <p class="font-semibold text-gray-900">{{ $turma->coordenador->name }}</p>
            </div>
            @endif
        </div>
        @else
        <p class="text-gray-500 text-center py-4">Não está matriculado em nenhuma turma</p>
        @endif
    </x-card>

    {{-- Minhas Notas --}}
    <div class="lg:col-span-2">
        <x-card title="Minhas Notas" icon="fas fa-clipboard-list">
            @if($notas->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT1</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT2</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MT3</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">CFD</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($notas as $nota)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $nota->disciplina->nome }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ $nota->mt1 ? number_format($nota->mt1, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ $nota->mt2 ? number_format($nota->mt2, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ $nota->mt3 ? number_format($nota->mt3, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center font-bold">
                                {{ $nota->cfd ? number_format($nota->cfd, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($nota->cfd !== null)
                                    <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}">
                                        {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                                    </x-badge>
                                @else
                                    <x-badge type="gray">Pendente</x-badge>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Nenhuma nota lançada ainda</p>
            </div>
            @endif
        </x-card>
    </div>

</div>

{{--
    Acesso Rápido para alunos — apenas perfil e notas.
    Relatórios são geridos pela secretaria/ADM.
--}}
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Acesso Rápido</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <a href="{{ route('notas.index') }}"
           class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300
                  hover:border-primary-500 hover:bg-primary-50 transition-all">
            <i class="fas fa-clipboard-list text-2xl text-primary-600 mr-3"></i>
            <div>
                <span class="font-medium text-gray-700 block">Minhas Notas</span>
                <span class="text-xs text-gray-500">Ver notas detalhadas por disciplina</span>
            </div>
        </a>

        <a href="{{ route('profile.show') }}"
           class="flex items-center p-4 bg-white rounded-lg border-2 border-dashed border-gray-300
                  hover:border-blue-500 hover:bg-blue-50 transition-all">
            <i class="fas fa-user text-2xl text-blue-600 mr-3"></i>
            <div>
                <span class="font-medium text-gray-700 block">Meu Perfil</span>
                <span class="text-xs text-gray-500">Consultar perfil e alterar apenas a senha</span>
            </div>
        </a>

    </div>
</div>

@endsection
