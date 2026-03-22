@extends('layouts.app')

@section('page-title', 'Alunos')

@section('header-actions')
@if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
<a href="{{ route('users.create') }}" class="btn btn-primary">
    <i class="fas fa-user-plus mr-2"></i>
    Novo Aluno
</a>
@endif
@endsection

@section('content')

{{-- Filtros --}}
<x-card class="mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Buscar por nome ou nº processo..." class="input">
        <select name="turma" class="input">
            <option value="">Todas as turmas</option>
            @foreach($turmas as $turma)
            <option value="{{ $turma->id }}" {{ request('turma') == $turma->id ? 'selected' : '' }}>
                {{ $turma->nome_completo }}
            </option>
            @endforeach
        </select>
        <select name="status" class="input">
            <option value="">Todos os status</option>
            <option value="ativo"   {{ request('status') == 'ativo'   ? 'selected' : '' }}>Ativo</option>
            <option value="inativo" {{ request('status') == 'inativo' ? 'selected' : '' }}>Inativo</option>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search mr-2"></i>
            Filtrar
        </button>
    </form>
</x-card>

{{-- Lista --}}
<x-card>
    @if($alunos->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Processo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turma</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($alunos as $aluno)
                @php
                    $turmaAtual = $aluno->turmas->where('pivot.status', 'matriculado')->first();

                    // Verificar se o professor pode ver o boletim deste aluno
                    $professorPodeVerBoletim = false;
                    if (auth()->user()->isProfessor() && $turmaAtual) {
                        $professorPodeVerBoletim = $turmasProfesor->contains($turmaAtual->id);
                    }

                    $podeVerBoletim = auth()->user()->isAdmin()
                        || auth()->user()->isSecretaria()
                        || $professorPodeVerBoletim;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $aluno->foto_perfil_url }}"
                                 class="w-10 h-10 rounded-full object-cover mr-3"
                                 alt="{{ $aluno->name }}">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $aluno->name }}</div>
                                <div class="text-sm text-gray-500">{{ $aluno->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-900">{{ $aluno->numero_processo ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($turmaAtual)
                            <x-badge type="info">{{ $turmaAtual->nome_completo }}</x-badge>
                        @else
                            <span class="text-gray-400 text-sm">Sem turma</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge type="{{ $aluno->ativo ? 'success' : 'danger' }}">
                            {{ $aluno->ativo ? 'Ativo' : 'Inativo' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">

                            {{-- Ver perfil — todos os papéis autorizados --}}
                            <a href="{{ route('users.show', $aluno) }}"
                               class="text-primary-600 hover:text-primary-900"
                               title="Ver perfil">
                                <i class="fas fa-eye"></i>
                            </a>

                            {{-- Editar — ADM e Secretária --}}
                            @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                            <a href="{{ route('users.edit', $aluno) }}"
                               class="text-blue-600 hover:text-blue-900"
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif

                            {{-- Boletim — ADM, Secretária e Professor das turmas do aluno --}}
                            @if($podeVerBoletim)
                            <a href="{{ route('relatorios.boletim', $aluno) }}"
                               class="text-green-600 hover:text-green-900"
                               title="Boletim">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            @endif

                            {{-- Deletar — apenas ADM e Secretária, nunca a si mesmo --}}
                            @if((auth()->user()->isAdmin() || auth()->user()->isSecretaria()) && $aluno->id !== auth()->id())
                            <form method="POST"
                                  action="{{ route('users.destroy', $aluno) }}"
                                  class="inline"
                                  onsubmit="return confirm('Deletar {{ addslashes($aluno->name) }}?\n\nO utilizador pode ser restaurado na Lixeira.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:text-red-900"
                                        title="Deletar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $alunos->links() }}
    </div>

    @else
    <div class="text-center py-12">
        <i class="fas fa-user-graduate text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhum aluno encontrado</p>
    </div>
    @endif
</x-card>

@endsection