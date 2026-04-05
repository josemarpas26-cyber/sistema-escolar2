@props([
    'title',
    'value',
    'icon',
    'color' => 'blue',
    'trend' => null,
    'href'  => null,
])

@php
$colorMap = [
    'primary' => ['bg' => '#eff6ff', 'icon' => '#2563eb', 'bar' => '#2563eb'],
    'blue'    => ['bg' => '#eff6ff', 'icon' => '#2563eb', 'bar' => '#2563eb'],
    'green'   => ['bg' => '#f0fdf4', 'icon' => '#16a34a', 'bar' => '#16a34a'],
    'red'     => ['bg' => '#fef2f2', 'icon' => '#dc2626', 'bar' => '#dc2626'],
    'purple'  => ['bg' => '#f5f3ff', 'icon' => '#7c3aed', 'bar' => '#7c3aed'],
    'orange'  => ['bg' => '#fff7ed', 'icon' => '#ea580c', 'bar' => '#ea580c'],
    'warning' => ['bg' => '#fffbeb', 'icon' => '#d97706', 'bar' => '#d97706'],
    'teal'    => ['bg' => '#f0fdfa', 'icon' => '#0f766e', 'bar' => '#0f766e'],
];
$c = $colorMap[$color] ?? $colorMap['blue'];
$tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    style="
        display: block;
        background: var(--surface-card, #fff);
        border: 1px solid var(--surface-border, #e2e8f0);
        border-radius: var(--radius-lg, 14px);
        padding: 20px;
        box-shadow: var(--shadow-sm);
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: box-shadow .2s, transform .2s;
        {{ $href ? 'cursor: pointer;' : '' }}
    "
    @if($href) onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,.1)';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='';this.style.transform=''"
    @endif
>
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
        <div style="min-width: 0;">
            <p style="
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--text-tertiary, #94a3b8);
                margin: 0 0 8px;
            ">{{ $title }}</p>
            <p style="
                font-size: 26px;
                font-weight: 800;
                color: var(--text-primary, #0f172a);
                margin: 0;
                line-height: 1;
                letter-spacing: -.5px;
            ">{{ $value }}</p>

            @if($trend !== null)
            <p style="
                font-size: 12px;
                font-weight: 600;
                margin: 6px 0 0;
                color: {{ $trend > 0 ? '#16a34a' : '#dc2626' }};
            ">
                <i class="fas fa-arrow-{{ $trend > 0 ? 'up' : 'down' }}" style="font-size:10px"></i>
                {{ abs($trend) }}%
            </p>
            @endif
        </div>

        <div style="
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: {{ $c['bg'] }};
            color: {{ $c['icon'] }};
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        ">
            <i class="{{ $icon }}"></i>
        </div>
    </div>

    <!-- Bottom accent bar -->
    <div style="
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: {{ $c['bar'] }};
        opacity: .15;
    "></div>
</{{ $tag }}>