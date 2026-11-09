@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="pagination">
        <span>Menampilkan <b style="color:var(--text)">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</b> dari <b style="color:var(--text)">{{ $paginator->total() }}</b> data</span>
        @if ($paginator->hasPages())
            <div class="pages">
                <a class="page-btn {{ $paginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $paginator->previousPageUrl() }}" aria-label="Sebelumnya"><i data-lucide="chevron-left" style="width:15px"></i></a>
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="page-btn disabled">…</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <a class="page-btn {{ $page == $paginator->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                        @endforeach
                    @endif
                @endforeach
                <a class="page-btn {{ $paginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $paginator->nextPageUrl() }}" aria-label="Berikutnya"><i data-lucide="chevron-right" style="width:15px"></i></a>
            </div>
        @endif
    </nav>
@endif
