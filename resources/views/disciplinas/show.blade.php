@extends('layouts.app')
@section('page-title', $disciplina->nome)
@section('header-actions')
<a href="{{ route('disciplinas.edit', $disciplina) }}" class="btn btn-primary"><i class="fas fa-edit mr-2"></i>Editar</a>
@endsection
@section('content')
<div class="max-w-4xl">
    <x-card title="Informações da Disciplina" icon="fas fa-info-circle">
        <div class="grid grid-cols-2 gap-4">
            <div><span class="text-gray-600">Código:</span> <span class="font-bold">{{ $disciplina->codigo }}</span></div>
            <div><span class="text-gray-600">Nome:</span> <span class="font-semibold">{{ $disciplina->nome }}</span></div>
            <div><span class="text-gray-600">Classes:</span>
                @if($disciplina->leciona_10)<x-badge type="info">10ª</x-badge>@endif
                @if($disciplina->leciona_11)<x-badge type="info">11ª</x-badge>@endif
                @if($disciplina->leciona_12)<x-badge type="info">12ª</x-badge>@endif
            </div>
            <div><span class="text-gray-600">Terminal:</span> <x-badge type="{{ $disciplina->disciplina_terminal ? 'success' : 'gray' }}">{{ $disciplina->disciplina_terminal ? 'Sim' : 'Não' }}</x-badge></div>
            <div><span class="text-gray-600">Status:</span> <x-badge type="{{ $disciplina->ativo ? 'success' : 'danger' }}">{{ $disciplina->ativo ? 'Ativa' : 'Inativa' }}</x-badge></div>
        </div>
    </x-card>
</div>
@endsection
