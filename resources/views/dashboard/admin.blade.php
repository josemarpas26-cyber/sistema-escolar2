@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')

<style>
/* ── DASHBOARD REDESIGN ── */

.metrics-strip {
    display: flex;
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    margin-bottom: 20px;
    background: var(--surface-card);
}
.metric-tile {
    flex: 1;
    padding: 20px 24px;
    border-right: 1px solid var(--surface-border);
    position: relative;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: background var(--dur-fast);
}
.metric-tile:last-child { border-right: none; }
.metric-tile:hover { background: var(--hover-bg); }
.metric-accent {
    position: absolute;
    top: 0; left: 0;
    width: 3px; height: 100%;
    border-radius: 0;
}
.metric-num {
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -1px;
    line-height: 1;
    margin-bottom: 5px;
    color: var(--tx-1);
}
.metric-lbl {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 700;
    color: var(--tx-3);
}

.db-two-col {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 16px;
}
@media (max-width: 900px) {
    .db-two-col { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .metrics-strip { flex-wrap: wrap; }
    .metric-tile { min-width: 50%; border-bottom: 1px solid var(--surface-border); }
    .metric-tile:nth-child(2) { border-right: none; }
}

/* ── Calendário ── */
.db-cal {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-lg);
    padding: 24px;
}
.db-ano-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--surface-border);
    flex-wrap: wrap;
}
.db-ano-nome {
    font-size: 14px;
    font-weight: 700;
    color: var(--tx-1);
}
.db-ano-dias {
    font-size: 12px;
    color: var(--tx-3);
    margin-left: auto;
}

.cal-nav {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.cal-month-label {
    flex: 1;
    font-size: 13px;
    font-weight: 700;
    color: var(--tx-1);
}
.cal-nav-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--tx-3);
    padding: 5px 9px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    line-height: 1;
    transition: background var(--dur-fast), color var(--dur-fast);
    font-family: var(--font-sans);
}
.cal-nav-btn:hover { background: var(--hover-bg); color: var(--tx-1); }

.mini-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.mini-cal-wd {
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    color: var(--tx-4);
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 0 0 5px;
}
.mini-cal-d {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    color: var(--tx-1);
    transition: background var(--dur-fast);
    user-select: none;
}
.mini-cal-d:hover { background: var(--hover-bg); }
.mini-cal-d.other-month { color: var(--tx-4); opacity: .45; pointer-events: none; }
.mini-cal-d.today {
    background: var(--blue-600);
    color: #fff;
    font-weight: 700;
}
.mini-cal-d.selected {
    background: var(--badge-blue-bg);
    color: var(--badge-blue-tx);
    font-weight: 600;
}
.mini-cal-d.has-event::after {
    content: '';
    position: absolute;
    bottom: 3px;
    width: 4px; height: 4px;
    border-radius: 50%;
    background: var(--blue-500);
}
.mini-cal-d.today.has-event::after { background: rgba(255,255,255,.6); }

.cal-actions {
    display: flex;
    gap: 6px;
    margin-top: 14px;
}
.cal-btn-primary {
    flex: 1;
    height: 32px;
    border: none;
    border-radius: var(--radius-sm);
    background: var(--blue-600);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: var(--font-sans);
    transition: opacity var(--dur-fast);
    opacity: .4;
    pointer-events: none;
}
.cal-btn-primary.active { opacity: 1; pointer-events: auto; }
.cal-btn-secondary {
    height: 32px;
    padding: 0 12px;
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-sm);
    background: none;
    color: var(--tx-3);
    font-size: 12px;
    cursor: pointer;
    font-family: var(--font-sans);
    transition: opacity var(--dur-fast), background var(--dur-fast);
    opacity: .4;
    pointer-events: none;
}
.cal-btn-secondary.active { opacity: 1; pointer-events: auto; }
.cal-btn-secondary:hover { background: var(--hover-bg); }

.db-events-list {
    margin-top: 14px;
    border-top: 1px solid var(--surface-border);
    padding-top: 14px;
    max-height: 180px;
    overflow-y: auto;
}
.db-event-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 0;
    border-bottom: 1px solid var(--surface-border);
}
.db-event-row:last-child { border-bottom: none; }
.db-event-bar {
    width: 3px;
    min-height: 28px;
    border-radius: 2px;
    flex-shrink: 0;
    align-self: stretch;
}
.db-event-name {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--tx-1);
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.db-event-date { font-size: 11px; color: var(--tx-3); }
.db-event-del {
    background: none;
    border: none;
    color: var(--tx-4);
    cursor: pointer;
    font-size: 11px;
    padding: 2px 4px;
    transition: color var(--dur-fast);
    font-family: var(--font-sans);
}
.db-event-del:hover { color: var(--err-ico); }

