@extends('layouts.app')

@section('page-title', 'Meu histórico')

@section('content')
<div class="space-y-6">
    <x-card title="Meu histórico académico" icon="fas fa-history">
        <p class="text-sm text-slate-600">
            Nesta página pode consultar o resumo do seu histórico e abrir o documento completo.
        </p>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-slate-500">Aluno</div>
                <div class="text-sm font-semibold text-slate-800">{{ $aluno->name }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-slate-500">Nº de registos</div>
                <div class="text-sm font-semibold text-slate-800">{{ $totalRegistros }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-slate-500">Anos com histórico</div>
                <div class="text-sm font-semibold text-slate-800">{{ $anosComHistorico->count() }}</div>
            </div>
        </div>

        @if($anosComHistorico->isNotEmpty())
            <div class="mt-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Anos letivos</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($anosComHistorico as $anoNome)
                        <span class="inline-flex items-center rounded-full bg-indigo-100 text-indigo-700 px-3 py-1 text-xs font-semibold">
                            {{ $anoNome }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ $urlDocumento }}" target="_blank" class="btn btn-outline">
                <i class="fas fa-eye mr-2"></i>
                Ver documento
            </a>
            <a href="{{ $urlDocumentoPdf }}" class="btn btn-primary">
                <i class="fas fa-file-pdf mr-2"></i>
                Baixar PDF
            </a>
        </div>
    </x-card>
</div>
@endsection
