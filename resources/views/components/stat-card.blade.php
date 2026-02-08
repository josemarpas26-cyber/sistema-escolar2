@props(['title', 'value', 'icon', 'color' => 'primary', 'trend' => null])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ $value }}</p>
            @if($trend)
            <p class="text-sm text-{{ $trend > 0 ? 'green' : 'red' }}-600 mt-2">
                <i class="fas fa-arrow-{{ $trend > 0 ? 'up' : 'down' }} mr-1"></i>
                {{ abs($trend) }}%
            </p>
            @endif
        </div>
        <div class="w-12 h-12 bg-{{ $color }}-100 rounded-lg flex items-center justify-center">
            <i class="{{ $icon }} text-2xl text-{{ $color }}-600"></i>
        </div>
    </div>
</div>
