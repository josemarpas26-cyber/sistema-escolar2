@props(['title' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($title)
    <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            @if($icon)
            <i class="{{ $icon }} mr-2 text-primary-600"></i>
            @endif
            {{ $title }}
        </h3>
        @isset($actions)
        <div class="flex items-center space-x-2">
            {{ $actions }}
        </div>
        @endisset
    </div>
    @endif

    {{ $slot }}
</div>