@extends('layouts.app')

@section('page-title', 'Detalhes do Utilizador')

@section('header-actions')
@if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
<a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
    <i class="fas fa-edit mr-2"></i>
    Editar
</a>
@endif
@endsection

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Conteúdo principal ── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Dados Pessoais --}}
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
                    <p class="text-sm text-gray-600 mb-1">Género</p>
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

        {{-- Dados do Aluno --}}
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

        {{-- Histórico de turmas --}}
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
                                <a href="{{ route('turmas.show', $turma) }}" class="text-primary-600">
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

        {{-- Atribuições do professor --}}
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
                                <a href="{{ route('turmas.show', $atrib->turma) }}" class="text-primary-600">
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

    {{-- ── Sidebar ── --}}
    <div class="space-y-6">

        {{-- Foto e papel --}}
        <x-card title="Perfil" icon="fas fa-id-card">
            <div class="text-center">
                <img src="{{ $user->foto_perfil_url }}"
                     alt="{{ $user->name }}"
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

        {{-- Ações — apenas ADM e Secretária --}}
        @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
        <x-card title="Ações" icon="fas fa-bolt">
            <div class="space-y-2">

                <a href="{{ route('users.edit', $user) }}"
                   class="w-full inline-flex items-center justify-center gap-2
                          px-4 py-2.5 rounded-xl
                          bg-gradient-to-r from-indigo-600 to-purple-600
                          hover:from-indigo-700 hover:to-purple-700
                          text-white font-semibold shadow-md
                          transition-all duration-200 ease-in-out
                          hover:scale-[1.02] active:scale-[0.98]">
                    <i class="fas fa-edit text-sm"></i>
                    <span>Editar</span>
                </a>

                {{-- Boletim: ADM, Secretária e Professor das turmas do aluno --}}
                @if($user->isAluno())
                    @php
                        $podeVerBoletim = auth()->user()->isAdmin() || auth()->user()->isSecretaria();
                        if (!$podeVerBoletim && auth()->user()->isProfessor()) {
                            $turmaDoAluno = $user->turmas->where('pivot.status', 'matriculado')->first();
                            if ($turmaDoAluno) {
                                $podeVerBoletim = auth()->user()->atribuicoes()
                                    ->where('turma_id', $turmaDoAluno->id)
                                    ->exists();
                            }
                        }
                    @endphp

                    @if($podeVerBoletim)
                    <a href="{{ route('relatorios.boletim', $user) }}"
                       class="w-full inline-flex items-center justify-center gap-2
                              px-4 py-2.5 rounded-xl
                              bg-blue-600 hover:bg-blue-700
                              text-white font-semibold shadow-sm
                              transition-all duration-200
                              hover:scale-[1.02] active:scale-[0.98]">
                        <i class="fas fa-file-pdf text-sm"></i>
                        <span>Ver Boletim</span>
                    </a>
                    @endif
                @endif

                {{-- Ativar/Desativar --}}
                <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2
                                   px-4 py-2.5 rounded-xl font-semibold text-white shadow-sm
                                   transition-all duration-200
                                   hover:scale-[1.02] active:scale-[0.98]
                                   {{ $user->ativo ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-600 hover:bg-green-700' }}">
                        <i class="fas fa-{{ $user->ativo ? 'ban' : 'check' }} text-sm"></i>
                        <span>{{ $user->ativo ? 'Desativar' : 'Ativar' }}</span>
                    </button>
                </form>

                {{-- Deletar: nunca a si mesmo, Secretária não pode deletar ADM --}}
                @if($user->id !== auth()->id() && !($user->isAdmin() && !auth()->user()->isAdmin()))
                <form method="POST"
                      action="{{ route('users.destroy', $user) }}"
                      onsubmit="return confirm('Deletar {{ addslashes($user->name) }}?\n\nPoderá restaurar na Lixeira mais tarde.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2
                                   px-4 py-2.5 rounded-xl
                                   bg-red-600 hover:bg-red-700
                                   text-white font-semibold shadow-sm
                                   transition-all duration-200
                                   hover:scale-[1.02] active:scale-[0.98]">
                        <i class="fas fa-trash text-sm"></i>
                        <span>Deletar</span>
                    </button>
                </form>
                @endif

            </div>
        </x-card>
        @endif

    </div>

</div>

@endsection