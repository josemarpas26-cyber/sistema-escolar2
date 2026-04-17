@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')

<style>
/* ── DASHBOARD STYLES ── */
.dash-grid-4 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (min-width: 1024px) {
    .dash-grid-4 { grid-template-columns: repeat(4, 1fr); }
}

.dash-grid-2 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
@media (min-width: 768px) {
    .dash-grid-2 { grid-template-columns: 1fr 1fr; }
}

.dash-grid-3 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 24px;
}
@media (min-width: 768px) {
    .dash-grid-3 { grid-template-columns: repeat(3, 1fr); }
}

/* Quick action cards */
.qa-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: all .18s;
    cursor: pointer;
}
.qa-card:hover {
    border-color: var(--blue-300, #93c5fd);
    box-shadow: 0 4px 16px rgba(37,99,235,.1);
    transform: translateY(-1px);
}
.qa-icon {
    width: 42px;
    height: 42px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.qa-label {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-primary);
}
.qa-desc {
    font-size: 12px;
    color: var(--text-tertiary);
    margin-top: 1px;
}

/* Log item */
.log-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--surface-border);
}
.log-item:last-child { border-bottom: none; }
.log-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 5px;
}

/* Calendar mini */
.mini-cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.mini-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.mini-cal-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11.5px;
    border-radius: 6px;
    cursor: pointer;
    transition: background .12s;
    position: relative;
}
.mini-cal-day:hover { background: var(--gray-100); }
.mini-cal-day.other-month { color: var(--text-tertiary); }
.mini-cal-day.today {
    background: var(--blue-600);
    color: #fff;
    font-weight: 700;
}
.mini-cal-day.has-event::after {
    content: '';
    position: absolute;
    bottom: 2px;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--blue-500);
}
.mini-cal-day.today.has-event::after { background: rgba(255,255,255,.6); }
.mini-cal-day.selected { background: var(--blue-100); color: var(--blue-700); font-weight: 600; }
</style>

<!-- ── STAT CARDS ── -->
<div class="dash-grid-4">
    <x-stat-card
        title="Utilizadores"
        :value="$total_usuarios"
        icon="fas fa-users"
        color="blue"
        :href="route('users.index')"
    />
    <x-stat-card
        title="Alunos"
        :value="$total_alunos"
        icon="fas fa-user-graduate"
        color="green"
        :href="route('users.alunos')"
    />
    <x-stat-card
        title="Professores"
        :value="$total_professores"
        icon="fas fa-chalkboard-teacher"
        color="purple"
        :href="route('users.professores')"
    />
    <x-stat-card
        title="Turmas"
        :value="$total_turmas"
        icon="fas fa-school"
        color="teal"
        :href="route('turmas.index')"
    />
</div>

