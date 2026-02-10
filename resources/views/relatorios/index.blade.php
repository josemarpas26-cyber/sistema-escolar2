@extends('layouts.app')
@section('page-title', 'Relatórios')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <x-card title="Boletim do Aluno" icon="fas fa-file-pdf">
        <p class="text-sm text-gray-600 mb-4">Gere o boletim individual de qualquer aluno com todas as notas e médias.</p>
        <a href="{{ route('relatorios.boletim') }}" class="btn btn-primary w-full"><i class="fas fa-download mr-2"></i>Gerar Boletim</a>
    </x-card>
    <x-card title="Pauta da Turma" icon="fas fa-table">
        <p class="text-sm text-gray-600 mb-4">Liste todas as notas de uma turma em uma disciplina específica.</p>
        <a href="{{ route('relatorios.pauta', ['turma' => 1, 'disciplina' => 1]) }}" class="btn btn-primary w-full"><i class="fas fa-download mr-2"></i>Gerar Pauta</a>
    </x-card>
    <x-card title="Histórico Académico" icon="fas fa-history">
        <p class="text-sm text-gray-600 mb-4">Veja o histórico completo de um aluno por todos os anos letivos.</p>
        <a href="{{ route('relatorios.historico') }}" class="btn btn-primary w-full"><i class="fas fa-download mr-2"></i>Gerar Histórico</a>
    </x-card>
</div>
@endsection
