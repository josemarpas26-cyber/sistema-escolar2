@extends('layouts.app')

@section('page-title', 'Utilizadores')

@section('header-actions')
<a href="{{ route('users.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i>
    Novo Utilizador
</a>
@endsection

@section('content')

<!-- Filters -->
<div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px;box-shadow:var(--shadow-sm);">
    <form method="GET" action="{{ route('users.index') }}" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
        @csrf
        <div>
            <label class="label">Pesquisar</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Nome, email, BI..." class="input">
        </div>
        <div>
            <label class="label">Papel</label>
            <select name="role_id" class="input">
                <option value="">Todos os papéis</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                    {{ $role->display_name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Estado</label>
            <select name="ativo" class="input">
                <option value="">Todos</option>
                <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
                <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrar
            </button>
            @if(request()->hasAny(['search','role_id','ativo']))
            <a href="{{ route('users.index') }}" class="btn btn-outline" title="Limpar">
                <i class="fas fa-times"></i>
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Table -->
<div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;">

    @if($users->count() > 0)
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--gray-50);border-bottom:1px solid var(--surface-border);">
                    <th style="padding:10px 20px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Utilizador</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Email / Contacto</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Papel</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Estado</th>
                    <th style="padding:10px 20px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr style="border-bottom:1px solid var(--gray-100);" onmouseover="this.style.background='var(--blue-50,#eff6ff)'" onmouseout="this.style.background=''">
                    <td style="padding:12px 20px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $user->foto_perfil_url }}"
                                 alt="{{ $user->name }}"
                                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--surface-border);">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);">{{ $user->name }}</div>
                                @if($user->numero_processo)
                                <div style="font-size:11.5px;color:var(--text-tertiary);font-family:'JetBrains Mono',monospace;">{{ $user->numero_processo }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px 16px;">
                        <div style="font-size:13px;color:var(--text-primary);">{{ $user->email ?? '—' }}</div>
                        @if($user->telefone)
                        <div style="font-size:11.5px;color:var(--text-tertiary);">{{ $user->telefone }}</div>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:center;">
                        <x-badge type="primary">{{ $user->role->display_name }}</x-badge>
                    </td>
                    <td style="padding:12px 16px;text-align:center;">
                        <x-badge type="{{ $user->ativo ? 'success' : 'danger' }}">
                            {{ $user->ativo ? 'Ativo' : 'Inativo' }}
                        </x-badge>
                    </td>
                    <td style="padding:12px 20px;text-align:right;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                            <a href="{{ route('users.show', $user) }}"
                               style="width:32px;height:32px;border-radius:8px;background:var(--gray-100);color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-size:13px;transition:all .15s;"
                               onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb'"
                               onmouseout="this.style.background='var(--gray-100)';this.style.color='var(--text-secondary)'"
                               title="Ver perfil">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if(auth()->user()->isAdmin() || auth()->user()->isSecretaria())
                            <a href="{{ route('users.edit', $user) }}"
                               style="width:32px;height:32px;border-radius:8px;background:var(--gray-100);color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-size:13px;transition:all .15s;"
                               onmouseover="this.style.background='#dbeafe';this.style.color='#1d4ed8'"
                               onmouseout="this.style.background='var(--gray-100)';this.style.color='var(--text-secondary)'"
                               title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            @endif

                            <form method="POST" action="{{ route('users.toggle-status', $user) }}" style="display:inline;">
                                @csrf
                                <button type="submit"
                                        style="width:32px;height:32px;border-radius:8px;background:var(--gray-100);color:var(--text-secondary);border:none;display:inline-flex;align-items:center;justify-content:center;font-size:13px;cursor:pointer;transition:all .15s;"
                                        onmouseover="this.style.background='{{ $user->ativo ? '#fef3c7' : '#dcfce7' }}';this.style.color='{{ $user->ativo ? '#92400e' : '#15803d' }}'"
                                        onmouseout="this.style.background='var(--gray-100)';this.style.color='var(--text-secondary)'"
                                        title="{{ $user->ativo ? 'Desativar' : 'Ativar' }}">
                                    <i class="fas fa-{{ $user->ativo ? 'ban' : 'check' }}"></i>
                                </button>
                            </form>

                            @if($user->id !== auth()->id())
                            <button onclick="confirmDelete('delete-{{ $user->id }}', 'Deletar {{ addslashes($user->name) }}?')"
                                    style="width:32px;height:32px;border-radius:8px;background:var(--gray-100);color:var(--text-secondary);border:none;display:inline-flex;align-items:center;justify-content:center;font-size:13px;cursor:pointer;transition:all .15s;"
                                    onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'"
                                    onmouseout="this.style.background='var(--gray-100)';this.style.color='var(--text-secondary)'"
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                            <form id="delete-{{ $user->id }}" method="POST"
                                  action="{{ route('users.destroy', $user) }}" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="padding:12px 20px;border-top:1px solid var(--surface-border);">
        {{ $users->links('vendor.pagination.tailwind') }}
    </div>

    @else
    <!-- Empty State -->
    <div style="text-align:center;padding:64px 24px;">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--gray-100);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <i class="fas fa-users" style="font-size:24px;color:var(--text-tertiary);"></i>
        </div>
        <h3 style="font-size:16px;font-weight:700;color:var(--text-primary);margin:0 0 6px;">Nenhum utilizador encontrado</h3>
        <p style="font-size:13.5px;color:var(--text-tertiary);margin:0 0 20px;">Tente ajustar os filtros ou crie um novo utilizador.</p>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Criar Utilizador
        </a>
    </div>
    @endif

</div>

@endsection