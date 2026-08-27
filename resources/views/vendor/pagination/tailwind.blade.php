{{-- Site-wide pagination component — Laravel resolves resources/views/vendor/pagination/
     tailwind.blade.php automatically for every `$paginator->links()` call app-wide (no
     controller changes needed), replacing the framework default. That default splits into
     two separate blocks: a "sm:hidden" mobile one with only bare Previous/Next text links
     and no page numbers at all, and a "hidden sm:flex" desktop one with the actual numbered
     pills — meaning on a phone, page numbers never appeared, full stop. This is one unified,
     responsive layout instead: the same numbered pills at every screen width, wrapping onto
     a second line if there isn't room rather than disappearing.

     Neutral (not brand-orange) active-page styling so this looks correct whether it's
     rendered on the storefront (orange), the blog (indigo accents), or admin lists — it
     never has to know which section it's in. --}}
@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center gap-3">
    @if ($paginator->total() > 0)
    <p class="text-xs text-gray-500 dark:text-gray-400">
        {{ __('Showing') }}
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $paginator->firstItem() }}</span>
        {{ __('to') }}
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $paginator->lastItem() }}</span>
        {{ __('of') }}
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $paginator->total() }}</span>
        {{ __('results') }}
    </p>
    @endif

    <div class="flex flex-wrap items-center justify-center gap-1.5">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                class="w-9 h-9 flex items-center justify-center rounded-full text-gray-300 dark:text-gray-700 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </a>
        @endif

        {{-- Page numbers / ellipsis --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="w-9 h-9 flex items-center justify-center text-sm text-gray-400 dark:text-gray-600" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                            class="w-9 h-9 flex items-center justify-center rounded-full text-sm font-bold bg-gray-900 text-white dark:bg-white dark:text-gray-900">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                            class="w-9 h-9 flex items-center justify-center rounded-full text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-95 transition">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                class="w-9 h-9 flex items-center justify-center rounded-full text-gray-300 dark:text-gray-700 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </span>
        @endif
    </div>
</nav>
@endif
