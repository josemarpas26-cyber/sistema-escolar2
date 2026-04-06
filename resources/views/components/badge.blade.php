@props(['type' => 'gray'])

@php
$styles = [
    'primary' => 'background:#dbeafe;color:#1d4ed8;',
    'success' => 'background:#dcfce7;color:#15803d;',
    'warning' => 'background:#fef3c7;color:#92400e;',
    'danger'  => 'background:#fee2e2;color:#b91c1c;',
    'gray'    => 'background:#f1f5f9;color:#475569;',
    'info'    => 'background:#cffafe;color:#0e7490;',
];
$style = $styles[$type] ?? $styles['gray'];
@endphp

<span {{ $attributes->merge(['style' => "display:inline-flex;align-items:center;padding:2px 9px;border-radius:9999px;font-size:11.5px;font-weight:600;letter-spacing:.01em;$style"]) }}>
    {{ $slot }}
</span>