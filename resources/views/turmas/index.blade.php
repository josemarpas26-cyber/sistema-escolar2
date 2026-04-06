@extends('layouts.app')
@section('page-title', 'Turmas')
@section('header-actions')
<a href="{{ route('turmas.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i>
    Nova Turma
</a>
@endsection

@section('content')

<div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;">

    @if($turmas->count() > 0)
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--gray-50);border-bottom:1px solid var(--surface-border);">
                    <th style="padding:10px 20px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Turma</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Curso</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Classe</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Alunos</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Ano Letivo</th>
                    <th style="padding:10px 20px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-tertiary);">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($turmas as $turma)
                @php
                    $pct = $turma->capacidade > 0 ? min(100, ($turma->total_alunos / $turma->capacidade) * 100) : 0;
                    $barColor = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#d97706' : '#16a34a');
                @endphp
                <tr style="border-bottom:1px solid var(--gray-100);" onmouseover="this.style.background='var(--blue-50,#eff6ff)'" onmouseout="this.style.background=''">
                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:var(--blue-50,#eff6ff);color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
                                {{ $turma->classe }}ª
                            </div>
                            <div>
                                <div style="font-size:13.5px;font-weight:700;color:var(--text-primary);">{{ $turma->nome }}</div>
                                @if($turma->coordenador)
                                <div style="font-size:11.5px;color:var(--text-tertiary);">Dir: {{ $turma->coordenador->name }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;">
                        <span style="font-size:13px;color:var(--text-secondary);">{{ $turma->curso->nome }}</span>
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        <x-badge type="primary">{{ $turma->classe }}ª</x-badge>
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                            <div style="font-size:13px;font-weight:600;color:var(--text-primary);">
                                {{ $turma->total_alunos }}<span style="color:var(--text-tertiary);font-weight:400;">/{{ $turma->capacidade }}</span>
                            </div>
                            <div style="width:64px;height:4px;background:var(--gray-200);border-radius:2px;overflow:hidden;">
                                <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:2px;transition:width .3s;"></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="font-size:13px;color:var(--text-secondary);">{{ $turma->anoLetivo->nome }}</div>
                        @if($turma->anoLetivo->ativo)
                        <div style="font-size:11px;color:#16a34a;font-weight:600;margin-top:1px;">● Ativo</div>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:right;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                            <a href="{{ route('turmas.show', $turma) }}"
                               style="width:32px;height:32px;border-radius:8px;background:var(--gray-100);color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;transition:all .15s;"
                               onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb'"
                               onmouseout="this.style.background='var(--gray-100)';this.style.color='var(--text-secondary)'"
                               title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('turmas.edit', $turma) }}"
                               style="width:32px;height:32px;border-radius:8px;background:var(--gray-100);color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;transition:all .15s;"
                               onmouseover="this.style.background='#dbeafe';this.style.color='#1d4ed8'"
                               onmouseout="this.style.background='var(--gray-100)';this.style.color='var(--text-secondary)'"
                               title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding:12px 20px;border-top:1px solid var(--surface-border);">
        {{ $turmas->links('vendor.pagination.tailwind') }}
    </div>

    @else
    <div style="text-align:center;padding:64px 24px;">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--gray-100);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <i class="fas fa-chalkboard" style="font-size:24px;color:var(--text-tertiary);"></i>
        </div>
        <h3 style="font-size:16px;font-weight:700;color:var(--text-primary);margin:0 0 6px;">Nenhuma turma cadastrada</h3>
        <p style="font-size:13.5px;color:var(--text-tertiary);margin:0 0 20px;">Crie a primeira turma do sistema.</p>
        <a href="{{ route('turmas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Criar Turma
        </a>
    </div>
    @endif

</div>
@endsection