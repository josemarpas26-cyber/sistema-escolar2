@if ($paginator->hasPages())
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">

    <span style="font-size:12.5px;color:var(--text-tertiary);">
        Mostrando <strong style="color:var(--text-secondary)">{{ $paginator->firstItem() }}</strong>–<strong style="color:var(--text-secondary)">{{ $paginator->lastItem() }}</strong>
        de <strong style="color:var(--text-secondary)">{{ $paginator->total() }}</strong>
    </span>

    <div style="display:flex;align-items:center;gap:2px;">

        {{-- First --}}
        @if ($paginator->onFirstPage())
        <span style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--gray-300,#cbd5e1);cursor:not-allowed;">«</span>
        @else
        <a href="{{ $paginator->url(1) }}" style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--text-tertiary);text-decoration:none;transition:all .15s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background=''">«</a>
        @endif

        {{-- Prev --}}
        @if ($paginator->onFirstPage())
        <span style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--gray-300,#cbd5e1);cursor:not-allowed;">‹</span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}" style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--text-tertiary);text-decoration:none;transition:all .15s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background=''">‹</a>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
            <span style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;font-size:12.5px;color:var(--text-tertiary);">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                    <span style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:700;background:#2563eb;color:#fff;cursor:default;">{{ $page }}</span>
                    @else
                    <a href="{{ $url }}" style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12.5px;color:var(--text-secondary);text-decoration:none;transition:all .15s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background=''">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--text-tertiary);text-decoration:none;transition:all .15s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background=''">›</a>
        @else
        <span style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--gray-300,#cbd5e1);cursor:not-allowed;">›</span>
        @endif

        {{-- Last --}}
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->url($paginator->lastPage()) }}" style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--text-tertiary);text-decoration:none;transition:all .15s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background=''">»</a>
        @else
        <span style="width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:var(--gray-300,#cbd5e1);cursor:not-allowed;">»</span>
        @endif

    </div>
</div>
@endif