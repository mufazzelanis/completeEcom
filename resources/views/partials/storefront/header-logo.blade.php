{{-- Logo — mobile and desktop resolve independently: mobile prefers site_logo_mobile,
     falling back to the main logo, then to the initials/site-name placeholder; desktop
     always uses the main logo (or the placeholder). This must NOT be nested so that a
     mobile logo with no main logo set still renders on mobile.
     Speed: it's above the fold on every page, so it loads eagerly (not lazy) as a small
     400px webp thumbnail instead of the full upload (PageSpeed flagged a 767px file shown at
     149px), with its real width/height so the header never shifts while it arrives. --}}
@php
    $logoImg = function (?string $key) {
        $path = $key ? setting($key) : null;
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return null;
        }
        $dims = image_dims($path);

        // 200w for normal screens (shown ~150 px wide), 400w only where the screen is sharp enough to need it
        return [
            'src' => image_thumb($path, 200),
            'srcset' => image_thumb($path, 200) . ' 200w, ' . image_thumb($path, 400) . ' 400w',
            'w' => $dims[0] ?? null, 'h' => $dims[1] ?? null,
        ];
    };
    $mainLogo = $logoUrl ? $logoImg('site_logo') : null;
    $mobileLogo = ($logoMobileUrl ?? null) ? $logoImg('site_logo_mobile') : null;
    $mobileLogo ??= $mainLogo;
@endphp
<a href="{{ route('home') }}" class="flex-shrink-0 flex items-center gap-2">
    {{-- h-9/max-w-[130px] (was h-12/190px) — the floating capsule (layouts/app.blade.php)
         added its own horizontal padding on top of this row's existing inset, and combined
         with a 190px-wide logo plus the full action-icon row, the smallest common phone
         widths (320-375px) genuinely didn't have enough space: the row overflowed, and with
         no overflow-x:hidden safety net (added alongside this), that overflow turned into the
         entire page shifting sideways on scroll — not just the header looking cramped. --}}
    <div class="md:hidden flex items-center gap-2">
        @if($mobileLogo)
            <img src="{{ $mobileLogo['src'] }}" srcset="{{ $mobileLogo['srcset'] }}" sizes="{{ $mobileLogo['w'] ? (int) ceil(min(130, 36 * $mobileLogo['w'] / max(1, $mobileLogo['h']))) : 130 }}px" alt="{{ $siteName }}" class="h-9 max-w-[130px] object-contain" style="width:auto"
                 @if($mobileLogo['w']) width="{{ $mobileLogo['w'] }}" height="{{ $mobileLogo['h'] }}" @endif decoding="async">
        @else
            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-lg">{{ strtoupper(substr($siteName,0,1)) }}</span>
            </div>
            <span class="text-lg font-extrabold text-gray-800 dark:text-gray-100 hidden sm:block">{{ $siteName }}</span>
        @endif
    </div>
    <div class="hidden md:flex items-center gap-2">
        @if($mainLogo)
            <img src="{{ $mainLogo['src'] }}" srcset="{{ $mainLogo['srcset'] }}" sizes="{{ $mainLogo['w'] ? (int) ceil(min(220, 64 * $mainLogo['w'] / max(1, $mainLogo['h']))) : 220 }}px" alt="{{ $siteName }}" class="h-16 max-w-[220px] object-contain" style="width:auto"
                 @if($mainLogo['w']) width="{{ $mainLogo['w'] }}" height="{{ $mainLogo['h'] }}" @endif
                 {{-- same file as the phone logo → already being fetched; a different one stays lazy so phones (where it's hidden) never download it --}}
                 @if(($mobileLogo['src'] ?? null) !== $mainLogo['src']) loading="lazy" @endif decoding="async">
        @else
            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-lg">{{ strtoupper(substr($siteName,0,1)) }}</span>
            </div>
            <span class="text-xl font-extrabold text-gray-800 dark:text-gray-100">{{ $siteName }}</span>
        @endif
    </div>
</a>
