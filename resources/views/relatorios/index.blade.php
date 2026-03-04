@extends('layouts.app')

@section('page-title', 'Relatórios')

@section('content')
<div class="space-y-6">

    {{-- ================= BOLETIM ================= --}}
    <x-card title="Boletim do Aluno" icon="fas fa-file-pdf">
        <form method="GET" action="{{ route('relatorios.boletim') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">

            <select name="aluno_id" class="form-input" required>
                <option value="">Aluno</option>
                @foreach($alunos as $aluno)
                    <option value="{{ $aluno->id }}">
                        {{ $aluno->name }} ({{ $aluno->numero_processo }})
                    </option>
                @endforeach
            </select>

            <select name="ano_letivo_id" class="form-input" required>
                <option value="">Ano letivo</option>
                @foreach($anosLetivos as $ano)
                    <option value="{{ $ano->id }}" @selected($anoLetivo?->id === $ano->id)>
                        {{ $ano->nome }}
                    </option>
                @endforeach
            </select>

            <select name="disciplina_id" class="form-input">
                <option value="">Todas as disciplinas</option>
                @foreach($disciplinas as $disciplina)
                    <option value="{{ $disciplina->id }}">
                        {{ $disciplina->nome }}
                    </option>
                @endforeach
            </select>

            <select name="trimestre" class="form-input">
                <option value="final">Final (CFD)</option>
                <option value="1">1º Trimestre</option>
                <option value="2">2º Trimestre</option>
                <option value="3">3º Trimestre</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-outline w-full">Ver</button>
                <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
            </div>

        </form>
    </x-card>

    {{-- ================= PAUTA DISCIPLINA ================= --}}
    <x-card title="Pauta da Turma (disciplina/trimestre)" icon="fas fa-table">
        <form method="GET"
              id="form-pauta"
              action="{{ route('relatorios.pauta', ['turma' => $turmas->first()?->id ?? 1]) }}"
              class="grid grid-cols-1 md:grid-cols-5 gap-3">

            <select name="turma_id" id="turma_id" class="form-input" required>
                <option value="">Turma</option>
                @foreach($turmas as $turma)
                    <option value="{{ $turma->id }}">
                        {{ $turma->nome_completo }} - {{ $turma->anoLetivo->nome }}
                    </option>
                @endforeach
            </select>

            <select name="ano_letivo_id" class="form-input" required>
                @foreach($anosLetivos as $ano)
                    <option value="{{ $ano->id }}" @selected($anoLetivo?->id === $ano->id)>
                        {{ $ano->nome }}
                    </option>
                @endforeach
            </select>

            <select name="disciplina" id="disciplina_id" class="form-input">
                <option value="">Todas as disciplinas</option>
                @foreach($disciplinas as $disciplina)
                    <option value="{{ $disciplina->id }}">
                        {{ $disciplina->nome }}
                    </option>
                @endforeach
            </select>

            <select name="trimestre" class="form-input">
                <option value="final">Final (CFD)</option>
                <option value="1">1º Trimestre</option>
                <option value="2">2º Trimestre</option>
                <option value="3">3º Trimestre</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-outline w-full">Ver</button>
                <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
            </div>

        </form>
    </x-card>

    {{-- ================= PAUTA GERAL ================= --}}
    <x-card title="Pauta Geral do Ano Letivo" icon="fas fa-list-check">
        <form method="GET"
              id="form-pauta-geral"
              action="{{ route('relatorios.pauta', ['turma' => $turmas->first()?->id ?? 1]) }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <select name="turma_id" id="turma_id_geral" class="form-input" required>
                <option value="">Turma</option>
                @foreach($turmas as $turma)
                    <option value="{{ $turma->id }}">
                        {{ $turma->nome_completo }}
                    </option>
                @endforeach
            </select>

            <select name="ano_letivo_id" class="form-input" required>
                @foreach($anosLetivos as $ano)
                    <option value="{{ $ano->id }}" @selected($anoLetivo?->id === $ano->id)>
                        {{ $ano->nome }}
                    </option>
                @endforeach
            </select>

            <select name="trimestre" class="form-input">
                <option value="final">Final (CFD)</option>
                <option value="1">1º Trimestre</option>
                <option value="2">2º Trimestre</option>
                <option value="3">3º Trimestre</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-outline w-full">Ver</button>
                <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
            </div>

        </form>
    </x-card>

    {{-- ================= HISTÓRICOS ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <x-card title="Histórico Académico do Aluno" icon="fas fa-history">
            <form method="GET" action="{{ route('relatorios.historico') }}" class="space-y-3">

                <select name="aluno_id" class="form-input" required>
                    <option value="">Aluno</option>
                    @foreach($alunos as $aluno)
                        <option value="{{ $aluno->id }}">
                            {{ $aluno->name }} ({{ $aluno->numero_processo }})
                        </option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-outline w-full">Ver</button>
                    <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
                </div>

            </form>
        </x-card>

        <x-card title="Histórico de Turmas do Professor" icon="fas fa-chalkboard-teacher">
            <form method="GET" action="{{ route('relatorios.historico-professor') }}" class="space-y-3">

                <select name="professor_id" class="form-input" required>
                    <option value="">Professor</option>
                    @foreach($professores as $professor)
                        <option value="{{ $professor->id }}">
                            {{ $professor->name }}
                        </option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-outline w-full">Ver</button>
                    <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
                </div>

            </form>
        </x-card>

    </div>

</div>
@endsection

@push('scripts')
<script>
    // Gerar a URL base de forma segura - o Blade.
    const BASE_URL = '{{ url("relatorios/pauta") }}';

    function makePautaAction(turmaId, disciplinaId) {
        if (!turmaId) return '';
        
        if (disciplinaId) {
            return BASE_URL + '/' + turmaId + '/' + disciplinaId;
        }
        
        return BASE_URL + '/' + turmaId;
    }

    // Form da pauta por disciplina
    const formPauta = document.getElementById('form-pauta');
    if (formPauta) {
        formPauta.addEventListener('submit', function(e) {
            const turmaId = document.getElementById('turma_id').value;
            const disciplinaId = document.getElementById('disciplina_id').value;
            const newAction = makePautaAction(turmaId, disciplinaId);
            
            if (newAction) {
                this.action = newAction;
            }
        });
    }

    // Form da pauta geral
    const formPautaGeral = document.getElementById('form-pauta-geral');
    if (formPautaGeral) {
        formPautaGeral.addEventListener('submit', function(e) {
            const turmaId = document.getElementById('turma_id_geral').value;
            const newAction = makePautaAction(turmaId, '');
            
            if (newAction) {
                this.action = newAction;
            }
        });
    }
</script>
@endpush