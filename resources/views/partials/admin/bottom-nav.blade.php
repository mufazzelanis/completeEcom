{{-- Mobile app-style bottom quick-nav for the admin panel — hidden on lg+ (the full sidebar
     takes over there). The sidebar drawer already reaches every admin screen, but on a phone
     that's still "open drawer → scroll → find link" for even the handful of things an admin
     checks constantly while away from their desk (today's orders, adding a product). Putting
     those on a persistent bottom bar — same rounded-top, active-pill treatment as the
     storefront's bottom nav — is what makes this feel like a native admin app you'd actually
     reach for on your phone, not just "the desktop dashboard, shrunk."

     Standalone x-data (this partial sits outside layouts/admin.blade.php's own x-data scope)
     so its "More" tab can flip the shared Alpine.store('adminSidebar').open — the exact same
     flag the topbar hamburger and sidebar overlay already use — to open the real sidebar for
     everything else. --}}
<nav x-data class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-gray-900/95 backdrop-blur border-t border-gray-100 dark:border-gray-700 rounded-t-2xl shadow-[0_-4px_16px_rgba(0,0,0,0.06)]"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid grid-cols-4 px-1 pt-1.5">
        @php
            $abnItems = [
                [
                    'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'label' => 'Dashboard',
                    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                ],
                [
                    'url' => route('admin.orders.index'), 'active' => request()->routeIs('admin.orders.*'), 'label' => 'Orders',
                    'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                ],
                [
                    'url' => route('admin.products.index'), 'active' => request()->routeIs('admin.products.*'), 'label' => 'Products',
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ],
            ];
        @endphp
        @foreach($abnItems as $item)
            <a href="{{ $item['url'] }}" class="flex flex-col items-center justify-center gap-1 py-2 active:scale-95 transition-transform">
                <span class="flex items-center justify-center w-10 h-7 rounded-full transition-colors {{ $item['active'] ? 'bg-orange-50 dark:bg-orange-500/15' : '' }}">
                    <svg class="w-5 h-5 {{ $item['active'] ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.5' : '2' }}" d="{{ $item['icon'] }}"/></svg>
                </span>
                <span class="text-[10px] font-medium leading-none {{ $item['active'] ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach

        <button type="button" @click="$store.adminSidebar.open = true"
            class="flex flex-col items-center justify-center gap-1 py-2 active:scale-95 transition-transform">
            <span class="flex items-center justify-center w-10 h-7 rounded-full">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </span>
            <span class="text-[10px] font-medium leading-none text-gray-500 dark:text-gray-400">More</span>
        </button>
    </div>
</nav>
