@props([
    'title' => null,
    'icon' => null,
])



<div class="group relative bg-white rounded-xl border border-gray-200
            shadow-sm hover:shadow-xl transition-all duration-300
            hover:-translate-y-1 overflow-hidden p-6">

    <!-- Glow leve neutro -->
    <div class="absolute inset-0 bg-gradient-to-r 
                from-gray-100 to-transparent 
                opacity-0 group-hover:opacity-100 
                transition-opacity duration-300">
    </div>

    <div class="relative mb-5 flex items-center space-x-3">
        <div class="w-10 h-10 flex items-center justify-center 
                    rounded-lg bg-gray-100 text-gray-600 
                    group-hover:scale-110 transition-transform duration-300">
            <i class="{{ $icon }}"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-lg">
            {{ $title }}
        </h3>
    </div>

    <div class="relative">
        {{ $slot }}
    </div>

    <!-- Barra inferior animada -->
    <div class="absolute bottom-0 left-0 h-1 w-0
                bg-gray-600 
                group-hover:w-full transition-all duration-300">
    </div>
</div>
