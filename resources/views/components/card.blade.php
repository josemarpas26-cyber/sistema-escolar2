@props([
    'title' => null,
    'icon'  => null,
    'noPad' => false,
])

<div style="
    background: var(--surface-card, #fff);
    border: 1px solid var(--surface-border, #e2e8f0);
    border-radius: var(--radius-lg, 14px);
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,.07));
    overflow: hidden;
">
    @if($title)
    <div style="
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 20px;
        border-bottom: 1px solid var(--surface-border, #e2e8f0);
        background: var(--gray-50, #f8fafc);
    ">
        @if($icon)
        <span style="
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--blue-50, #eff6ff);
            color: var(--blue-600, #2563eb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        ">
            <i class="{{ $icon }}"></i>
        </span>
        @endif
        <h3 style="
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary, #0f172a);
            margin: 0;
        ">{{ $title }}</h3>
    </div>
    @endif

    <div style="{{ $noPad ? '' : 'padding: 20px;' }}">
        {{ $slot }}
    </div>
</div>