@extends('layouts.app')
@section('page-title', isset($curso) ? 'Editar Curso' : 'Novo Curso')
@section('content')
<form method="POST" action="{{ isset($curso) ? route('cursos.update', $curso) : route('cursos.store') }}">
    @csrf
    @if(isset($curso)) @method('PUT') @endif
    
    <div class="max-w-2xl">
        <x-card title="{{ isset($curso) ? 'Editar' : 'Novo' }} Curso" icon="fas fa-book">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Código *</label>
                        <input type="text" name="codigo" value="{{ old('codigo', $curso->codigo ?? '') }}" class="input" required>
                        @error('codigo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Coordenador</label>
                        <select name="coordenador_id" class="input">
                            <option value="">Sem coordenador</option>
                            @foreach($professores as $prof)
                            <option value="{{ $prof->id }}" {{ (isset($curso) && $curso->coordenador_id == $prof->id) ? 'selected' : '' }}>
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
                    <label class="label">Descrição</label>
                    <textarea name="descricao" rows="3" class="input">{{ old('descricao', $curso->descricao ?? '') }}</textarea>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="ativo" value="1" {{ (isset($curso) && $curso->ativo) || !isset($curso) ? 'checked' : '' }} class="rounded">
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
