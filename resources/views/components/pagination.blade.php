@props([
    'paginator',
    'perPage' => null,
    'perPageOptions' => [10, 20, 25, 50],
])

@php
    $total = $paginator->total();
    $hasPages = $paginator->hasPages();
    $pageName = $paginator->getPageName();
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    $pages = [];
    if ($last <= 7) {
        $pages = range(1, $last);
    } elseif ($last > 1) {
        $pages[] = 1;
        $start = max(2, $current - 1);
        $end = min($last - 1, $current + 1);
        if ($start > 2) {
            $pages[] = '…';
        }
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }
        if ($end < $last - 1) {
            $pages[] = '…';
        }
        $pages[] = $last;
    }
@endphp

@if ($total > 0)
    <nav
        role="navigation"
        aria-label="Pagination"
        class="flex flex-col gap-3 border-t border-zinc-100 px-5 py-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
            @if ($paginator->firstItem())
                <p>
                    Showing
                    <span class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                    of
                    <span class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ $total }}</span>
                </p>
            @endif

            @if ($perPage)
                <label class="inline-flex items-center gap-1.5">
                    <span class="sr-only sm:not-sr-only">Per page</span>
                    <select
                        wire:model.live="{{ $perPage }}"
                        class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>

        @if ($hasPages)
            <div class="flex flex-wrap items-center gap-1.5">
                @if ($paginator->onFirstPage())
                    <span class="pagination-btn pagination-btn-disabled">Previous</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" class="pagination-btn">Previous</button>
                @endif

                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($pages as $page)
                        @if ($page === '…')
                            <span class="px-1.5 text-xs text-zinc-400" aria-hidden="true">…</span>
                        @elseif ($page === $current)
                            <span class="pagination-btn pagination-btn-active" aria-current="page">{{ $page }}</span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                class="pagination-btn pagination-btn-page"
                            >{{ $page }}</button>
                        @endif
                    @endforeach
                </div>

                <span class="text-xs tabular-nums text-zinc-500 sm:hidden dark:text-zinc-400">
                    {{ $current }} / {{ $last }}
                </span>

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" class="pagination-btn">Next</button>
                @else
                    <span class="pagination-btn pagination-btn-disabled">Next</span>
                @endif
            </div>
        @endif
    </nav>
@endif
