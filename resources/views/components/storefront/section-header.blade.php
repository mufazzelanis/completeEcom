@props(['title', 'subtitle' => null, 'viewAllUrl' => null, 'viewAllLabel' => 'View All', 'theme' => 'default'])
<div class="flex items-end justify-between gap-3 mb-5 reveal">
    <div class="min-w-0">
        <div class="flex items-center gap-2.5">
            <span class="w-1.5 h-5 rounded-full flex-shrink-0 {{ $theme === 'sale' ? 'bg-white' : 'bg-gradient-to-b from-orange-500 to-red-500' }}"></span>
            <h2 class="text-lg md:text-xl font-extrabold tracking-tight truncate {{ $theme === 'sale' ? 'text-white' : 'text-gray-900 dark:text-white' }}">{{ $title }}</h2>
        </div>
        @if($subtitle)
            <p class="text-xs md:text-sm mt-0.5 ml-4 {{ $theme === 'sale' ? 'text-white/80' : 'text-gray-500 dark:text-gray-400' }}">{{ $subtitle }}</p>
        @endif
    </div>
    @if($viewAllUrl)
    <a href="{{ $viewAllUrl }}"
       class="group flex-shrink-0 inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-bold transition
              {{ $theme === 'sale' ? 'bg-white/15 hover:bg-white/25 text-white' : 'bg-orange-50 hover:bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:hover:bg-orange-500/20 dark:text-orange-400' }}">
        {{ $viewAllLabel }}
        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
    </a>
    @endif
</div>
