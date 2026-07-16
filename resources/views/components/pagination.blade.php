@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-3 border-t border-zinc-100 px-3 py-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
            @if ($paginator->firstItem())
                Showing <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                of <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $paginator->total() }}</span>
            @endif
        </div>
        <div class="flex gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled">Previous</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="pagination-btn">Previous</button>
            @endif
            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="pagination-btn">Next</button>
            @else
                <span class="pagination-btn pagination-btn-disabled">Next</span>
            @endif
        </div>
    </nav>
@endif
