@props([
    'title',
    'value',
    'icon',
    'color' => 'primary',
    'trend' => null,
    'href' => null
])

@if($href)
<a href="{{ $href }}" class="group block transition-all duration-300 hover:-translate-y-2">
@endif

<div class="relative bg-white rounded-xl border border-gray-200 p-6
            shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">

    <!-- Glow dinâmico -->
    <div class="absolute inset-0 bg-gradient-to-r 
                from-{{ $color }}-100 to-transparent 
                opacity-0 group-hover:opacity-100 
                transition-opacity duration-300">
    </div>

    <div class="relative flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-600 mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ $value }}</p>

            @if($trend)
                <p class="text-sm text-{{ $trend > 0 ? 'green' : 'red' }}-600 mt-2">
                    <i class="fas fa-arrow-{{ $trend > 0 ? 'up' : 'down' }} mr-1"></i>
                    {{ abs($trend) }}%
                </p>
            @endif
        </div>

        <div class="w-14 h-14 rounded-xl 
                    bg-{{ $color }}-100 
                    text-{{ $color }}-600 
                    flex items-center justify-center 
                    text-2xl transition-transform duration-300 
                    group-hover:scale-110">
            <i class="{{ $icon }}"></i>
        </div>
    </div>

    <!-- Barra animada inferior -->
    <div class="absolute bottom-0 left-0 h-1 w-0 
                bg-{{ $color }}-500 
                group-hover:w-full transition-all duration-300">
    </div>
</div>

@if($href)
</a>
@endif
