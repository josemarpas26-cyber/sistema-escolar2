@props([
    'title',
    'value',
    'subtitle' => null,
    'color' => 'primary',
    'icon' => 'fas fa-chart-line',
    'trend' => null,
])

@php
$palette = [
    'primary' => 'from-primary-600 to-primary-700 text-white border-primary-500/40',
    'success' => 'from-emerald-500 to-emerald-600 text-white border-emerald-400/40',
    'warning' => 'from-amber-500 to-amber-600 text-white border-amber-400/40',
    'danger' => 'from-rose-500 to-rose-600 text-white border-rose-400/40',
    'info' => 'from-sky-500 to-sky-600 text-white border-sky-400/40',
];
$cardClass = $palette[$color] ?? $palette['primary'];
@endphp

<div class="rounded-2xl border bg-gradient-to-br {{ $cardClass }} p-5 shadow-sm hover:shadow-md transition-all duration-200">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wide opacity-85">{{ $title }}</p>
            <p class="text-3xl font-bold leading-tight mt-1">{{ $value }}</p>
            @if($subtitle)
                <p class="text-xs opacity-90 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center">
            <i class="{{ $icon }} text-lg"></i>
        </div>
    </div>

    @if($trend !== null)
        <div class="mt-3 text-xs inline-flex items-center gap-1 rounded-full bg-white/20 px-2 py-1">
            <span>{{ $trend >= 0 ? '▲' : '▼' }}</span>
            <span>{{ abs($trend) }}%</span>
            <span class="opacity-90">vs período anterior</span>
        </div>
    @endif
</div>
