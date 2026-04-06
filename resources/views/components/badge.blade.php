@props(['type' => 'gray'])

@php
$cls = match($type) {
    'primary' => 'badge-blue',
    'blue'    => 'badge-blue',
    'success' => 'badge-green',
    'warning' => 'badge-amber',
    'danger'  => 'badge-red',
    'info'    => 'badge-teal',
    'teal'    => 'badge-teal',
    default   => 'badge-gray',
};
@endphp

<span {{ $attributes->merge(['class' => "badge $cls"]) }}>{{ $slot }}</span>