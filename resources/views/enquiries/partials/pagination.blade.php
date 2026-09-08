<nav class="epr-pagination" id="eprPagination" aria-label="Enquiries pagination">
    <span class="epr-page-summary" role="status">
        @if($enquiries->total())
            Showing {{ $enquiries->firstItem() ?? 0 }}–{{ $enquiries->lastItem() ?? 0 }} of {{ $enquiries->total() }} enquiries
        @else
            No matching enquiries
        @endif
    </span>
    @if($enquiries->total())
        @if($enquiries->onFirstPage())
            <button type="button" disabled>Previous</button>
        @else
            <a href="{{ $enquiries->previousPageUrl() }}" rel="prev">Previous</a>
        @endif
        @php
            $pages = collect([1, $enquiries->lastPage()])
                ->merge(range(max(1, $enquiries->currentPage() - 2), min($enquiries->lastPage(), $enquiries->currentPage() + 2)))
                ->unique()->sort();
            $previousPage = 0;
        @endphp
        @foreach($pages as $page)
            @if($previousPage && $page - $previousPage > 1)<span aria-hidden="true">…</span>@endif
            @if($page === $enquiries->currentPage())
                <button type="button" aria-label="Page {{ $page }}" aria-current="page">{{ $page }}</button>
            @else
                <a href="{{ $enquiries->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>
            @endif
            @php $previousPage = $page; @endphp
        @endforeach
        @if($enquiries->hasMorePages())
            <a href="{{ $enquiries->nextPageUrl() }}" rel="next">Next</a>
        @else
            <button type="button" disabled>Next</button>
        @endif
    @endif
</nav>
