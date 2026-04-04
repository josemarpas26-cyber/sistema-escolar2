@if ($paginator->hasPages())
<div class="flex items-center justify-between px-1 py-3 mt-4">

  {{-- Info de resultados --}}
  <p class="text-sm text-slate-500">
    Mostrando
    <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
    –
    <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
    de
    <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
    resultados
  </p>

  {{-- Botões de navegação --}}
  <div class="flex items-center gap-0.5">

    {{-- Primeira página (<<) --}}
    @if ($paginator->onFirstPage())
      <span class="inline-flex items-center justify-center w-8 h-8
                   text-slate-300 cursor-not-allowed rounded-md text-sm">
        «
      </span>
    @else
      <a href="{{ $paginator->url(1) }}"
         class="inline-flex items-center justify-center w-8 h-8
                text-slate-400 hover:text-blue-600
                rounded-md transition-colors duration-150 text-sm">
        «
      </a>
    @endif

    {{-- Página anterior (<) --}}
    @if ($paginator->onFirstPage())
      <span class="inline-flex items-center justify-center w-8 h-8
                   text-slate-300 cursor-not-allowed rounded-md text-sm">
        ‹
      </span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}"
          class="inline-flex items-center justify-center w-8 h-8
                text-slate-400 hover:text-blue-600
                rounded-md transition-colors duration-150 text-sm">
        ‹
      </a>
    @endif

    {{-- Números de página --}}
    @foreach ($elements as $element)
    
      {{-- Reticências --}}
      @if (is_string($element))
        <span class="inline-flex items-center justify-center w-8 h-8
                     text-slate-400 text-sm">
          {{ $element }}
        </span>
      @endif

     {{-- Páginas numeradas --}}
      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            {{-- Página actual --}}
            <span class="inline-flex items-center justify-center w-8 h-8
                         rounded-full bg-blue-600 text-white font-semibold
                         text-sm cursor-default shadow-sm">
              {{ $page }}
            </span>
          @else
            {{-- Outras páginas --}}
            <a href="{{ $url }}"
                class="inline-flex items-center justify-center w-8 h-8
                      rounded-full text-slate-500
                      hover:bg-slate-100 hover:text-slate-700
                      transition-colors duration-150 text-sm">
              {{ $page }}
            </a>
          @endif
        @endforeach
      @endif

    @endforeach

   {{-- Próxima página (>) --}}
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}"
          class="inline-flex items-center justify-center w-8 h-8
                text-slate-400 hover:text-blue-600
                rounded-md transition-colors duration-150 text-sm">
        ›
      </a>
    @else
      <span class="inline-flex items-center justify-center w-8 h-8
                   text-slate-300 cursor-not-allowed rounded-md text-sm">
        ›
      </span>
    @endif

    {{-- Última página (>>) --}}
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->url($paginator->lastPage()) }}"
         class="inline-flex items-center justify-center w-8 h-8
                text-slate-400 hover:text-blue-600
                rounded-md transition-colors duration-150 text-sm">
        »
      </a>
    @else
      <span class="inline-flex items-center justify-center w-8 h-8
                   text-slate-300 cursor-not-allowed rounded-md text-sm">
        »
      </span>
    @endif

  </div>
</div>
@endif