<!-- ── ANO LETIVO + LOGS ── -->
<div class="dash-grid-2">

    <!-- Ano Letivo + Calendário -->
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:var(--radius-lg);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--surface-border);background:var(--gray-50);display:flex;align-items:center;gap:10px;">
            <span style="width:30px;height:30px;border-radius:8px;background:#eff6ff;color:#2563eb;display:inline-flex;align-items:center;justify-content:center;font-size:13px;">
                <i class="fas fa-calendar-alt"></i>
            </span>
            <h3 style="font-size:14px;font-weight:700;color:var(--text-primary);margin:0;">Ano Letivo</h3>
        </div>
        <div style="padding:20px;">
            @if($ano_letivo_ativo)
            <!-- Info Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                <div style="background:var(--gray-50);border-radius:var(--radius-md);padding:12px 14px;">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-tertiary);margin-bottom:4px;">Período</div>
                    <div style="font-size:15px;font-weight:700;color:var(--text-primary);">{{ $ano_letivo_ativo->nome }}</div>
                </div>
                <div style="background:var(--gray-50);border-radius:var(--radius-md);padding:12px 14px;">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-tertiary);margin-bottom:4px;">Estado</div>
                    <x-badge type="{{ $ano_letivo_ativo->encerrado ? 'danger' : 'success' }}">
                        {{ $ano_letivo_ativo->encerrado ? 'Encerrado' : 'Ativo' }}
                    </x-badge>
                    @if(!$ano_letivo_ativo->encerrado && isset($dias_restantes))
                    <div style="font-size:11px;color:var(--text-tertiary);margin-top:3px;">
                        @if($dias_restantes > 0)
                            {{ $dias_restantes }} dias restantes
                        @elseif($dias_restantes === 0)
                            Encerra hoje
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Mini Calendar -->
            <div x-data="schoolCalendar()" x-init="init()">
                <div class="mini-cal-header">
                    <button @click="previousMonth()" style="background:none;border:none;padding:6px 8px;border-radius:6px;cursor:pointer;color:var(--text-secondary);font-size:12px;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background='none'">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span style="font-size:13px;font-weight:700;color:var(--text-primary);" x-text="currentMonthYear"></span>
                    <button @click="nextMonth()" style="background:none;border:none;padding:6px 8px;border-radius:6px;cursor:pointer;color:var(--text-secondary);font-size:12px;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background='none'">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Day headers -->
                <div class="mini-cal-grid" style="margin-bottom:2px;">
                    <template x-for="d in ['D','S','T','Q','Q','S','S']">
                        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--text-tertiary);padding:4px 0;" x-text="d"></div>
                    </template>
                </div>

                <!-- Days -->
                <div class="mini-cal-grid">
                    <template x-for="(day, i) in calendarDays" :key="i">
                        <div @click="selectDay(day)"
                             :class="{
                                'other-month': !day.isCurrentMonth,
                                'today': day.isToday && day.isCurrentMonth,
                                'selected': day.isSelected && day.isCurrentMonth && !day.isToday,
                                'has-event': day.hasEvent && day.isCurrentMonth,
                             }"
                             class="mini-cal-day"
                             :style="day.hasEvent && day.isCurrentMonth && !day.isToday && !day.isSelected ? `background:${day.eventColor};color:#fff;` : ''"
                        >
                            <span x-text="day.day"></span>
                        </div>
                    </template>
                </div>

                <!-- Event actions -->
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button @click="showEventModal = true"
                            :disabled="selectedDays.length === 0"
                            style="flex:1;height:34px;border-radius:8px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;background:var(--blue-600);color:#fff;transition:opacity .15s;"
                            :style="selectedDays.length === 0 ? 'opacity:.4;cursor:not-allowed' : ''">
                        <i class="fas fa-plus" style="margin-right:5px;"></i>
                        Evento <span x-show="selectedDays.length > 0" x-text="'('+selectedDays.length+')'"></span>
                    </button>
                    <button @click="clearSelection()"
                            :disabled="selectedDays.length === 0"
                            style="height:34px;padding:0 12px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid var(--surface-border);background:var(--surface-card);cursor:pointer;color:var(--text-secondary);"
                            :style="selectedDays.length === 0 ? 'opacity:.4;cursor:not-allowed' : ''">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Events list -->
                <div x-show="monthEvents.length > 0" x-cloak style="margin-top:12px;border-top:1px solid var(--surface-border);padding-top:12px;max-height:160px;overflow-y:auto;">
                    <template x-for="event in monthEvents" :key="event.id">
                        <div style="display:flex;align-items:start;gap:10px;padding:8px;border-radius:8px;margin-bottom:4px;background:var(--gray-50);">
                            <div :style="`width:3px;min-height:32px;border-radius:2px;background:${event.color};flex-shrink:0`"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12.5px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="event.title"></div>
                                <div style="font-size:11px;color:var(--text-tertiary);" x-text="event.dateRange"></div>
                            </div>
                            <button @click="deleteEvent(event.id)" style="background:none;border:none;color:var(--text-tertiary);cursor:pointer;padding:2px 4px;font-size:11px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='var(--text-tertiary)'">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Event Modal -->
                <div x-show="showEventModal" x-cloak @click.self="showEventModal=false"
                     style="position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:100;padding:20px;">
                    <div style="background:var(--surface-card);border-radius:var(--radius-xl);padding:24px;max-width:380px;width:100%;box-shadow:var(--shadow-xl);">
                        <h3 style="font-size:16px;font-weight:700;margin:0 0 16px;">Criar Evento</h3>
                        <form @submit.prevent="saveEvent()">
                            <div style="margin-bottom:12px;">
                                <label style="font-size:11.5px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em;">Título *</label>
                                <input type="text" x-model="newEvent.title" required
                                       style="width:100%;height:38px;padding:0 12px;border:1.5px solid var(--surface-border);border-radius:8px;font-size:13px;background:var(--surface-card);color:var(--text-primary);"
                                       placeholder="Ex: Prova de Matemática">
                            </div>
                            <div style="margin-bottom:14px;">
                                <label style="font-size:11.5px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Cor</label>
                                <div style="display:flex;gap:8px;">
                                    <template x-for="color in eventColors" :key="color">
                                        <button type="button" @click="newEvent.color = color"
                                                :style="`width:26px;height:26px;border-radius:50%;background:${color};border:2px solid ${newEvent.color===color ? '#0f172a' : 'transparent'};cursor:pointer;`">
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div style="background:var(--blue-50);border-radius:8px;padding:10px 12px;font-size:12px;color:#1d4ed8;margin-bottom:16px;" x-text="selectedDaysText"></div>
                            <div style="display:flex;gap:10px;">
                                <button type="submit" style="flex:1;height:38px;border-radius:8px;background:var(--blue-600);color:#fff;border:none;font-size:13px;font-weight:600;cursor:pointer;">Guardar</button>
                                <button type="button" @click="showEventModal=false" style="height:38px;padding:0 16px;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-card);font-size:13px;cursor:pointer;color:var(--text-secondary);">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            @else
            <div style="text-align:center;padding:40px 0;color:var(--text-tertiary);">
                <i class="fas fa-calendar-xmark" style="font-size:2rem;margin-bottom:12px;display:block;"></i>
                Nenhum ano letivo ativo
            </div>
            @endif
        </div>
    </div>

    <!-- Logs Recentes -->
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:var(--radius-lg);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--surface-border);background:var(--gray-50);display:flex;align-items:center;gap:10px;">
            <span style="width:30px;height:30px;border-radius:8px;background:#f0fdf4;color:#16a34a;display:inline-flex;align-items:center;justify-content:center;font-size:13px;">
                <i class="fas fa-history"></i>
            </span>
            <h3 style="font-size:14px;font-weight:700;color:var(--text-primary);margin:0;flex:1">Alterações Recentes</h3>
            <a href="{{ route('logs.index') }}" style="font-size:12px;font-weight:600;color:var(--blue-600);text-decoration:none;">Ver todos →</a>
        </div>
        <div style="padding:4px 20px 16px;">
            @forelse($logs_recentes as $log)
            <div class="log-item">
                <div class="log-dot" style="background: {{ $log->acao === 'criacao' ? '#16a34a' : ($log->acao === 'exclusao' ? '#dc2626' : '#2563eb') }}"></div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:13px;color:var(--text-primary);margin:0 0 2px;">
                        <span style="font-weight:600;">{{ optional($log->usuario)->name ?? 'Sistema' }}</span>
                        {{ $log->descricao_acao }}
                        <span style="color:var(--blue-600)">{{ $log->descricao_campo }}</span>
                    </p>
                    <p style="font-size:11.5px;color:var(--text-tertiary);margin:0;">
                        {{ $log->alvo_exibicao }} · {{ optional($log->disciplina)->nome ?? '—' }}
                    </p>
                </div>
                <span style="font-size:11px;color:var(--text-tertiary);white-space:nowrap;margin-left:8px;">
                    {{ $log->data_alteracao->diffForHumans() }}
                </span>
            </div>
            @empty
            <div style="text-align:center;padding:40px 0;color:var(--text-tertiary);">
                <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
                Nenhum log recente
            </div>
            @endforelse
        </div>
    </div>

