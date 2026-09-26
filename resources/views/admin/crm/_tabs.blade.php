@php
    $can = fn ($p) => auth()->user()->hasPermission($p);
    $tabs = [
        ['admin.crm.dashboard', 'Overview', 'admin.crm.dashboard', 'crm.view'],
        ['admin.crm.contacts.index', 'Customers', 'admin.crm.contacts.*', 'crm.view'],
        ['admin.crm.leads.index', 'Leads', 'admin.crm.leads.*', 'crm.view'],
        ['admin.crm.tasks.index', 'Tasks', 'admin.crm.tasks.*', 'crm.view'],
        ['admin.crm.segments.index', 'Segments', 'admin.crm.segments.*', 'crm.view'],
        ['admin.crm.analytics', 'Analytics', 'admin.crm.analytics', 'crm.view'],
        ['admin.crm.settings', 'Settings', 'admin.crm.settings', 'crm.manage'],
    ];
    $openTasks = \App\Models\Crm\CrmTask::open()->where('due_at', '<', now())->count();
@endphp
<div class="mb-5 -mx-1 overflow-x-auto scrollbar-hide">
    <div class="flex items-center gap-1.5 px-1 min-w-max">
        @foreach($tabs as [$route, $label, $pattern, $perm])
            @if($can($perm))
                <a href="{{ route($route) }}"
                   class="px-3.5 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs($pattern) ? 'bg-orange-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                    {{ $label }}
                    @if($label === 'Tasks' && $openTasks > 0)
                        <span class="ml-1 inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 rounded-full text-[10px] font-bold bg-red-500 text-white">{{ $openTasks > 99 ? '99+' : $openTasks }}</span>
                    @endif
                </a>
            @endif
        @endforeach
    </div>
</div>
@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <div class="font-semibold mb-1">Please fix the following:</div>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
