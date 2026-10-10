{{-- Logo — mobile and desktop resolve independently: mobile prefers site_logo_mobile,
     falling back to the main logo, then to the initials/site-name placeholder; desktop
     always uses the main logo (or the placeholder). This must NOT be nested so that a
     mobile logo with no main logo set still renders on mobile. --}}
<a href="{{ route('home') }}" class="flex-shrink-0 flex items-center gap-2">
    {{-- h-9/max-w-[130px] (was h-12/190px) — the floating capsule (layouts/app.blade.php)
         added its own horizontal padding on top of this row's existing inset, and combined
         with a 190px-wide logo plus the full action-icon row, the smallest common phone
         widths (320-375px) genuinely didn't have enough space: the row overflowed, and with
         no overflow-x:hidden safety net (added alongside this), that overflow turned into the
         entire page shifting sideways on scroll — not just the header looking cramped. --}}
    <div class="md:hidden flex items-center gap-2">
        @if($logoMobileUrl ?? null)
            <img src="{{ $logoMobileUrl }}" alt="{{ $siteName }}" class="h-9 max-w-[130px] object-contain">
        @elseif($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-9 max-w-[130px] object-contain">
        @else
            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-lg">{{ strtoupper(substr($siteName,0,1)) }}</span>
            </div>
            <span class="text-lg font-extrabold text-gray-800 dark:text-gray-100 hidden sm:block">{{ $siteName }}</span>
        @endif
    </div>
    <div class="hidden md:flex items-center gap-2">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-16 max-w-[220px] object-contain">
        @else
            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-lg">{{ strtoupper(substr($siteName,0,1)) }}</span>
            </div>
            <span class="text-xl font-extrabold text-gray-800 dark:text-gray-100">{{ $siteName }}</span>
        @endif
    </div>
</a>
