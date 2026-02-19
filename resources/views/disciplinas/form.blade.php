@extends('layouts.app')
@section('page-title', isset($disciplina) ? 'Editar Disciplina' : 'Nova Disciplina')
@section('content')
<form method="POST" action="{{ isset($disciplina) ? route('disciplinas.update', $disciplina) : route('disciplinas.store') }}">
    @csrf
    @if(isset($disciplina)) @method('PUT') @endif
    <div class="max-w-2xl">
        <x-card title="Dados da Disciplina" icon="fas fa-book-open">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="label">Código *</label><input type="text" name="codigo" value="{{ old('codigo', $disciplina->codigo ?? '') }}" class="input" required></div>
                    <div><label class="label">Nome *</label><input type="text" name="nome" value="{{ old('nome', $disciplina->nome ?? '') }}" class="input" required></div>
                </div>
                <div><label class="label">Descrição</label><textarea name="descricao" class="input" rows="2">{{ old('descricao', $disciplina->descricao ?? '') }}</textarea></div>
                <div><p class="label">Lecionada nas Classes:</p>
                    <div class="flex space-x-4">
                        <label class="flex items-center"><input type="hidden" name="leciona_10" value="0">
<input type="checkbox" name="leciona_10" value="1"
    {{ old('leciona_10', $disciplina->leciona_10 ?? false) ? 'checked' : '' }}
    class="rounded">
 <span class="ml-2">10ª Classe</span></label>

                        <label class="flex items-center"><input type="hidden" name="leciona_11" value="0">
<input type="checkbox" name="leciona_11" value="1"
    {{ old('leciona_11', $disciplina->leciona_11 ?? false) ? 'checked' : '' }}
    class="rounded"> <span class="ml-2">11ª Classe</span></label>

                        <label class="flex items-center"><input type="hidden" name="leciona_12" value="0">
<input type="checkbox" name="leciona_12" value="1"
    {{ old('leciona_12', $disciplina->leciona_12 ?? false) ? 'checked' : '' }}
    class="rounded"> <span class="ml-2">12ª Classe</span></label>
                    
                    </div>
                </div>
                <div class="flex space-x-6">
                    <label class="flex items-center"><input type="hidden" name="disciplina_terminal" value="0">
<input type="checkbox" name="disciplina_terminal" value="1"
    {{ old('disciplina_terminal', $disciplina->disciplina_terminal ?? false) ? 'checked' : '' }}
    class="rounded">
<span class="ml-2">Disciplina Terminal</span></label>
                    
                <label class="flex items-center"><input type="checkbox" name="ativo" value="1" {{ (isset($disciplina) && $disciplina->ativo) || !isset($disciplina) ? 'checked' : '' }} class="rounded"> <span class="ml-2">Ativa</span></label>
                </div>
            </div>
            <div class="mt-6 flex space-x-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Salvar</button>
                <a href="{{ route('disciplinas.index') }}" class="btn btn-outline">Cancelar</a>
            </div>
        </x-card>
    </div>
</form>
@endsection
