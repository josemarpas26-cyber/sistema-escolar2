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
         @csrf
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Pesquisar por nome ou n.º de processo..." class="input">
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

@if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
<x-card class="mb-6 foco-wrap">
    <div class="foco-header mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Modos Foco</h3>
            <p class="text-sm text-gray-500">Ações rápidas em massa com feedback inteligente para evitar operações redundantes.</p>
        </div>
        <span class="foco-counter text-xs text-gray-600">
            <i class="fas fa-users mr-1"></i>
            Selecionados: <strong id="focus-selected-count">0</strong>
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <form method="POST" action="{{ route('focus.matricular-alunos') }}" class="space-y-2 focus-form foco-action-card">
            @csrf
            <h4 class="font-medium text-gray-900"><i class="fas fa-user-check mr-1 text-emerald-600"></i> Modo Foco: Matrícula de múltiplos alunos</h4>
            <div class="focus-ids"></div>
            <select name="turma_id" class="input" required>
                <option value="">Selecionar turma</option>
                @foreach($turmas as $turma)
                    <option value="{{ $turma->id }}">{{ $turma->nome_completo }}</option>
                @endforeach
            </select>
            <input type="date" class="input" name="data_matricula" value="{{ now()->toDateString() }}">
            <button type="submit" class="btn btn-primary w-full">Aplicar matrícula em massa</button>
        </form>

        <form method="POST" action="{{ route('focus.atualizar-status') }}" class="space-y-2 focus-form foco-action-card">
            @csrf
            <h4 class="font-medium text-gray-900"><i class="fas fa-toggle-on mr-1 text-blue-600"></i> Modo Foco: Atualização em lote</h4>
            <div class="focus-ids"></div>
            <select name="ativo" class="input" required>
                <option value="1">Marcar como ativo</option>
                <option value="0">Marcar como inativo</option>
            </select>
            <button type="submit" class="btn btn-primary w-full">Atualizar selecionados</button>
        </form>

        <form method="POST" action="{{ route('focus.arquivar-usuarios') }}" class="space-y-2 focus-form foco-action-card" onsubmit="return confirm('Arquivar os registos selecionados? Esta ação pode ser revertida na Lixeira.')">
            @csrf
            @method('DELETE')
            <h4 class="font-medium text-gray-900"><i class="fas fa-box-archive mr-1 text-amber-600"></i> Modo Foco: Eliminação/arquivamento em massa</h4>
            <div class="focus-ids"></div>
            <button type="submit" class="btn btn-danger w-full">Arquivar selecionados</button>
        </form>

        <form method="POST" action="{{ route('focus.importar-alunos') }}" enctype="multipart/form-data" class="space-y-2 foco-action-card">
            @csrf
            <h4 class="font-medium text-gray-900"><i class="fas fa-file-import mr-1 text-violet-600"></i> Modo Foco: Importação em massa (Excel/CSV)</h4>
            <input type="file" name="ficheiro" class="input" accept=".csv,.txt,.xlsx,.xls" required>
            <p class="text-xs text-gray-500">Colunas mínimas: <code>name</code>, <code>numero_processo</code>, <code>bi</code>, <code>data_nascimento</code>.</p>
            <button type="submit" class="btn btn-primary w-full">Importar ficheiro</button>
        </form>
    </div>
</x-card>
@endif

{{-- Lista --}}
<x-card>
    @if($alunos->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                        <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">
                            <input type="checkbox" id="focus-select-all" class="rounded border-gray-300">
                        </th>
                    @endif
                    <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                    <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">Nº Processo</th>
                    <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-500 uppercase">Turma</th>
                    <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3.5 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
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
                    @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                        <td class="px-6 py-4 align-top">
                            <input type="checkbox" class="focus-item rounded border-gray-300" value="{{ $aluno->id }}">
                        </td>
                    @endif
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
                                  onsubmit="return confirm('Eliminar {{ addslashes($aluno->name) }}?\n\nO utilizador pode ser restaurado na Lixeira.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:text-red-900"
                                        title="Eliminar">
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
     {{ $alunos->links('vendor.pagination.tailwind') }}
    </div>

    @else
    <div class="text-center py-12">
        <i class="fas fa-user-graduate text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Nenhum aluno encontrado</p>
    </div>
    @endif
</x-card>

@if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
<style>
    .foco-wrap{
        background: linear-gradient(180deg, rgba(37,99,235,.04) 0%, rgba(37,99,235,.01) 100%);
        border: 1px solid rgba(37,99,235,.16);
    }
    .foco-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        flex-wrap:wrap;
    }
    .foco-counter{
        display:inline-flex;
        align-items:center;
        background:#fff;
        border:1px solid #dbeafe;
        border-radius:9999px;
        padding:.35rem .7rem;
        font-weight:600;
    }
    .foco-action-card{
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:14px;
        background:#fff;
        box-shadow:0 1px 2px rgba(15,23,42,.04);
        transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
    }
    .foco-action-card:hover{
        border-color:#cbd5e1;
        box-shadow:0 8px 18px rgba(15,23,42,.08);
        transform:translateY(-1px);
    }
    .dark .foco-wrap{
        background: linear-gradient(180deg, rgba(56,139,253,.12) 0%, rgba(56,139,253,.04) 100%);
        border-color:#334155;
    }
    .dark .foco-counter,
    .dark .foco-action-card{
        background:#0f172a;
        border-color:#334155;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('focus-select-all');
        const items = Array.from(document.querySelectorAll('.focus-item'));
        const hiddenFields = Array.from(document.querySelectorAll('.focus-ids'));
        const countElement = document.getElementById('focus-selected-count');

        const syncSelection = () => {
            const selectedIds = items.filter((item) => item.checked).map((item) => Number(item.value));
            hiddenFields.forEach((field) => {
                field.innerHTML = '';
                selectedIds.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'user_ids[]';
                    input.value = id;
                    field.appendChild(input);
                });
            });
            countElement.textContent = String(selectedIds.length);
        };

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                items.forEach((item) => {
                    item.checked = this.checked;
                });
                syncSelection();
            });
        }

        items.forEach((item) => {
            item.addEventListener('change', syncSelection);
        });

        document.querySelectorAll('.focus-form').forEach((form) => {
            form.addEventListener('submit', function (event) {
                const selected = items.some((item) => item.checked);
                if (!selected) {
                    event.preventDefault();
                    alert('Selecione pelo menos um aluno para usar este Modo Foco.');
                }
            });
        });
    });
</script>
@endif

@endsection
