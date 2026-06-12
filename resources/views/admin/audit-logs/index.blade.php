@extends('layouts.admin')
@section('title', 'Audit Logs')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Audit Logs</h1>
        <p class="text-sm text-gray-500 mt-0.5">Track all admin actions across the system</p>
    </div>
    <span class="text-sm text-gray-400">{{ $logs->total() }} total entries</span>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Action</label>
            <select name="action" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Actions</option>
                @foreach($actions as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Admin</label>
            <select name="user_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Admins</option>
                @foreach($logUsers as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
            <a href="{{ route('admin.audit-logs.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm hover:bg-gray-200 transition">Clear</a>
        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Time</th>
                <th class="px-5 py-3 text-left">Admin</th>
                <th class="px-5 py-3 text-left">Action</th>
                <th class="px-5 py-3 text-left">Description</th>
                <th class="px-5 py-3 text-left">Model</th>
                <th class="px-5 py-3 text-left">Changes</th>
                <th class="px-5 py-3 text-left">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50" x-data="{}">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50 transition" x-data="{ open: false }">
                <td class="px-5 py-3 whitespace-nowrap">
                    <p class="font-medium text-gray-800 text-xs">{{ $log->created_at->format('M d, Y') }}</p>
                    <p class="text-gray-400 text-xs">{{ $log->created_at->format('H:i:s') }}</p>
                </td>
                <td class="px-5 py-3">
                    @if($log->user)
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 text-xs font-bold">{{ strtoupper(substr($log->user->name, 0, 1)) }}</span>
                        </div>
                        <span class="text-gray-700 text-xs font-medium">{{ $log->user->name }}</span>
                    </div>
                    @else
                    <span class="text-gray-400 text-xs italic">System</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $log->action_color }}">
                        {{ $log->action }}
                    </span>
                </td>
                <td class="px-5 py-3 text-gray-700 max-w-xs">
                    <p class="truncate text-xs">{{ $log->description }}</p>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500">
                    @if($log->model_type)
                    <p class="text-gray-600">{{ class_basename($log->model_type) }}</p>
                    @if($log->model_id)<p class="text-gray-400">#{{ $log->model_id }}</p>@endif
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    @if($log->old_values || $log->new_values)
                    <button @click="open = !open" class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        View diff
                    </button>
                    <div x-show="open" x-cloak x-transition class="mt-2 text-xs space-y-1 bg-gray-50 rounded-lg p-3 min-w-48">
                        @if($log->old_values)
                        <div class="text-red-600 font-medium mb-1">Before:</div>
                        @foreach($log->old_values as $k => $v)
                        <div class="flex gap-2">
                            <span class="text-gray-400 font-mono">{{ $k }}:</span>
                            <span class="text-red-700 font-mono line-through">{{ is_array($v) ? json_encode($v) : $v }}</span>
                        </div>
                        @endforeach
                        @endif
                        @if($log->new_values)
                        <div class="text-green-600 font-medium mt-2 mb-1">After:</div>
                        @foreach($log->new_values as $k => $v)
                        <div class="flex gap-2">
                            <span class="text-gray-400 font-mono">{{ $k }}:</span>
                            <span class="text-green-700 font-mono">{{ is_array($v) ? json_encode($v) : $v }}</span>
                        </div>
                        @endforeach
                        @endif
                    </div>
                    @else
                    <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-gray-400 font-mono">{{ $log->ip_address }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-gray-400">No audit log entries found.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
@endsection
