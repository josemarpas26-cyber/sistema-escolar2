@extends('layouts.app')

@section('page-title', 'Editar Nota')

@section('content')
@php
    $notaFinalizada = $nota->status === 'finalizado';
    $podeReabrirNota = auth()->user()?->hasPermission('notas.reabrir') ?? false;
    $somenteLeitura = $notaFinalizada && !$podeReabrirNota;
    $bloquearMacDireta = auth()->user()?->isAdmin() || auth()->user()?->isSecretaria();
    $camposCaSomenteLeitura = $camposCaSomenteLeitura ?? ['ca_10' => false, 'ca_11' => false];
@endphp

<div class="max-w-4xl mx-auto">

    <!-- Info do Aluno e Disciplina -->
    <x-card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $nota->aluno->name }}</h3>
                <p class="text-sm text-gray-600">
                    {{ $nota->turma->nome_completo }} | {{ $nota->disciplina->nome }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Nº Processo: {{ $nota->aluno->numero_processo }} | Ano: {{ $nota->anoLetivo->nome }}
                </p>
            </div>
            <div>
                @if($nota->cfd)
                <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}" class="text-lg">
                    CFD: {{ number_format($nota->cfd, 2) }}
                </x-badge>
                @endif
            </div>
        </div>
    </x-card>
    @if($somenteLeitura)
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-sm text-yellow-800">Esta nota foi finalizada e está em modo somente leitura.</p>
    </div>
    @endif
    <!-- Formulário de Edição -->
    <form method="POST" action="{{ route('notas.update', $nota) }}">
        @csrf
        @method('PUT')

        <!-- 1º Trimestre -->
        <x-card title="1º Trimestre" icon="fas fa-calendar-alt" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="label">MAC1</label>
                    <input type="number" name="mac1" value="{{ old('mac1', $nota->mac1) }}" 
                            step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura || $bloquearMacDireta ? 'disabled' : '' }}>
                    @if($bloquearMacDireta)<p class="text-xs text-gray-500 mt-1">MAC é calculada automaticamente pelas avaliações contínuas.</p>@endif
                    @error('mac1')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">PP1</label>
                    <input type="number" name="pp1" value="{{ old('pp1', $nota->pp1) }}" 
                            step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura ? 'disabled' : '' }}>
                    @error('pp1')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">PT1</label>
                    <input type="number" name="pt1" value="{{ old('pt1', $nota->pt1) }}" 
                            step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura ? 'disabled' : '' }}>
                    @error('pt1')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">MT1 (Calculado)</label>
                    <input type="text" value="{{ $nota->mt1 ? number_format($nota->mt1, 2) : '-' }}" 
                           class="input bg-gray-100" readonly>
                </div>
            </div>
        </x-card>

        <!-- 2º Trimestre -->
        <x-card title="2º Trimestre" icon="fas fa-calendar-alt" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="label">MAC2</label>
                    <input type="number" name="mac2" value="{{ old('mac2', $nota->mac2) }}" 
                          step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura || $bloquearMacDireta ? 'disabled' : '' }}>
                    @if($bloquearMacDireta)<p class="text-xs text-gray-500 mt-1">MAC é calculada automaticamente pelas avaliações contínuas.</p>@endif
                    @error('mac2')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">PP2</label>
                    <input type="number" name="pp2" value="{{ old('pp2', $nota->pp2) }}" 
                            step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura ? 'disabled' : '' }}>
                    @error('pp2')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">PT2</label>
                    <input type="number" name="pt2" value="{{ old('pt2', $nota->pt2) }}" 
                           step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura ? 'disabled' : '' }}>
                    @error('pt2')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">MT2 (Calculado)</label>
                    <input type="text" value="{{ $nota->mt2 ? number_format($nota->mt2, 2) : '-' }}" 
                           class="input bg-gray-100" readonly>
                </div>
                <div>
                    <label class="label">MFT2 (Calculado)</label>
                    <input type="text" value="{{ $nota->mft2 ? number_format($nota->mft2, 2) : '-' }}" 
                           class="input bg-gray-100" readonly>
                </div>
            </div>
        </x-card>

        <!-- 3º Trimestre -->
        <x-card title="3º Trimestre" icon="fas fa-calendar-alt" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="label">MAC3</label>
                    <input type="number" name="mac3" value="{{ old('mac3', $nota->mac3) }}" 
                                 step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura || $bloquearMacDireta ? 'disabled' : '' }}>
                    @if($bloquearMacDireta)<p class="text-xs text-gray-500 mt-1">MAC é calculada automaticamente pelas avaliações contínuas.</p>@endif
                    @error('mac3')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">PP3</label>
                    <input type="number" name="pp3" value="{{ old('pp3', $nota->pp3) }}" 
                           step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura ? 'disabled' : '' }}>
                    @error('pp3')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">PG (Prova Global)</label>
                    <input type="number" name="pg" value="{{ old('pg', $nota->pg) }}" 
                           step="0.01" min="-1" max="20" class="input" onblur="formatNota(this)" {{ $somenteLeitura ? 'disabled' : '' }}>
                    @error('pg')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

        </x-card>

        <!-- Classificações Anuais (destaque para transferência) -->
        @if($nota->turma->classe > 10)
        <x-card title="Classificações Anuais (CA)" icon="fas fa-star" class="mb-6 border-2 border-blue-200">
            <div class="mb-4 rounded-lg bg-blue-50 border border-blue-100 p-3 text-sm text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                Este bloco é usado pela administração/secretaria para lançar CAs anteriores de alunos transferidos.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($nota->turma->classe >= 11)
                <div>
                    <label class="label">CA da 10ª <span class="text-blue-700 font-semibold">(Destaque)</span></label>
                    <input type="number" name="ca_10" value="{{ old('ca_10', $nota->ca_10) }}"
                           step="0.01" min="-1" max="20" class="input border-blue-300 focus:ring-blue-400"
                           onblur="formatNota(this)" {{ ($somenteLeitura || $camposCaSomenteLeitura['ca_10']) ? 'disabled' : '' }}>
                    @if($camposCaSomenteLeitura['ca_10'])
                        <p class="text-xs text-gray-500 mt-1">Somente leitura: aluno já associado a turma da 10ª no ano anterior.</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Preencher manualmente para transferido sem histórico interno da 10ª. Alterações ficam auditadas nos logs.</p>
                    @endif
                </div>
                @endif

                @if($nota->turma->classe == 12)
                <div>
                    <label class="label">CA da 11ª <span class="text-blue-700 font-semibold">(Destaque)</span></label>
                    <input type="number" name="ca_11" value="{{ old('ca_11', $nota->ca_11) }}"
                           step="0.01" min="-1" max="20" class="input border-blue-300 focus:ring-blue-400"
                           onblur="formatNota(this)" {{ ($somenteLeitura || $camposCaSomenteLeitura['ca_11']) ? 'disabled' : '' }}>
                    @if($camposCaSomenteLeitura['ca_11'])
                        <p class="text-xs text-gray-500 mt-1">Somente leitura: aluno já associado a turma da 11ª no ano anterior.</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Preencher manualmente para transferido sem histórico interno da 11ª. Alterações ficam auditadas nos logs.</p>
                    @endif
                </div>
                @endif
            </div>
        </x-card>
        @endif

        <!-- Classificações Finais -->
        <x-card title="Classificações Finais" icon="fas fa-check-circle" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="label">MT3</label>
                    <input type="text" value="{{ $nota->mt3 ? number_format($nota->mt3, 2) : '-' }}" 
                           class="input bg-gray-100" readonly>
                </div>
                <div>
                    <label class="label">CF (Calculado)</label>
                    <input type="text" value="{{ $nota->cf ? number_format($nota->cf, 2) : '-' }}" 
                           class="input bg-gray-100" readonly>
                </div>
                <div>
                    <label class="label">CFD (Final)</label>
                    <input type="text" 
                           value="{{ $nota->cfd ? number_format($nota->cfd, 2) : '-' }}" 
                           class="input bg-gray-100 font-bold {{ $nota->cfd && $nota->cfd >= 10 ? 'text-green-600' : 'text-red-600' }}" 
                           readonly>
                </div>
                <div>
                    <label class="label">Status</label>
                    @if($nota->cfd)
                    <div class="mt-2">
                        <x-badge type="{{ $nota->isAprovado() ? 'success' : 'danger' }}" class="text-sm">
                            {{ $nota->isAprovado() ? 'Aprovado' : 'Reprovado' }}
                        </x-badge>
                    </div>
                    @else
                    <div class="mt-2">
                        <x-badge type="gray" class="text-sm">Pendente</x-badge>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Aviso -->
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                    <p class="text-sm text-blue-800">
                        As médias (MT1, MT2, MT3, MFT2, CF, CFD) são calculadas automaticamente ao guardar.
                    </p>
                </div>
            </div>
        </x-card>

        <!-- Ações -->
        <div class="flex justify-end space-x-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar
            </a>
             @if(!$somenteLeitura)
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar Alterações
            </button>
            @endif
        </div>

    </form>

</div>

@endsection

@push('scripts')
<script>
function formatNota(input) {
    let value = parseFloat(input.value.replace(',', '.'));
    if (isNaN(value) || value < 0) {
        input.value = '';
    } else if (value > 20) {
        input.value = '20.00';
    } else {
        input.value = value.toFixed(2);
    }
}
</script>
@endpush
