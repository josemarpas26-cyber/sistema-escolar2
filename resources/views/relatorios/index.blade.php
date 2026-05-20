@extends('layouts.app')

@section('page-title', 'Relatórios')

@section('content')
<div class="space-y-6">

    {{-- ================= BOLETIM ================= --}}
    <x-card title="Boletim do Aluno" icon="fas fa-file-pdf">
        <form method="GET" action="{{ route('relatorios.boletim') }}" class="mt-1 grid grid-cols-1 gap-3 md:grid-cols-5">
            @csrf
            <select name="aluno_id" class="form-input" required>
                <option value="">Aluno</option>
                @foreach($alunos as $aluno)
                    <option value="{{ $aluno->id }}">
                        {{ $aluno->name }} ({{ $aluno->numero_processo }})
                    </option>
                @endforeach
            </select>

            <select name="ano_letivo_id" class="form-input js-ano-filtro-index" required>
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

            <div class="flex gap-3">
                <button type="submit" class="btn btn-outline w-full">Ver</button>
                <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
            </div>

        </form>
    </x-card>


{{-- ================= BOLETINS EM MASSA ================= --}}
<x-card title="Boletins em Massa (turma completa)" icon="fas fa-file-excel">
    <form method="GET" action="{{ route('relatorios.boletins-massa') }}"
         class="mt-1 grid grid-cols-1 gap-3 md:grid-cols-5">
        @csrf

        <select name="turma_id" id="turma_id_boletim_massa" class="form-input" required>
            <option value="">Turma</option>
            @foreach($turmas as $turma)
                <option value="{{ $turma->id }}">
                    {{ $turma->nome_completo }} — {{ $turma->anoLetivo->nome }}
                </option>
            @endforeach
        </select>

        <select name="aluno_id" id="aluno_id_boletim_massa" class="form-input">
            <option value="">Todos os alunos </option>
            @foreach($alunos as $aluno)
                <option value="{{ $aluno->id }}" data-turmas="{{ $aluno->turmas->pluck('id')->implode(',') }}">
                    {{ $aluno->name }} ({{ $aluno->numero_processo }})
                </option>
            @endforeach
        </select>

        <select name="ano_letivo_id" class="form-input">
            @foreach($anosLetivos as $ano)
                <option value="{{ $ano->id }}" @selected($anoLetivo?->id === $ano->id)>
                    {{ $ano->nome }}
                </option>
            @endforeach
        </select>

        <select name="trimestre" class="form-input">
            <option value="final">Final (CFD)</option>
            <option value="1">1º Trimestre</option>
            <option value="2" selected>2º Trimestre</option>
            <option value="3">3º Trimestre</option>
        </select>

        <div class="flex gap-3 items-end">
            <button type="submit" name="formato" value="xlsx" class="btn btn-success w-full">
                <i class="fas fa-file-excel mr-2"></i>
                XLSX
            </button>
            <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">
                <i class="fas fa-file-pdf mr-2"></i>
                PDF
            </button>
        </div>
    </form>
       <p class="mt-3 px-1 text-xs text-gray-400">
        Sem aluno selecionado: exportação em massa. Com aluno selecionado: exportação única.
    </p>
</x-card>


    {{-- ================= PAUTA DISCIPLINA ================= --}}
    <x-card title="Pauta da Turma (disciplina/trimestre)" icon="fas fa-table">
        <form method="GET"
              id="form-pauta"
              action="{{ route('relatorios.pauta', ['turma' => $turmas->first()?->id ?? 1]) }}"
              class="mt-1 grid grid-cols-1 gap-3 md:grid-cols-5">
    @csrf

            <select name="turma_id" id="turma_id" class="form-input" required>
                <option value="">Turma</option>
                @foreach($turmas as $turma)
                    <option value="{{ $turma->id }}">
                        {{ $turma->nome_completo }} - {{ $turma->anoLetivo->nome }}
                    </option>
                @endforeach
            </select>

            <select name="ano_letivo_id" class="form-input js-ano-filtro-index" required>
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

            <div class="flex gap-3">
                <button type="submit" class="btn btn-outline w-full">Ver</button>
                <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
            </div>

        </form>
    </x-card>

