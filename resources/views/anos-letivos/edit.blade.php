@extends('layouts.app')

@section('page-title', 'Editar Ano Letivo')

@section('content')

<form method="POST" action="{{ route('anos-letivos.update', $anoLetivo) }}">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Formulário -->
        <div class="lg:col-span-2">
            <x-card title="Dados do Ano Letivo" icon="fas fa-calendar-alt">
                
                <div class="space-y-4">
                    
                    <!-- Nome -->
                    <div>
                        <label class="label">Nome do Ano Letivo *</label>
                        <input type="text" name="nome" value="{{ old('nome', $anoLetivo->nome) }}" class="input" required>
                        @error('nome')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Datas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <div>
                            <label class="label">Data de Início *</label>
                            <input type="date" name="data_inicio" 
                                   value="{{ old('data_inicio', $anoLetivo->data_inicio->format('Y-m-d')) }}" 
                                   class="input" required>
                            @error('data_inicio')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">Data de Fim *</label>
                            <input type="date" name="data_fim" 
                                   value="{{ old('data_fim', $anoLetivo->data_fim->format('Y-m-d')) }}" 
                                   class="input" required>
                            @error('data_fim')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <!-- Avisos -->
                    @if($anoLetivo->turmas->count() > 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                            <div class="text-sm text-yellow-800">
                                <p class="font-semibold mb-1">Atenção:</p>
                                <p>Este ano letivo possui {{ $anoLetivo->turmas->count() }} turma(s) cadastrada(s). Tenha cuidado ao alterar as datas.</p>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

                <!-- Ações -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('anos-letivos.index') }}" class="btn btn-outline">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Alterações
                    </button>
                </div>

            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Info -->
            <x-card title="Informações" icon="fas fa-info-circle">
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-600">Criado em:</span>
                        <p class="font-semibold text-gray-900">{{ $anoLetivo->created_at->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Total de Turmas:</span>
                        <p class="font-semibold text-gray-900">{{ $anoLetivo->turmas->count() }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Status Atual:</span>
                        <p>
                            @if($anoLetivo->ativo)
                            <x-badge type="success">Ativo</x-badge>
                            @elseif($anoLetivo->encerrado)
                            <x-badge type="danger">Encerrado</x-badge>
                            @else
                            <x-badge type="gray">Inativo</x-badge>
                            @endif
                        </p>
                    </div>
                </div>
            </x-card>

            <!-- Ações de Status -->
            <x-card title="Gerenciar Status" icon="fas fa-cog">
                <div class="space-y-3">
                    
                    @if(!$anoLetivo->ativo && !$anoLetivo->encerrado)
                    <form method="POST" action="{{ route('anos-letivos.reativar', $anoLetivo) }}">
                        @csrf
                        <button type="submit" class="btn btn-success w-full"
                                onclick="return confirm('Deseja reativar este ano letivo? O ano ativo atual será desativado.')">
                            <i class="fas fa-play mr-2"></i>
                            Reativar Ano Letivo
                        </button>
                    </form>
                    @endif

                    @if($anoLetivo->ativo && !$anoLetivo->encerrado)
                    <form method="POST" action="{{ route('anos-letivos.encerrar', $anoLetivo) }}">
                        @csrf
                        <button type="submit" class="btn btn-danger w-full"
                                onclick="return confirm('Deseja encerrar este ano letivo? Esta ação não pode ser desfeita facilmente.')">
                            <i class="fas fa-stop mr-2"></i>
                            Encerrar Ano Letivo
                        </button>
                    </form>
                    @endif

                    @if($anoLetivo->encerrado)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-lock text-red-600 mt-1 mr-3"></i>
                            <div class="text-sm text-red-800">
                                <p class="font-semibold">Ano Encerrado</p>
                                <p class="mt-1">Este ano letivo foi encerrado e não pode ser reativado diretamente.</p>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </x-card>

            <!-- Estatísticas -->
            @if($anoLetivo->turmas->count() > 0)
            <x-card title="Estatísticas" icon="fas fa-chart-bar">
                <div class="space-y-3">
                    <div class="text-center p-3 bg-primary-50 rounded-lg">
                        <div class="text-2xl font-bold text-primary-600">{{ $anoLetivo->turmas->count() }}</div>
                        <div class="text-xs text-gray-600">Turmas</div>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ $anoLetivo->turmas->sum('total_alunos') }}</div>
                        <div class="text-xs text-gray-600">Total de Alunos</div>
                    </div>
                </div>
            </x-card>
            @endif

        </div>

    </div>

</form>

@endsection