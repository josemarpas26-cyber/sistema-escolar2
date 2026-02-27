@extends('layouts.app')

@section('page-title', 'Painel do administrador')

@section('content')

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <x-stat-card 
        title="Usuários" 
        :value="$total_usuarios" 
        icon="fas fa-users"
        color="primary"
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
        color="blue"
        :href="route('users.professores')"
    />

    <x-stat-card 
        title="Turmas" 
        :value="$total_turmas" 
        icon="fas fa-school"
        color="purple"
        :href="route('turmas.index')"
    />

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Ano Letivo Ativo + Calendário -->
    <x-card title="Ano Letivo Ativo" icon="fas fa-calendar-alt">
        @if($ano_letivo_ativo)
        <div class="space-y-3 mb-6">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Nome:</span>
                <span class="font-semibold">{{ $ano_letivo_ativo->nome }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Início:</span>
                <span class="font-semibold">{{ $ano_letivo_ativo->data_inicio->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Fim:</span>
                <span class="font-semibold">{{ $ano_letivo_ativo->data_fim->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Status:</span>
                <div class="flex items-center space-x-2">
                    <x-badge type="{{ $ano_letivo_ativo->encerrado ? 'danger' : 'success' }}">
                        {{ $ano_letivo_ativo->encerrado ? 'Encerrado' : 'Ativo' }}
                    </x-badge>

                    @if(!$ano_letivo_ativo->encerrado && isset($dias_restantes))
                        <span class="text-sm text-gray-500">
                            @if($dias_restantes > 0)
                                ({{ $dias_restantes }} dias restantes)
                            @elseif($dias_restantes === 0)
                                (Encerrando hoje)
                            @else
                                (Já deveria estar encerrado 👀)
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Calendário Escolar -->
        <div class="border-t pt-6" x-data="schoolCalendar()" x-init="init()">
            
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">
                    <i class="fas fa-calendar-days mr-2"></i>
                    Calendário Escolar
                </h3>
            </div>

            <!-- Navegação do Calendário -->
            <div class="flex items-center justify-between mb-3">
                <button @click="previousMonth()" 
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-sm">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <h4 class="text-sm font-semibold" x-text="currentMonthYear"></h4>
                
                <button @click="nextMonth()" 
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-sm">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Grade do Calendário (Compacta) -->
            <div class="grid grid-cols-7 gap-1 mb-3">
                <!-- Cabeçalho dos dias -->
                <template x-for="day in ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']">
                    <div class="text-center text-xs font-semibold text-gray-500 py-1" x-text="day"></div>
                </template>

                <!-- Dias do mês -->
                <template x-for="(day, index) in calendarDays" :key="index">
                    <div @click="selectDay(day)" 
                         :class="{
                            'bg-gray-50 text-gray-400 cursor-default': !day.isCurrentMonth,

                            // Evento pinta o quadrado inteiro
                            [day.eventColor + ' text-white']: day.hasEvent && day.isCurrentMonth && !day.isSelected,

                            // Seleção manual continua funcionando
                            'bg-blue-500 text-white': day.isSelected && day.isCurrentMonth,

                            'ring-1 ring-primary-500': day.isToday && day.isCurrentMonth,
                            'hover:opacity-80': day.isCurrentMonth,
                            'cursor-pointer': day.isCurrentMonth
                        }"
                         class="aspect-square flex items-center justify-center text-xs border border-gray-200 rounded transition-all relative">
                        
                        <span x-text="day.day"></span>
                        
                        <!-- Indicador de evento (bolinha) -->

                    </div>
                </template>
            </div>

            <!-- Botões de Ação Compactos -->
            <div class="flex gap-2 mb-3">
                <button @click="showEventModal = true" 
                        :disabled="selectedDays.length === 0"
                        :class="selectedDays.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-700'"
                        class="btn btn-primary text-xs py-1.5 px-3 flex-1">
                    <i class="fas fa-plus mr-1"></i>
                    Evento
                    <span x-show="selectedDays.length > 0" 
                          x-text="'(' + selectedDays.length + ')'"
                          class="ml-1"></span>
                </button>
                
                <button @click="clearSelection()" 
                        :disabled="selectedDays.length === 0"
                        class="btn btn-outline text-xs py-1.5 px-3"
                        :class="selectedDays.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Lista de Eventos Compacta -->
            <div x-show="monthEvents.length > 0" 
                 x-cloak
                 class="border-t pt-3 max-h-48 overflow-y-auto">
                <h5 class="text-xs font-semibold text-gray-600 mb-2">Eventos este mês:</h5>
                <div class="space-y-1.5">
                    <template x-for="event in monthEvents" :key="event.id">
                        <div class="flex items-start gap-2 p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                            <div class="w-1 h-full rounded-full" :class="event.color"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-xs truncate" x-text="event.title"></p>
                                <p class="text-xs text-gray-500" x-text="event.dateRange"></p>
                            </div>
                            <button @click="deleteEvent(event.id)" 
                                    class="text-red-500 hover:text-red-700 transition-colors">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal de Criar Evento -->
            <div x-show="showEventModal" 
                 x-cloak
                 @click.self="showEventModal = false"
                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold mb-4">Criar Evento Escolar</h3>
                    
                    <form @submit.prevent="saveEvent()">
                        <div class="space-y-4">
                            <!-- Título -->
                            <div>
                                <label class="label">Título do Evento *</label>
                                <input type="text" 
                                       x-model="newEvent.title" 
                                       class="input"
                                       placeholder="Ex: Prova Final de Matemática"
                                       required>
                            </div>

                            <!-- Descrição -->
                            <div>
                                <label class="label">Descrição</label>
                                <textarea x-model="newEvent.description" 
                                          class="input" 
                                          rows="2"
                                          placeholder="Descrição opcional"></textarea>
                            </div>

                            <!-- Cor -->
                            <div>
                                <label class="label">Cor</label>
                                <div class="flex gap-2">
                                    <template x-for="color in eventColors" :key="color.value">
                                        <button type="button"
                                                @click="newEvent.color = color.value"
                                                :class="[
                                                    color.class,
                                                    newEvent.color === color.value ? 'ring-2 ring-offset-2 ring-gray-400' : ''
                                                ]"
                                                class="w-8 h-8 rounded-full transition-all hover:scale-110">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Dias selecionados -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <label class="text-xs font-semibold text-blue-700">Dias selecionados</label>
                                <p class="text-sm text-blue-900 mt-1" x-text="selectedDaysText"></p>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="flex gap-3 mt-6">
                            <button type="submit" class="btn btn-primary flex-1">
                                <i class="fas fa-save mr-2"></i>
                                Salvar
                            </button>
                            <button type="button" 
                                    @click="showEventModal = false" 
                                    class="btn btn-outline">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
        @else
        <p class="text-gray-500 text-center py-4">Nenhum ano letivo ativo</p>
        @endif
    </x-card>

    <!-- Logs Recentes -->
    <x-card title="Alterações Recentes" icon="fas fa-history">
        @if($logs_recentes->count() > 0)
        <div class="space-y-3">
            @foreach($logs_recentes as $log)
            <div class="flex items-start space-x-3 pb-3 border-b border-gray-100 last:border-0">
                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-edit text-primary-600 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">
                        <span class="font-semibold">{{ $log->usuario->name }}</span> 
                        {{ $log->descricao_acao }} 
                        <span class="text-primary-600">{{ $log->descricao_campo }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $log->aluno->name }} - {{ $log->disciplina->nome }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $log->data_alteracao->diffForHumans() }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4 text-center">
            <a href="{{ route('logs.index') }}" class="text-sm text-primary-900 hover:text-primary-500 font-medium">
                Ver todos os logs →
            </a>
        </div>
        @else
        <p class="text-gray-500 text-center py-4">Nenhum log recente</p>
        @endif
    </x-card>

</div>

<!-- Quick Actions -->
@php
$actions = [
    [
        'label' => 'Novo Usuário',
        'route' => route('users.create'),
        'icon' => 'fas fa-user-plus',
        'color' => 'blue',
        'description' => 'Criar novo usuário no sistema'
    ],
    [
        'label' => 'Nova Turma',
        'route' => route('turmas.create'),
        'icon' => 'fas fa-chalkboard',
        'color' => 'green',
        'description' => 'Cadastrar uma nova turma'
    ],
    [
        'label' => 'Dashboard Logs',
        'route' => route('logs.dashboard'),
        'icon' => 'fas fa-chart-line',
        'color' => 'purple',
        'description' => 'Visualizar estatísticas e alterações'
    ],
];

$colorClasses = [
    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'gradient' => 'from-blue-100', 'bar' => 'bg-blue-500'],
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'gradient' => 'from-green-100', 'bar' => 'bg-green-500'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'gradient' => 'from-purple-100', 'bar' => 'bg-purple-500'],
    'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'gradient' => 'from-red-100', 'bar' => 'bg-red-500'],
];
@endphp

