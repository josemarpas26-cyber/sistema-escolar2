@extends('layouts.app')

@section('page-title', $turma->nome_completo)

@section('header-actions')
<div class="flex space-x-2">
    <a href="{{ route('turmas.edit', $turma) }}" class="btn btn-primary">
        <i class="fas fa-edit mr-2"></i>
        Editar
    </a>
    @if($turma->classe < 12)
    <form method="POST" action="{{ route('turmas.promover', $turma) }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-success" 
                onclick="return confirm('Deseja promover esta turma para a {{ $turma->classe + 1 }}a classe?')">
            <i class="fas fa-arrow-up mr-2"></i>
            Promover Turma
        </button>
    </form>
    @endif
</div>
@endsection

@push('head-scripts')
<!-- Alpine.js DEVE ser carregado ANTES do conteúdo -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Conteúdo Principal -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ tab: 'alunos' }">
            
            <!-- Tab Headers -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="tab = 'alunos'" 
                            :class="tab === 'alunos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                        <i class="fas fa-users mr-2"></i>
                        Alunos ({{ $turma->total_alunos }})
                    </button>
                    <button @click="tab = 'disciplinas'" 
                            :class="tab === 'disciplinas' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                        <i class="fas fa-book mr-2"></i>
                        Disciplinas ({{ $turma->disciplinas->count() }})
                    </button>
                    <button @click="tab = 'professores'" 
                            :class="tab === 'professores' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>
                        Professores
                    </button>
                    <button @click="tab = 'estatisticas'" 
                            :class="tab === 'estatisticas' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Estatísticas
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">

                <!-- Tab Alunos -->
                <div x-show="tab === 'alunos'">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Alunos Matriculados</h3>
                        <button onclick="toggleModal('matricularModal')" 
                                class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus mr-2"></i>
                            Matricular Aluno
                        </button>
                    </div>

                    @if($turma->alunos->where('pivot.status', 'matriculado')->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Processo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Matrícula</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($turma->alunos->where('pivot.status', 'matriculado') as $aluno)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('users.show', $aluno) }}" class="font-medium text-primary-600 hover:text-primary-900">
                                            {{ $aluno->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $aluno->numero_processo }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($aluno->pivot->data_matricula)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <x-badge type="success">{{ ucfirst($aluno->pivot->status) }}</x-badge>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('turmas.remover-aluno', [$turma, $aluno]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Deseja remover este aluno da turma?')">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Nenhum aluno matriculado</p>
                        <button onclick="toggleModal('matricularModal')" 
                                class="btn btn-primary mt-4">
                            <i class="fas fa-user-plus mr-2"></i>
                            Matricular Primeiro Aluno
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Tab Disciplinas -->
                <div x-show="tab === 'disciplinas'" x-cloak>
                    <h3 class="text-lg font-semibold mb-4">Disciplinas da Turma</h3>
                    
                    @if($turma->disciplinas->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($turma->disciplinas as $disciplina)
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-500 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $disciplina->nome }}</h4>
                                    <p class="text-sm text-gray-500">{{ $disciplina->codigo }}</p>
                                </div>
                                @if($disciplina->disciplina_terminal)
                                <x-badge type="warning">Terminal</x-badge>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Nenhuma disciplina atribuída</p>
                    </div>
                    @endif
                </div>

                <!-- Tab Professores -->
                <div x-show="tab === 'professores'" x-cloak>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Atribuições de Professores</h3>
                        <button onclick="toggleModal('atribuirModal')" 
                                class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus mr-2"></i>
                            Atribuir Professor
                        </button>
                    </div>

                    @if($turma->atribuicoes->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Professor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($turma->atribuicoes as $atrib)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('users.show', $atrib->professor) }}" class="font-medium text-primary-600">
                                            {{ $atrib->professor->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">{{ $atrib->disciplina->nome }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('turmas.remover-professor', [$turma, $atrib->id]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Deseja remover esta atribuição?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-chalkboard-teacher text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Nenhum professor atribuído</p>
                    </div>
                    @endif
                </div>

                <!-- Tab Estatísticas -->
                <div x-show="tab === 'estatisticas'" x-cloak>
                    <h3 class="text-lg font-semibold mb-4">Estatísticas da Turma</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-primary-50 rounded-lg p-4">
                            <div class="text-sm text-primary-700 mb-1">Ocupação</div>
                            <div class="text-2xl font-bold text-primary-900">
                                {{ $turma->total_alunos > 0 ? number_format(($turma->total_alunos / $turma->capacidade) * 100, 1) : 0 }}%
                            </div>
                            <div class="text-xs text-primary-600 mt-1">{{ $turma->total_alunos }} / {{ $turma->capacidade }} alunos</div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-green-700 mb-1">Disciplinas</div>
                            <div class="text-2xl font-bold text-green-900">{{ $turma->disciplinas->count() }}</div>
                            <div class="text-xs text-green-600 mt-1">cadastradas</div>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm text-blue-700 mb-1">Professores</div>
                            <div class="text-2xl font-bold text-blue-900">{{ $turma->atribuicoes->unique('professor_id')->count() }}</div>
                            <div class="text-xs text-blue-600 mt-1">atribuídos</div>
                        </div>

                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-sm text-purple-700 mb-1">Ano Letivo</div>
                            <div class="text-lg font-bold text-purple-900">{{ $turma->anoLetivo->nome }}</div>
                            <div class="text-xs text-purple-600 mt-1">
                                <x-badge type="{{ $turma->anoLetivo->ativo ? 'success' : 'gray' }}">
                                    {{ $turma->anoLetivo->ativo ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Info Card -->
        <x-card title="Informações" icon="fas fa-info-circle">
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-600">Curso:</span>
                    <p class="font-semibold text-gray-900">{{ $turma->curso->nome }}</p>
                </div>
                <div>
                    <span class="text-gray-600">Classe:</span>
                    <p class="font-semibold text-gray-900">{{ $turma->classe }}ª Classe</p>
                </div>
                <div>
                    <span class="text-gray-600">Coordenador:</span>
                    <p class="font-semibold text-gray-900">
                        @if($turma->coordenador)
                        <a href="{{ route('users.show', $turma->coordenador) }}" class="text-primary-600">
                            {{ $turma->coordenador->name }}
                        </a>
                        @else
                        Sem coordenador
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-gray-600">Capacidade:</span>
                    <p class="font-semibold text-gray-900">{{ $turma->capacidade }} alunos</p>
                </div>
                <div>
                    <span class="text-gray-600">Vagas:</span>
                    <p class="font-semibold {{ $turma->hasVagas() ? 'text-green-600' : 'text-red-600' }}">
                        {{ $turma->capacidade - $turma->total_alunos }} disponíveis
                    </p>
                </div>
                <div>
                    <span class="text-gray-600">Status:</span>
                    <x-badge type="{{ $turma->ativo ? 'success' : 'danger' }}">
                        {{ $turma->ativo ? 'Ativa' : 'Inativa' }}
                    </x-badge>
                </div>
            </div>
        </x-card>

        <!-- Quick Actions -->
        <x-card title="Ações Rápidas" icon="fas fa-bolt">
<div class="space-y-2">

    {{-- NOTAS --}}
    <a href="{{ route('notas.index', ['turma_id' => $turma->id]) }}"
       class="btn btn-primary w-full flex items-center justify-center">
        <i class="fas fa-clipboard-list mr-2"></i>
        <span>Notas</span>
    </a>

    {{-- CONSOLIDADO --}}
    <a href="{{ route('relatorios.consolidado', $turma) }}"
       class="btn btn-outline w-full flex items-center justify-center">
        <i class="fas fa-file-alt mr-2"></i>
        <span>Consolidado</span>
    </a>

    {{-- ATIVAR / DESATIVAR --}}
    <form method="POST" action="{{ route('turmas.toggle-status', $turma) }}">
        @csrf
        <button type="submit"
            class="btn w-full flex items-center justify-center
                {{ $turma->ativo ? 'bg-red-600 hover:bg-red-700 text-white' : 'btn-outline' }}">
            <i class="fas fa-{{ $turma->ativo ? 'ban' : 'check' }} mr-2"></i>
            <span>{{ $turma->ativo ? 'Desativar' : 'Ativar' }}</span>
        </button>
    </form>

</div>
        </x-card>

    </div>

</div>

<!-- Modal Matricular Aluno -->
<div id="matricularModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="if(event.target === this) toggleModal('matricularModal')">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Matricular Aluno</h3>
            <button onclick="toggleModal('matricularModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('turmas.matricular-aluno', $turma) }}">
            @csrf
            <div class="mb-4">
                <label class="label">Aluno</label>
                <select name="aluno_id" class="input" required>
                    <option value="">Selecione...</option>
                    @foreach($alunosDisponiveis as $aluno)
                    <option value="{{ $aluno->id }}">{{ $aluno->name }} - {{ $aluno->numero_processo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="label">Data de Matrícula</label>
                <input type="date" name="data_matricula" value="{{ date('Y-m-d') }}" class="input" required>
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="btn btn-primary flex-1">Matricular</button>
                <button type="button" onclick="toggleModal('matricularModal')" class="btn btn-outline flex-1">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Atribuir Professor -->
<div id="atribuirModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="if(event.target === this) toggleModal('atribuirModal')">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Atribuir Professor</h3>
            <button onclick="toggleModal('atribuirModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('turmas.atribuir-professor', $turma) }}">
            @csrf
            <div class="mb-4">
                <label class="label">Professor</label>
                <select name="professor_id" class="input" required>
                    <option value="">Selecione...</option>
                    @foreach($professores as $prof)
                    <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="label">Disciplina</label>
                <select name="disciplina_id" class="input" required>
                    <option value="">Selecione...</option>
                    @foreach($turma->disciplinas as $disc)
                    <option value="{{ $disc->id }}">{{ $disc->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="btn btn-primary flex-1">Atribuir</button>
                <button type="button" onclick="toggleModal('atribuirModal')" class="btn btn-outline flex-1">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.toggle('hidden');
    }
}
</script>
@endpush