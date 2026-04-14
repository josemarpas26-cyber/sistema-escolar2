@extends('layouts.app')
@section('page-title', isset($curso) ? 'Editar Curso' : 'Novo Curso')
@section('content')
<form method="POST" action="{{ isset($curso) ? route('cursos.update', $curso) : route('cursos.store') }}">
    @csrf
    @if(isset($curso)) @method('PUT') @endif

    <div class="max-w-2xl">
        <x-card title="{{ isset($curso) ? 'Editar' : 'Novo' }} Curso" icon="fas fa-book">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Codigo *</label>
                        <input
                            type="text"
                            name="codigo"
                            id="codigo"
                            required
                            maxlength="10"
                            placeholder="Ex: CFB, CH, TIC..."
                            value="{{ old('codigo', strtoupper($curso->codigo ?? '')) }}"
                            x-on:input="
                                this.value = this.value
                                    .replace(/[^A-Za-z]/g, '')
                                    .toUpperCase();
                            "
                            x-on:keydown="
                                const permitidas = /^[A-Za-z]$/;
                                const teclasSistema = [
                                    'Backspace','Delete','Tab',
                                    'ArrowLeft','ArrowRight','Home','End'
                                ];
                                if (!permitidas.test($event.key) &&
                                    !teclasSistema.includes($event.key)) {
                                    $event.preventDefault();
                                }
                            "
                            x-on:paste="
                                $event.preventDefault();
                                const texto = ($event.clipboardData || window.clipboardData)
                                    .getData('text');
                                const apenasLetras = texto
                                    .replace(/[^A-Za-z]/g, '')
                                    .toUpperCase();
                                this.value = apenasLetras;
                            "
                            class="input uppercase font-semibold tracking-widest @error('codigo') border-red-400 bg-red-50 @enderror"
                        >
                        <p class="text-xs text-slate-400 mt-1">
                            Apenas letras de A a Z, sem espacos ou simbolos.
                        </p>
                        @error('codigo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label">Area de Formacao *</label>
                        <select name="area_formacao_id" class="input @error('area_formacao_id') border-red-400 bg-red-50 @enderror" required>
                            <option value="">Selecione...</option>
                            @foreach($areasFormacao as $area)
                                <option value="{{ $area->id }}" {{ (string) old('area_formacao_id', $curso->area_formacao_id ?? '') === (string) $area->id ? 'selected' : '' }}>
                                    {{ $area->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_formacao_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label">Coordenador</label>
                        <select name="coordenador_id" class="input">
                            <option value="">Sem coordenador</option>
                            @foreach($professores as $prof)
                                <option value="{{ $prof->id }}" {{ (string) old('coordenador_id', $curso->coordenador_id ?? '') === (string) $prof->id ? 'selected' : '' }}>
                                    {{ $prof->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Nome do Curso *</label>
                    <input type="text" name="nome" value="{{ old('nome', $curso->nome ?? '') }}" class="input" required>
                    @error('nome')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label">Descricao</label>
                    <textarea name="descricao" rows="3" class="input">{{ old('descricao', $curso->descricao ?? '') }}</textarea>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="ativo" value="1" {{ old('ativo', (isset($curso) ? (int) $curso->ativo : 1)) ? 'checked' : '' }} class="rounded">
                        <span class="ml-2">Curso ativo</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex space-x-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Salvar</button>
                <a href="{{ route('cursos.index') }}" class="btn btn-outline">Cancelar</a>
            </div>
        </x-card>
    </div>
</form>
@endsection
