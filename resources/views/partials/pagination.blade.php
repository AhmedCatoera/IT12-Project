@if ($paginator->hasPages())
    <nav class="pager" aria-label="Pages">
        <div class="pager-info">
            @if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @endif
        </div>
        <div class="pager-links">
            @if ($paginator->onFirstPage())
                <span class="disabled" aria-disabled="true">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">&lsaquo;</a>
            @endif

            @if (isset($elements))
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="disabled">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">&rsaquo;</a>
            @else
                <span class="disabled" aria-disabled="true">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
