@extends('layouts.app')

@section('page-title', 'Sem Ano Letivo Ativo')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card title="Sem Ano Letivo Ativo" icon="fas fa-calendar-times">
        <div class="space-y-4 text-sm text-gray-700">
            <p>Não existe um ano letivo ativo no sistema neste momento.</p>
            <p>Contacte a administração para ativar um período letivo antes de continuar.</p>
        </div>
    </x-card>
</div>
@endsection