</div>

<!-- ── QUICK ACTIONS ── -->

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
            const first = new Date(this.currentYear, this.currentMonth, 1);
            const last  = new Date(this.currentYear, this.currentMonth + 1, 0);
            const prevLast = new Date(this.currentYear, this.currentMonth, 0);
            const days = [];
            for (let i = first.getDay(); i > 0; i--) {
                days.push(this._day(prevLast.getDate() - i + 1, this.currentMonth === 0 ? 11 : this.currentMonth - 1, this.currentMonth === 0 ? this.currentYear - 1 : this.currentYear, false));
            }
            for (let d = 1; d <= last.getDate(); d++) {
                days.push(this._day(d, this.currentMonth, this.currentYear, true));
            }
            const rem = 42 - days.length;
            for (let d = 1; d <= rem; d++) {
                days.push(this._day(d, this.currentMonth === 11 ? 0 : this.currentMonth + 1, this.currentMonth === 11 ? this.currentYear + 1 : this.currentYear, false));
            }
            return days;
        },
        _day(d, m, y, curr) {
            const dateStr = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const today = new Date();
            const evs = this.events.filter(e => e.dates.includes(dateStr));
            return {
                day: d, month: m, year: y, dateStr, isCurrentMonth: curr,
                isToday: d === today.getDate() && m === today.getMonth() && y === today.getFullYear(),
                isSelected: this.selectedDays.includes(dateStr),
                hasEvent: evs.length > 0,
                eventColor: evs[0]?.color || null,
            };
        },
        get monthEvents() {
            return this.events.filter(e => e.dates.some(d => {
                const [y,mo] = d.split('-');
                return parseInt(mo)-1 === this.currentMonth && parseInt(y) === this.currentYear;
            })).map(e => {
                const sorted = e.dates.slice().sort();
                const fmt = s => { const [y,m,d] = s.split('-'); return `${d}/${m}/${y}`; };
                return { ...e, dateRange: sorted.length > 1 ? `${fmt(sorted[0])} – ${fmt(sorted[sorted.length-1])}` : fmt(sorted[0]) };
            });
        },
        get selectedDaysText() {
            if (!this.selectedDays.length) return 'Nenhum dia seleccionado';
            const s = this.selectedDays.slice().sort();
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
            this.events.push({ id: Date.now(), title: this.newEvent.title, color: this.newEvent.color, dates: [...this.selectedDays].sort() });
            localStorage.setItem('siga_cal_events', JSON.stringify(this.events));
            this.showEventModal = false;
            this.clearSelection();
            this.newEvent = { title: '', color: '#3b82f6' };
        },
        deleteEvent(id) {
            if (!confirm('Apagar este evento?')) return;
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