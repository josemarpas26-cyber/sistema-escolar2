@extends('layouts.app')

@section('page-title', 'Detalhes do Usuário')

@section('header-actions')
<a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
    <i class="fas fa-edit mr-2"></i>
    Editar
</a>
@endsection

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Info Principal -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Dados Pessoais -->
        <x-card title="Informações Pessoais" icon="fas fa-user">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div>
                    <p class="text-sm text-gray-600 mb-1">Nome Completo</p>
                    <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">Email</p>
                    <p class="font-semibold text-gray-900">{{ $user->email }}</p>
                </div>

                @if($user->bi)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Bilhete de Identidade</p>
                    <p class="font-semibold text-gray-900">{{ $user->bi }}</p>
                </div>
                @endif

                @if($user->data_nascimento)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Data de Nascimento</p>
                    <p class="font-semibold text-gray-900">{{ $user->data_nascimento->format('d/m/Y') }}</p>
                </div>
                @endif

                @if($user->genero)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Gênero</p>
                    <p class="font-semibold text-gray-900">{{ $user->genero == 'M' ? 'Masculino' : 'Feminino' }}</p>
                </div>
                @endif

                @if($user->telefone)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Telefone</p>
                    <p class="font-semibold text-gray-900">{{ $user->telefone }}</p>
                </div>
                @endif

                @if($user->endereco)
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600 mb-1">Endereço</p>
                    <p class="font-semibold text-gray-900">{{ $user->endereco }}</p>
                </div>
                @endif

            </div>
        </x-card>

        <!-- Se for Aluno -->
        @if($user->isAluno())
        <x-card title="Dados do Aluno" icon="fas fa-graduation-cap">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                @if($user->numero_processo)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Número de Processo</p>
                    <p class="font-semibold text-gray-900">{{ $user->numero_processo }}</p>
                </div>
                @endif

                @if($user->turmas->isNotEmpty())
                <div>
                    <p class="text-sm text-gray-600 mb-1">Turma Atual</p>
                    <p class="font-semibold text-gray-900">
                        {{ $user->turmas->where('pivot.status', 'matriculado')->first()?->nome_completo ?? 'Sem turma' }}
                    </p>
                </div>
                @endif

                @if($user->nome_encarregado)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Encarregado</p>
                    <p class="font-semibold text-gray-900">{{ $user->nome_encarregado }}</p>
                </div>
                @endif

                @if($user->contacto_encarregado)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Contacto do Encarregado</p>
                    <p class="font-semibold text-gray-900">{{ $user->contacto_encarregado }}</p>
                </div>
                @endif

            </div>
        </x-card>

        <!-- Turmas do Aluno -->
        @if($user->turmas->isNotEmpty())
        <x-card title="Histórico de Turmas" icon="fas fa-history">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ano Letivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrícula</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($user->turmas as $turma)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('turmas.show', $turma) }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $turma->nome_completo }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $turma->curso->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $turma->anoLetivo->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge type="{{ $turma->pivot->status == 'matriculado' ? 'success' : 'gray' }}">
                                    {{ ucfirst($turma->pivot->status) }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($turma->pivot->data_matricula)->format('d/m/Y') }}
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif
        @endif

        <!-- Se for Professor -->
        @if($user->isProfessor() && $user->atribuicoes->isNotEmpty())
        <x-card title="Atribuições" icon="fas fa-chalkboard-teacher">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ano Letivo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($user->atribuicoes as $atrib)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('turmas.show', $atrib->turma) }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $atrib->turma->nome_completo }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $atrib->disciplina->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $atrib->anoLetivo->nome }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif

    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Foto e Info -->
        <x-card title="Perfil" icon="fas fa-id-card">
            <div class="text-center">
                <img src="{{ $user->foto_perfil_url }}" alt="{{ $user->name }}" 
                     class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-primary-200 mb-4">
                
                <h3 class="font-bold text-lg text-gray-900 mb-1">{{ $user->name }}</h3>
                <x-badge type="primary" class="text-sm">{{ $user->role->display_name }}</x-badge>
                
                <div class="mt-4 pt-4 border-t border-gray-200 text-left space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Status:</span>
                        <x-badge type="{{ $user->ativo ? 'success' : 'danger' }}">
                            {{ $user->ativo ? 'Ativo' : 'Inativo' }}
                        </x-badge>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Cadastro:</span>
                        <span class="font-semibold">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Ações Rápidas -->
        <x-card title="Ações" icon="fas fa-bolt">
            <div class="space-y-2">
                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary w-full">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>

                @if($user->isAluno())
                <a href="{{ route('relatorios.boletim', $user) }}" class="btn btn-outline w-full">
                    <i class="fas fa-file-pdf mr-2"></i>
                    Ver Boletim
                </a>
                @endif

                <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline w-full {{ $user->ativo ? 'text-yellow-600' : 'text-green-600' }}">
                        <i class="fas fa-{{ $user->ativo ? 'ban' : 'check' }} mr-2"></i>
                        {{ $user->ativo ? 'Desativar' : 'Ativar' }}
                    </button>
                </form>

                @if($user->id !== auth()->id())
                <button onclick="confirmDelete('delete-form', 'Deseja deletar {{ $user->name }}?')" 
                        class="btn btn-danger w-full">
                    <i class="fas fa-trash mr-2"></i>
                    Deletar Usuário
                </button>
                <form id="delete-form" method="POST" action="{{ route('users.destroy', $user) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            </div>
        </x-card>

    </div>

</div>

@endsection