/* ── Logs ── */
.db-logs {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.db-logs-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 14px;
    border-bottom: 1px solid var(--surface-border);
    flex-shrink: 0;
}
.db-logs-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--tx-1);
}
.db-logs-link {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--blue-600);
    text-decoration: none;
}
.db-logs-link:hover { text-decoration: underline; }
.db-logs-body { flex: 1; overflow-y: auto; }

.db-log-entry {
    display: flex;
    gap: 12px;
    padding: 11px 20px;
    border-bottom: 1px solid var(--surface-border);
    align-items: flex-start;
}
.db-log-entry:last-child { border-bottom: none; }
.db-log-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 5px;
}
.db-log-body { flex: 1; min-width: 0; }
.db-log-text {
    font-size: 12.5px;
    line-height: 1.45;
    color: var(--tx-1);
}
.db-log-text strong { font-weight: 700; }
.db-log-text .hl { color: var(--blue-600); }
.db-log-sub { font-size: 11px; color: var(--tx-3); margin-top: 2px; }
.db-log-time { font-size: 11px; color: var(--tx-4); white-space: nowrap; flex-shrink: 0; }

/* ── Modal ── */
.db-modal-bg {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.45);
    display: flex; align-items: center; justify-content: center;
    z-index: 100; padding: 20px;
}
.db-modal {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-xl);
    padding: 24px;
    width: 100%; max-width: 360px;
    box-shadow: var(--sh-xl);
}
.db-modal-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--tx-1);
    margin-bottom: 16px;
}
.db-modal-label {
    display: block;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 700;
    color: var(--tx-3);
    margin-bottom: 5px;
}
.db-modal-input {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border: 1.5px solid var(--inp-border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    color: var(--inp-tx);
    background: var(--inp-bg);
    outline: none;
    margin-bottom: 14px;
    font-family: var(--font-sans);
    transition: border-color var(--dur-fast);
}
.db-modal-input:focus { border-color: var(--inp-border-focus); }
.db-color-row { display: flex; gap: 8px; margin-bottom: 14px; }
.db-color-dot {
    width: 22px; height: 22px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color var(--dur-fast);
    flex-shrink: 0;
}
.db-color-dot.sel { border-color: var(--tx-1); }
.db-modal-info {
    font-size: 12px;
    color: var(--info-tx);
    background: var(--info-bg);
    border: 1px solid var(--info-bd);
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
}
.db-modal-footer { display: flex; gap: 8px; }
.db-modal-save {
    flex: 1; height: 38px;
    background: var(--blue-600); color: #fff;
    border: none; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600;
    cursor: pointer; font-family: var(--font-sans);
}
.db-modal-save:hover { background: var(--blue-700); }
.db-modal-cancel {
    height: 38px; padding: 0 16px;
    background: none;
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-sm);
    font-size: 13px; cursor: pointer;
    color: var(--tx-3); font-family: var(--font-sans);
}
.db-modal-cancel:hover { background: var(--hover-bg); }
</style>

<!-- ── MÉTRICAS ── -->
<div class="metrics-strip">
    <a class="metric-tile" href="{{ route('users.index') }}">
        <div class="metric-accent" style="background: var(--blue-600)"></div>
        <div class="metric-num">{{ $total_usuarios }}</div>
        <div class="metric-lbl">Utilizadores</div>
    </a>
    <a class="metric-tile" href="{{ route('users.alunos') }}">
        <div class="metric-accent" style="background: var(--ok-ico)"></div>
        <div class="metric-num">{{ $total_alunos }}</div>
        <div class="metric-lbl">Alunos</div>
    </a>
    <a class="metric-tile" href="{{ route('users.professores') }}">
        <div class="metric-accent" style="background: #7c3aed"></div>
        <div class="metric-num">{{ $total_professores }}</div>
        <div class="metric-lbl">Professores</div>
    </a>
    <a class="metric-tile" href="{{ route('turmas.index') }}">
        <div class="metric-accent" style="background: #0d9488"></div>
        <div class="metric-num">{{ $total_turmas }}</div>
        <div class="metric-lbl">Turmas</div>
    </a>
</div>

