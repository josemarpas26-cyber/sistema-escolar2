@extends('layouts.app')

@section('page-title', 'Calendário da Turma')

@section('content')
<x-card class="mb-6" title="Calendário" icon="fas fa-calendar-days">
    <form method="GET" action="{{ route('calendario.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <select name="turma_id" class="form-input" onchange="this.form.submit()" required>
            <option value="">Selecione uma turma</option>
            @foreach($turmas as $turma)
                <option value="{{ $turma->id }}" @selected((int) $turmaId === (int) $turma->id)>
                    {{ $turma->nome_completo }}
                </option>
            @endforeach
        </select>

        <input type="month" name="mes" class="form-input" value="{{ $mesSelecionado }}" onchange="this.form.submit()">

        <div class="text-sm text-slate-500 flex items-center">
            @if($isProfessor)
                Pode criar, editar e remover os seus eventos.
            @else
                Visualização apenas de leitura.
            @endif
        </div>
    </form>
</x-card>

@if($isProfessor && $turmaId)
<x-card class="mb-6" title="Novo evento" icon="fas fa-plus-circle">
    <form method="POST" action="{{ route('calendario.eventos.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @csrf
        <input type="hidden" name="turma_id" value="{{ $turmaId }}">

        <input type="text" name="titulo" class="form-input" maxlength="120" placeholder="Título do evento" required>
        <input type="text" name="local" class="form-input" maxlength="120" placeholder="Local (opcional)">

        <input type="datetime-local" name="inicio" class="form-input" required>
        <input type="datetime-local" name="fim" class="form-input" placeholder="Fim (opcional)">

        <textarea name="descricao" rows="3" class="form-input md:col-span-2" placeholder="Descrição (opcional)"></textarea>

        <div class="md:col-span-2">
            <button class="btn btn-primary" type="submit">Adicionar evento</button>
        </div>
    </form>
</x-card>
@endif

<x-card title="Eventos" icon="fas fa-list-check">
    @if(!$turmaId)
        <p class="text-slate-500">Selecione uma turma para visualizar o calendário.</p>
    @elseif($eventos->isEmpty())
        <p class="text-slate-500">Nenhum evento registado para o período selecionado.</p>
    @else
        <div class="space-y-4">
            @foreach($eventos->groupBy(fn($evento) => $evento->inicio->format('Y-m-d')) as $data => $eventosDoDia)
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-slate-100 text-sm font-semibold text-slate-700">
                        {{ \Carbon\Carbon::parse($data)->translatedFormat('l, d/m/Y') }}
                    </div>
                    <div class="divide-y divide-slate-200">
                        @foreach($eventosDoDia as $evento)
                            @php
                                $meuEvento = auth()->id() === $evento->professor_id;
                            @endphp
                            <div class="p-4 text-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <div class="font-semibold text-slate-800">{{ $evento->titulo }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $evento->inicio->format('H:i') }}
                                        @if($evento->fim)
                                            - {{ $evento->fim->format('H:i') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="text-xs text-slate-500 mb-1">
                                    Professor: <strong>{{ $evento->professor->name }}</strong>
                                    @if($evento->local)
                                        • Local: <strong>{{ $evento->local }}</strong>
                                    @endif
                                </div>

                                @if($evento->descricao)
                                    <p class="text-slate-700 mb-2">{{ $evento->descricao }}</p>
                                @endif

                                @if($isProfessor && $meuEvento)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-indigo-600 text-xs font-semibold">Editar evento</summary>
                                        <form method="POST" action="{{ route('calendario.eventos.update', $evento) }}" class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="turma_id" value="{{ $turmaId }}">

                                            <input type="text" name="titulo" class="form-input" maxlength="120" value="{{ $evento->titulo }}" required>
                                            <input type="text" name="local" class="form-input" maxlength="120" value="{{ $evento->local }}" placeholder="Local">
                                            <input type="datetime-local" name="inicio" class="form-input" value="{{ $evento->inicio->format('Y-m-d\TH:i') }}" required>
                                            <input type="datetime-local" name="fim" class="form-input" value="{{ optional($evento->fim)->format('Y-m-d\TH:i') }}">
                                            <textarea name="descricao" rows="2" class="form-input md:col-span-2">{{ $evento->descricao }}</textarea>

                                            <div class="md:col-span-2 flex gap-2">
                                                <button class="btn btn-primary" type="submit">Salvar</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('calendario.eventos.destroy', $evento) }}" class="mt-2" onsubmit="return confirm('Remover este evento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit">Remover evento</button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-card>
@endsection
