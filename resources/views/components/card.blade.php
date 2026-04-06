@props([
    'title' => null,
    'icon'  => null,
    'noPad' => false,
])

<div class="card">
    @if($title)
    <div class="card-header">
        @if($icon)
        <span style="
            width:30px; height:30px;
            border-radius:8px;
            background: var(--info-bg);
            color: var(--blue-500, #3b82f6);
            display:inline-flex; align-items:center; justify-content:center;
            font-size:13px; flex-shrink:0;
        "><i class="{{ $icon }}"></i></span>
        @endif
        {{ $title }}
    </div>
    @endif
    <div {{ $noPad ? '' : 'class="card-body"' }}>
        {{ $slot }}
    </div>
</div>