<!-- ── DUAS COLUNAS ── -->
<div class="db-two-col">

    <!-- Calendário + Ano Letivo -->
    <div class="db-cal" x-data="schoolCalendar()" x-init="init()">

        @if($ano_letivo_ativo)
        <div class="db-ano-row">
            <span class="db-ano-nome">{{ $ano_letivo_ativo->nome }}</span>
            <x-badge type="{{ $ano_letivo_ativo->encerrado ? 'danger' : 'success' }}">
                {{ $ano_letivo_ativo->encerrado ? 'Encerrado' : 'Ativo' }}
            </x-badge>
            @if(!$ano_letivo_ativo->encerrado && isset($dias_restantes))
            <span class="db-ano-dias">
                @if($dias_restantes > 0)
                    {{ $dias_restantes }} dias restantes
                @elseif($dias_restantes === 0)
                    Encerra hoje
                @endif
            </span>
            @endif
        </div>
        @endif

        <!-- Navegação do mês -->
        <div class="cal-nav">
            <button class="cal-nav-btn" @click="previousMonth()">‹</button>
            <span class="cal-month-label" x-text="currentMonthYear"></span>
            <button class="cal-nav-btn" @click="nextMonth()">›</button>
        </div>

        <!-- Dias da semana -->
        <div class="mini-cal-grid" style="margin-bottom: 2px;">
            <template x-for="d in ['D','S','T','Q','Q','S','S']">
                <div class="mini-cal-wd" x-text="d"></div>
            </template>
        </div>

        <!-- Dias -->
        <div class="mini-cal-grid">
            <template x-for="(day, i) in calendarDays" :key="i">
                <div @click="selectDay(day)"
                     :class="{
                        'other-month': !day.isCurrentMonth,
                        'today': day.isToday && day.isCurrentMonth,
                        'selected': day.isSelected && day.isCurrentMonth && !day.isToday,
                        'has-event': day.hasEvent && day.isCurrentMonth,
                     }"
                     class="mini-cal-d"
                     :style="day.hasEvent && day.isCurrentMonth && !day.isToday && !day.isSelected ? `background:${day.eventColor};color:#fff;` : ''">
                    <span x-text="day.day"></span>
                </div>
            </template>
        </div>

        <!-- Acções -->
        <div class="cal-actions">
            <button @click="showEventModal = true"
                    :class="{ 'active': selectedDays.length > 0 }"
                    class="cal-btn-primary">
                + Criar evento <span x-show="selectedDays.length > 0" x-text="'(' + selectedDays.length + ')'"></span>
            </button>
            <button @click="clearSelection()"
                    :class="{ 'active': selectedDays.length > 0 }"
                    class="cal-btn-secondary">✕</button>
        </div>

        <!-- Lista de eventos do mês -->
        <div class="db-events-list" x-show="monthEvents.length > 0" x-cloak>
            <template x-for="event in monthEvents" :key="event.id">
                <div class="db-event-row">
                    <div class="db-event-bar" :style="`background:${event.color}`"></div>
                    <div class="db-event-name" x-text="event.title"></div>
                    <div class="db-event-date" x-text="event.dateRange"></div>
                    <button class="db-event-del" @click="deleteEvent(event.id)">✕</button>
                </div>
            </template>
        </div>

        <!-- Modal -->
        <div x-show="showEventModal" x-cloak @click.self="showEventModal = false"
             class="db-modal-bg">
            <div class="db-modal">
                <div class="db-modal-title">Novo evento</div>
                <form @submit.prevent="saveEvent()">
                    <label class="db-modal-label">Título</label>
                    <input type="text" x-model="newEvent.title" required
                           class="db-modal-input" placeholder="Ex: Prova de Matemática">

                    <label class="db-modal-label">Cor</label>
                    <div class="db-color-row">
                        <template x-for="color in eventColors" :key="color">
                            <div @click="newEvent.color = color"
                                 :class="{ 'sel': newEvent.color === color }"
                                 class="db-color-dot"
                                 :style="`background:${color}`"></div>
                        </template>
                    </div>

                    <div class="db-modal-info" x-text="selectedDaysText"></div>

                    <div class="db-modal-footer">
                        <button type="submit" class="db-modal-save">Guardar</button>
                        <button type="button" @click="showEventModal = false" class="db-modal-cancel">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Logs -->
    <div class="db-logs">
        <div class="db-logs-top">
            <span class="db-logs-title">Alterações recentes</span>
            <a href="{{ route('logs.index') }}" class="db-logs-link">Ver todos →</a>
        </div>
        <div class="db-logs-body">
            @forelse($logs_recentes as $log)
            <div class="db-log-entry">
                <div class="db-log-dot" style="background: {{ $log->acao === 'criacao' ? 'var(--ok-ico)' : ($log->acao === 'exclusao' ? 'var(--err-ico)' : 'var(--blue-600)') }}"></div>
                <div class="db-log-body">
                    <div class="db-log-text">
                        <strong>{{ optional($log->usuario)->name ?? 'Sistema' }}</strong>
                        {{ $log->descricao_acao }}
                        <span class="hl">{{ $log->descricao_campo }}</span>
                    </div>
                    <div class="db-log-sub">{{ $log->alvo_exibicao }} · {{ optional($log->disciplina)->nome ?? '—' }}</div>
                </div>
                <div class="db-log-time">{{ $log->data_alteracao->diffForHumans() }}</div>
            </div>
            @empty
            <div style="text-align:center; padding: 40px 20px; color: var(--tx-4);">
                <i class="fas fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 8px;"></i>
                Nenhum log recente
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function schoolCalendar() {
    return {
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        selectedDays: [],
        events: [],
        showEventModal: false,
        newEvent: { title: '', color: '#3b82f6' },
        eventColors: ['#3b82f6','#16a34a','#dc2626','#f59e0b','#7c3aed','#ec4899'],

        init() {
            try { this.events = JSON.parse(localStorage.getItem('siga_cal_events') || '[]'); } catch(e) {}
        },

        get currentMonthYear() {
            const m = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
            return m[this.currentMonth] + ' ' + this.currentYear;
        },

        get calendarDays() {
            const first    = new Date(this.currentYear, this.currentMonth, 1);
            const last     = new Date(this.currentYear, this.currentMonth + 1, 0);
            const prevLast = new Date(this.currentYear, this.currentMonth, 0);
            const days = [];
            for (let i = first.getDay(); i > 0; i--) {
                days.push(this._day(prevLast.getDate() - i + 1,
                    this.currentMonth === 0 ? 11 : this.currentMonth - 1,
                    this.currentMonth === 0 ? this.currentYear - 1 : this.currentYear, false));
            }
            for (let d = 1; d <= last.getDate(); d++) {
                days.push(this._day(d, this.currentMonth, this.currentYear, true));
            }
            const rem = 42 - days.length;
            for (let d = 1; d <= rem; d++) {
                days.push(this._day(d,
                    this.currentMonth === 11 ? 0 : this.currentMonth + 1,
                    this.currentMonth === 11 ? this.currentYear + 1 : this.currentYear, false));
            }
            return days;
        },

        _day(d, m, y, curr) {
            const dateStr = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const today   = new Date();
            const evs     = this.events.filter(e => e.dates.includes(dateStr));
            return {
                day: d, month: m, year: y, dateStr,
                isCurrentMonth: curr,
                isToday: d === today.getDate() && m === today.getMonth() && y === today.getFullYear(),
                isSelected: this.selectedDays.includes(dateStr),
                hasEvent: evs.length > 0,
                eventColor: evs[0]?.color || null,
            };
        },

        get monthEvents() {
            return this.events
                .filter(e => e.dates.some(d => {
                    const [y, mo] = d.split('-');
                    return parseInt(mo) - 1 === this.currentMonth && parseInt(y) === this.currentYear;
                }))
                .map(e => {
                    const sorted = e.dates.slice().sort();
                    const fmt = s => { const [y,m,d] = s.split('-'); return `${d}/${m}`; };
                    return { ...e, dateRange: sorted.length > 1 ? `${fmt(sorted[0])} – ${fmt(sorted[sorted.length-1])}` : fmt(sorted[0]) };
                });
        },

        get selectedDaysText() {
            if (!this.selectedDays.length) return 'Nenhum dia seleccionado';
            const s   = this.selectedDays.slice().sort();
            const fmt = d => { const [y,m,dd] = d.split('-'); return `${dd}/${m}`; };
            return s.length === 1 ? fmt(s[0]) : `${fmt(s[0])} – ${fmt(s[s.length-1])} (${s.length} dias)`;
        },

        selectDay(day) {
            if (!day.isCurrentMonth) return;
            const i = this.selectedDays.indexOf(day.dateStr);
            i > -1 ? this.selectedDays.splice(i, 1) : this.selectedDays.push(day.dateStr);
        },

        clearSelection() { this.selectedDays = []; },

        saveEvent() {
            if (!this.newEvent.title || !this.selectedDays.length) return;
            this.events.push({
                id: Date.now(),
                title: this.newEvent.title,
                color: this.newEvent.color,
                dates: [...this.selectedDays].sort()
            });
            localStorage.setItem('siga_cal_events', JSON.stringify(this.events));
            this.showEventModal = false;
            this.clearSelection();
            this.newEvent = { title: '', color: '#3b82f6' };
        },

        deleteEvent(id) {
            if (!confirm('Deseja remover este evento?')) return;
            this.events = this.events.filter(e => e.id !== id);
            localStorage.setItem('siga_cal_events', JSON.stringify(this.events));
        },

        previousMonth() {
            if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
            else this.currentMonth--;
        },

        nextMonth() {
            if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
            else this.currentMonth++;
        },
    };
}
</script>
@endpush