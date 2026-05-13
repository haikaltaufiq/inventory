@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-end">
        <div
            class="inline-flex items-center gap-2 rounded-2xl bg-white px-2 py-1.5 text-sm text-slate-600 shadow-sm ring-1 ring-slate-200">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                    class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl text-slate-300">
                    <i class="fas fa-chevron-left text-xs"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            @endif

            <span class="h-8 w-px bg-slate-200"></span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                    class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl text-slate-300">
                    <i class="fas fa-chevron-right text-xs"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
