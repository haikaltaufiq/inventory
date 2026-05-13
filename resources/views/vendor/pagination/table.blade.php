@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-end mb-10">
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

            <div class="flex items-center gap-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true"
                            class="inline-flex h-10 min-w-10 items-center justify-center px-2 text-slate-400">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                    class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg bg-slate-900 px-3 font-semibold text-white shadow-sm shadow-slate-900/20">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                    class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

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
