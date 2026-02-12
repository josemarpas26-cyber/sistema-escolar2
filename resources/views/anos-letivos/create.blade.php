@extends('layouts.app')

@section('page-title', 'Novo Ano Letivo')

@section('content')

<form method="POST" action="{{ route('anos-letivos.store') }}">
    @csrf

    <div class="max-w-2xl mx-auto">
        <x-card title="Dados do Ano Letivo" icon="fas fa-calendar-alt">
            
            <div class="space-y-4">
                
                <!-- Nome -->
                <div>
                    <label class="label">Nome do Ano Letivo *</label>
                    <input type="text" name="nome" value="{{ old('nome') }}" 
                           placeholder="Ex: 2024/2025" class="input" required>
                    <p class="text-xs text-gray-500 mt-1">Formato recomendado: AAAA/AAAA</p>
                    @error('nome')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Datas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Data Início -->
                    <div>
                        <label class="label">Data de Início *</label>
                        <input type="date" name="data_inicio" value="{{ old('data_inicio') }}" class="input" required>
                        @error('data_inicio')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Data Fim -->
                    <div>
                        <label class="label">Data de Fim *</label>
                        <input type="date" name="data_fim" value="{{ old('data_fim') }}" class="input" required>
                        @error('data_fim')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <!-- Informações -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">Importante:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Apenas um ano letivo pode estar ativo por vez</li>
                                <li>Ao criar um novo ano, o atual será desativado automaticamente</li>
                                <li>A data de fim deve ser posterior à data de início</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="ativo" value="1" checked 
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm font-medium text-gray-700">
                            Ativar este ano letivo
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 ml-6 mt-1">
                        Se marcado, este ano será definido como o ano letivo ativo
                    </p>
                </div>

            </div>

            <!-- Ações -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('anos-letivos.index') }}" class="btn btn-outline">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Criar Ano Letivo
                </button>
            </div>

        </x-card>
    </div>

</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataInicio = document.querySelector('[name="data_inicio"]');
    const dataFim = document.querySelector('[name="data_fim"]');
    const nomeInput = document.querySelector('[name="nome"]');

    // Sugerir nome baseado nas datas
    function sugerirNome() {
        if (dataInicio.value && dataFim.value) {
            const anoInicio = new Date(dataInicio.value).getFullYear();
            const anoFim = new Date(dataFim.value).getFullYear();

            // Só sugere se anos consecutivos
            if (anoFim === anoInicio + 1) {
                nomeInput.value = `${anoInicio}/${anoFim}`;
                nomeInput.setCustomValidity(''); // ok
            } else {
                nomeInput.setCustomValidity('O ano final deve ser o ano seguinte ao inicial');
            }
        }
    }

    dataInicio.addEventListener('change', sugerirNome);
    dataFim.addEventListener('change', sugerirNome);

    // Validar que data fim é posterior à data início
    dataFim.addEventListener('change', function() {
        if (dataInicio.value && dataFim.value) {
            if (new Date(dataFim.value) <= new Date(dataInicio.value)) {
                alert('A data de fim deve ser posterior à data de início');
                dataFim.value = '';
            }
        }
    });

    // Validação manual caso o usuário digite no campo nome
    nomeInput.addEventListener('input', function() {
        const regex = /^20\d{2}\/20\d{2}$/;
        if (!regex.test(nomeInput.value)) {
            nomeInput.setCustomValidity('Formato inválido. Use AAAA/AAAA');
            return;
        }

        const [inicio, fim] = nomeInput.value.split('/').map(Number);
        if (fim !== inicio + 1) {
            nomeInput.setCustomValidity('O ano final deve ser o ano seguinte ao inicial');
        } else {
            nomeInput.setCustomValidity('');
        }
    });
});
</script>
@endpush
