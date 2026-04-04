@if ($paginator->hasPages())
<div class="flex items-center justify-between px-1 py-3 mt-4">

  {{-- Info de resultados --}}
  <p class="text-sm text-slate-500">
    Mostrando
    <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
    a
    <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
    de
    <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
    resultados
  </p>

  {{-- Botões de navegação --}}
  <div class="flex items-center gap-1">

    {{-- Anterior --}}
    @if ($paginator->onFirstPage())
      <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg
                   text-slate-300 bg-slate-100 cursor-not-allowed text-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}"
         class="inline-flex items-center justify-center w-9 h-9 rounded-lg
                text-slate-600 bg-white border border-slate-200
                hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300
                transition-all duration-150 text-sm shadow-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </a>
    @endif

    {{-- Números de página --}}
    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="inline-flex items-center justify-center w-9 h-9
                     text-slate-400 text-sm">
          {{ $element }}
        </span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="inline-flex items-center justify-center w-9 h-9
                         rounded-lg bg-blue-600 text-white font-semibold
                         text-sm shadow-sm shadow-blue-200 cursor-default">
              {{ $page }}
            </span>
          @else
            <a href="{{ $url }}"
               class="inline-flex items-center justify-center w-9 h-9
                      rounded-lg text-slate-600 bg-white border border-slate-200
                      hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300
                      transition-all duration-150 text-sm shadow-sm">
              {{ $page }}
            </a>
          @endif
        @endforeach
      @endif
    @endforeach

    {{-- Próximo --}}
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}"
         class="inline-flex items-center justify-center w-9 h-9 rounded-lg
                text-slate-600 bg-white border border-slate-200
                hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300
                transition-all duration-150 text-sm shadow-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M9 18l6-6-6-6"/>
        </svg>
      </a>
    @else
      <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg
                   text-slate-300 bg-slate-100 cursor-not-allowed text-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M9 18l6-6-6-6"/>
        </svg>
      </span>
    @endif

  </div>
</div>
@endif