{{-- ================= PAUTA GERAL ================= --}}
<x-card title="Pauta Geral do Ano Letivo" icon="fas fa-list-check">
    <form method="GET"
          id="form-pauta-geral"
          action="{{ route('relatorios.pauta-geral', ['turma' => $turmas->first()?->id ?? 1]) }}"
          class="mt-1 grid grid-cols-1 gap-3 md:grid-cols-4">
        @csrf

        <select name="turma_id" id="turma_id_geral" class="form-input" required>
            <option value="">Turma</option>
            @foreach($turmas as $turma)
                <option value="{{ $turma->id }}">
                    {{ $turma->nome_completo }}
                </option>
            @endforeach
        </select>

        <select name="ano_letivo_id" class="form-input js-ano-filtro-index" required>
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

        <div class="flex gap-3">
            <button type="submit" class="btn btn-outline w-full">Ver</button>
            <button type="submit" name="formato" value="xlsx" class="btn btn-success w-full">XLSX</button>
            <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
        </div>

    </form>
</x-card>

    {{-- ================= HISTÓRICOS ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <x-card title="Histórico Académico do Aluno" icon="fas fa-history">
            <form method="GET" action="{{ route('relatorios.historico') }}" class="mt-1 space-y-4">
    @csrf

                <select name="aluno_id" class="form-input" required>
                    <option value="">Aluno</option>
                    @foreach($alunos as $aluno)
                        <option value="{{ $aluno->id }}">
                            {{ $aluno->name }} ({{ $aluno->numero_processo }})
                        </option>
                    @endforeach
                </select>

                   <div class="flex gap-3">
                    <button type="submit" formtarget="_blank" class="btn btn-outline w-full">Ver</button>
                    <button type="submit" name="formato" value="pdf" class="btn btn-primary w-full">PDF</button>
                </div>

            </form>
        </x-card>

        <x-card title="Histórico de Turmas do Professor" icon="fas fa-chalkboard-teacher">
               <form method="GET" action="{{ route('relatorios.historico-professor') }}" target="_blank" class="mt-1 space-y-4">
    @csrf

                <select name="professor_id" class="form-input" required>
                    <option value="">Professor</option>
                    @foreach($professores as $professor)
                        <option value="{{ $professor->id }}">
                            {{ $professor->name }}
                        </option>
                    @endforeach
                </select>

                 <div class="flex gap-3">
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
    

    const BASE_URL_GERAL = '{{ url("relatorios/pauta-geral") }}';

    const formPautaGeral = document.getElementById('form-pauta-geral');
    if (formPautaGeral) {
        formPautaGeral.addEventListener('submit', function(e) {
            const turmaId = document.getElementById('turma_id_geral').value;
            if (turmaId) {
                this.action = BASE_URL_GERAL + '/' + turmaId;
            }
        });
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

    // Ano letivo do índice de relatórios (atualiza listas de turmas/alunos/disciplinas)
    document.querySelectorAll('.js-ano-filtro-index').forEach((select) => {
        select.addEventListener('change', function () {
            if (!this.value) return;

            const url = new URL('{{ route("relatorios.index") }}', window.location.origin);
            url.searchParams.set('ano_letivo_id', this.value);
            window.location.href = url.toString();
        });
    });
    // Filtro aluno/turma do boletim em massa

    const turmaBoletimMassa = document.getElementById('turma_id_boletim_massa');
    const alunoBoletimMassa = document.getElementById('aluno_id_boletim_massa');

    function filtrarAlunosBoletimMassa() {
        if (!turmaBoletimMassa || !alunoBoletimMassa) return;

        const turmaId = turmaBoletimMassa.value;
        const options = Array.from(alunoBoletimMassa.options);

        options.forEach((option, idx) => {
            if (idx === 0) {
                option.hidden = false;
                return;
            }

            const turmas = (option.dataset.turmas || '').split(',').filter(Boolean);
            const pertence = !turmaId || turmas.includes(turmaId);
            option.hidden = !pertence;

            if (!pertence && option.selected) {
                alunoBoletimMassa.value = '';
            }
        });
    }

    if (turmaBoletimMassa) {
        turmaBoletimMassa.addEventListener('change', filtrarAlunosBoletimMassa);
        filtrarAlunosBoletimMassa();
    }

    // Form da pauta geral

</script>
@endpush