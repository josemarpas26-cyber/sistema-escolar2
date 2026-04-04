@props(['type' => 'gray'])

@php
$classes = [
    'primary' => 'bg-primary-100 text-primary-800',
    'success' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'danger' => 'bg-red-100 text-red-800',
    'gray' => 'bg-gray-100 text-gray-800',
    'info' => 'bg-blue-100 text-blue-800',
];
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . ($classes[$type] ?? $classes['gray'])]) }}>
    {{ $slot }}
</span>