<div class="mt-10">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">Ações Rápidas</h2>
        <span class="text-sm text-gray-400">{{ now()->format('d/m/Y') }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($actions as $action)
            @php $classes = $colorClasses[$action['color']] ?? $colorClasses['blue']; @endphp
            <a href="{{ $action['route'] }}"
               class="group relative p-6 bg-white rounded-xl border border-gray-200 shadow-sm 
                      hover:shadow-xl transition-all duration-300 hover:-translate-y-2 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r {{ $classes['gradient'] }} to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative flex items-start space-x-4">
                    <div class="w-14 h-14 flex items-center justify-center rounded-xl {{ $classes['bg'] }} {{ $classes['text'] }} text-2xl transition-transform duration-300 group-hover:scale-110">
                        <i class="{{ $action['icon'] }}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-lg">{{ $action['label'] }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $action['description'] }}</p>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-0 {{ $classes['bar'] }} group-hover:w-full transition-all duration-300"></div>
            </a>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
<script>
function schoolCalendar() {
    return {
        currentDate: new Date(),
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        selectedDays: [],
        events: [],
        showEventModal: false,
        newEvent: { title: '', description: '', color: 'bg-blue-500' },
        eventColors: [
            { value: 'bg-blue-500', class: 'bg-blue-500' },
            { value: 'bg-green-500', class: 'bg-green-500' },
            { value: 'bg-red-500', class: 'bg-red-500' },
            { value: 'bg-yellow-500', class: 'bg-yellow-500' },
            { value: 'bg-purple-500', class: 'bg-purple-500' },
            { value: 'bg-pink-500', class: 'bg-pink-500' },
        ],

        init() {
            const saved = localStorage.getItem('schoolEvents');
            if (saved) this.events = JSON.parse(saved);
        },

        get currentMonthYear() {
            const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                          'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            return months[this.currentMonth] + ' ' + this.currentYear;
        },

        get calendarDays() {
            const firstDay = new Date(this.currentYear, this.currentMonth, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            const prevLastDay = new Date(this.currentYear, this.currentMonth, 0);
            
            const firstDayOfWeek = firstDay.getDay();
            const lastDateOfMonth = lastDay.getDate();
            const prevLastDate = prevLastDay.getDate();
            
            const days = [];
            
            for (let i = firstDayOfWeek; i > 0; i--) {
                days.push(this.createDayObject(
                    prevLastDate - i + 1,
                    this.currentMonth === 0 ? 11 : this.currentMonth - 1,
                    this.currentMonth === 0 ? this.currentYear - 1 : this.currentYear,
                    false
                ));
            }
            
            for (let i = 1; i <= lastDateOfMonth; i++) {
                days.push(this.createDayObject(i, this.currentMonth, this.currentYear, true));
            }
            
            const remainingDays = 42 - days.length;
            for (let i = 1; i <= remainingDays; i++) {
                days.push(this.createDayObject(
                    i,
                    this.currentMonth === 11 ? 0 : this.currentMonth + 1,
                    this.currentMonth === 11 ? this.currentYear + 1 : this.currentYear,
                    false
                ));
            }
            
            return days;
        },

        createDayObject(day, month, year, isCurrentMonth) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const today = new Date();
            const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const dayEvents = this.events.filter(e => e.dates.includes(dateStr));
            
            return {
                day, month, year, dateStr, isCurrentMonth, isToday,
                isSelected: this.selectedDays.includes(dateStr),
                hasEvent: dayEvents.length > 0,
                eventColor: dayEvents[0]?.color || null
            };
        },

        get monthEvents() {
            return this.events.filter(event => {
                return event.dates.some(date => {
                    const [y, m] = date.split('-');
                    return parseInt(m) - 1 === this.currentMonth && parseInt(y) === this.currentYear;
                });
            }).map(event => {
                const sorted = event.dates.sort();
                const start = this.formatDate(sorted[0]);
                const end = sorted.length > 1 ? this.formatDate(sorted[sorted.length - 1]) : null;
                return { ...event, dateRange: end ? `${start} - ${end}` : start };
            });
        },

        get selectedDaysText() {
            if (this.selectedDays.length === 0) return 'Nenhum dia selecionado';
            if (this.selectedDays.length === 1) return this.formatDate(this.selectedDays[0]);
            const sorted = [...this.selectedDays].sort();
            return `${this.formatDate(sorted[0])} - ${this.formatDate(sorted[sorted.length - 1])} (${sorted.length} dias)`;
        },

        selectDay(day) {
            if (!day.isCurrentMonth) return;
            const index = this.selectedDays.indexOf(day.dateStr);
            index > -1 ? this.selectedDays.splice(index, 1) : this.selectedDays.push(day.dateStr);
        },

        clearSelection() {
            this.selectedDays = [];
        },

        saveEvent() {
            if (!this.newEvent.title || this.selectedDays.length === 0) return;
            const event = {
                id: Date.now(),
                title: this.newEvent.title,
                description: this.newEvent.description,
                color: this.newEvent.color,
                dates: [...this.selectedDays].sort()
            };
            this.events.push(event);
            localStorage.setItem('schoolEvents', JSON.stringify(this.events));
            this.showEventModal = false;
            this.clearSelection();
            this.newEvent = { title: '', description: '', color: 'bg-blue-500' };
        },

        deleteEvent(id) {
            if (!confirm('Tem certeza que deseja apagar este evento?')) return;
            this.events = this.events.filter(e => e.id !== id);
            localStorage.setItem('schoolEvents', JSON.stringify(this.events));
        },

        previousMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },

        formatDate(dateStr) {
            const [y, m, d] = dateStr.split('-');
            return `${d}/${m}/${y}`;
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endpush