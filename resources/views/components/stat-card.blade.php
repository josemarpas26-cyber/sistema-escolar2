@props([
    'title',
    'value',
    'icon',
    'color' => 'blue',
    'trend' => null,
    'href'  => null,
])

@php
/* Mapeia a prop color para tokens CSS — não usa hex hardcoded */
$iconBg = match($color) {
    'primary', 'blue' => 'var(--info-bg)',
    'green'           => 'var(--ok-bg)',
    'red', 'danger'   => 'var(--err-bg)',
    'purple'          => 'var(--badge-blue-bg)',
    'teal'            => 'var(--badge-teal-bg)',
    'orange', 'warning' => 'var(--warn-bg)',
    default           => 'var(--hover-bg)',
};
$iconTx = match($color) {
    'primary', 'blue' => 'var(--blue-600)',
    'green'           => 'var(--ok-ico)',
    'red', 'danger'   => 'var(--err-ico)',
    'purple'          => 'var(--blue-500)',
    'teal'            => 'var(--badge-teal-tx)',
    'orange', 'warning' => 'var(--warn-ico)',
    default           => 'var(--tx-3)',
};
$barTx = match($color) {
    'primary', 'blue' => 'var(--blue-600)',
    'green'           => 'var(--ok-ico)',
    'red', 'danger'   => 'var(--err-ico)',
    'purple'          => 'var(--blue-500)',
    'teal'            => 'var(--badge-teal-tx)',
    'orange', 'warning' => 'var(--warn-ico)',
    default           => 'var(--tx-3)',
};
$tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    style="
        display:block;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg, 14px);
        padding: 20px;
        box-shadow: var(--sh-sm);
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: box-shadow .18s, transform .18s;
        {{ $href ? 'cursor:pointer;' : '' }}
    "
    @if($href)
    onmouseover="this.style.boxShadow='var(--sh-md)';this.style.transform='translateY(-2px)'"
    onmouseout="this.style.boxShadow='var(--sh-sm)';this.style.transform=''"
    @endif
>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
        <div style="min-width:0;">
            <p style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--tx-4);margin:0 0 8px;">
                {{ $title }}
            </p>
            <p style="font-size:26px;font-weight:800;color:var(--tx-1);margin:0;line-height:1;letter-spacing:-.5px;">
                {{ $value }}
            </p>
            @if($trend !== null)
            <p style="font-size:12px;font-weight:600;margin:6px 0 0;color:{{ $trend > 0 ? 'var(--ok-tx)' : 'var(--err-tx)' }}">
                <i class="fas fa-arrow-{{ $trend > 0 ? 'up' : 'down' }}" style="font-size:10px"></i>
                {{ abs($trend) }}%
            </p>
            @endif
        </div>
        <div style="
            width:44px; height:44px;
            border-radius:12px;
            background: {{ $iconBg }};
            color: {{ $iconTx }};
            display:flex; align-items:center; justify-content:center;
            font-size:18px; flex-shrink:0;
        ">
            <i class="{{ $icon }}"></i>
        </div>
    </div>
    <!-- Accent bar -->
    <div style="
        position:absolute; bottom:0; left:0; right:0;
        height:3px;
        background: {{ $barTx }};
        opacity:.2;
    "></div>
</{{ $tag }}>