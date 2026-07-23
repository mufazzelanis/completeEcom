{{-- Logo --}}
<a href="{{ route('home') }}" class="flex-shrink-0 flex items-center gap-2">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 md:h-10 max-w-[140px] object-contain">
    @else
        <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-lg">{{ strtoupper(substr($siteName,0,1)) }}</span>
        </div>
        <span class="text-lg md:text-xl font-extrabold text-gray-800 dark:text-gray-100 hidden sm:block">{{ $siteName }}</span>
    @endif
</a>
