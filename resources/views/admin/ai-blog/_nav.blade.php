{{-- AI Blog section header: tabs + this section's messages (incl. warnings / validation errors). --}}
@php
    $tabs = [
        ['admin.ai-blog.index', 'Dashboard', 'admin.ai-blog.index'],
        ['admin.ai-blog.topics', 'Topic Planner', 'admin.ai-blog.topics*'],
        ['admin.ai-blog.weak', 'Weak Posts', 'admin.ai-blog.weak'],
    ];
    if (auth()->user()?->hasPermission('settings.manage')) {
        $tabs[] = ['admin.ai-blog.settings', 'Settings', 'admin.ai-blog.settings'];
    }
@endphp
<div class="mb-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span aria-hidden="true">✨</span> AI Blog
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">Product-based articles in Bangla &amp; English, written on your schedule.</p>
        </div>
        <nav class="flex flex-wrap gap-1 rounded-xl bg-gray-100 dark:bg-gray-800 p-1 text-sm font-medium">
            @foreach($tabs as [$route, $label, $pattern])
                <a href="{{ route($route) }}"
                   class="px-3.5 py-1.5 rounded-lg transition {{ request()->routeIs($pattern) ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    @foreach(['success' => 'bg-green-50 text-green-800 ring-green-200', 'warning' => 'bg-amber-50 text-amber-800 ring-amber-200', 'error' => 'bg-red-50 text-red-800 ring-red-200'] as $type => $cls)
        @if(session($type))
            <div class="mt-4 rounded-xl ring-1 px-4 py-3 text-sm {{ $cls }}">{{ session($type) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="mt-4 rounded-xl ring-1 ring-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
        </div>
    @endif
</div>
