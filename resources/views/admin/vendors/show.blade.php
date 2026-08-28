@extends('layouts.admin')
@section('title', 'Vendor Details')

@section('content')
<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.vendors.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Vendors</span>
    </a>
    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-700 transition">Edit Vendor</a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        @if($vendor->logo)
            <img src="{{ Storage::url($vendor->logo) }}" alt="{{ $vendor->business_name }}" class="w-16 h-16 rounded-full object-cover mx-auto mb-4">
        @else
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-indigo-600 font-bold text-2xl">{{ strtoupper(substr($vendor->business_name, 0, 1)) }}</span>
            </div>
        @endif
        <h2 class="text-lg font-bold text-gray-900">{{ $vendor->business_name }}</h2>
        <p class="text-gray-500 text-sm">{{ $vendor->email }}</p>
        <p class="text-gray-500 text-sm mt-1">{{ $vendor->phone }}</p>
        @if($vendor->website)
        <a href="{{ $vendor->website }}" target="_blank" class="text-indigo-600 text-xs hover:underline block mt-1">{{ $vendor->website }}</a>
        @endif
        <div class="mt-4">
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $vendor->statusBadge() }}">{{ ucfirst($vendor->status) }}</span>
        </div>
        <p class="text-xs text-gray-400 mt-3">Applied {{ $vendor->created_at->format('M d, Y') }}</p>
        @if($vendor->approved_at)
        <p class="text-xs text-gray-400">Approved {{ $vendor->approved_at->format('M d, Y') }}@if($vendor->approver) by {{ $vendor->approver->name }}@endif</p>
        @endif

        <div class="mt-5 flex flex-col gap-2" x-data="{ correcting: false }">
            @if($vendor->status === 'pending')
            <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Approve</button>
            </form>
            <button type="button" @click="correcting = !correcting" class="w-full bg-orange-50 text-orange-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-100 transition">Request Correction</button>
            <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" onsubmit="return confirm('Reject this application?')">
                @csrf
                <button class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-100 transition">Reject</button>
            </form>
            <div x-show="correcting" x-cloak class="pt-2 border-t border-gray-100">
                <form action="{{ route('admin.vendors.request-correction', $vendor) }}" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="correction_notes" rows="3" required placeholder="What needs to be fixed? e.g. NID back image is blurry, please re-upload."
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-700 transition">Send Correction Request</button>
                </form>
            </div>
            @elseif($vendor->status === 'approved')
            <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST" onsubmit="return confirm('Suspend this vendor?')">
                @csrf
                <button class="w-full bg-orange-50 text-orange-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-100 transition">Suspend</button>
            </form>
            @elseif(in_array($vendor->status, ['suspended', 'rejected']))
            <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Re-approve</button>
            </form>
            @elseif($vendor->status === 'needs_correction')
            <p class="text-xs text-gray-400">Waiting for the seller to resubmit corrected information.</p>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-3">Business Details</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $vendor->description ?: 'No description provided.' }}</p>
            @if($vendor->status === 'rejected' && $vendor->rejection_reason)
            <p class="text-sm text-red-600 mt-3"><strong>Rejection reason:</strong> {{ $vendor->rejection_reason }}</p>
            @endif
            @if($vendor->status === 'needs_correction' && $vendor->correction_notes)
            <p class="text-sm text-orange-600 mt-3"><strong>Correction requested:</strong> {{ $vendor->correction_notes }}</p>
            @endif
            <p class="text-sm text-gray-500 mt-3">Commission rate: <strong>{{ $vendor->commission_rate ?? 0 }}%</strong> @if($vendor->payout_method)&middot; Payout via {{ $vendor->payout_method }}@endif</p>
        </div>

        @if($vendor->profile_status !== 'none')
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Profile Change Request</h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $vendor->profileStatusBadge() }}">{{ ucfirst($vendor->profile_status) }}</span>
            </div>

            @if($vendor->profile_status === 'rejected' && $vendor->profile_rejection_reason)
            <p class="text-sm text-red-600 mb-4"><strong>You rejected this with reason:</strong> {{ $vendor->profile_rejection_reason }}</p>
            @endif

            @php $pc = $vendor->pending_changes ?? []; @endphp
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 text-left">Field</th>
                            <th class="py-2 text-left">Current</th>
                            <th class="py-2 text-left">Proposed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach(\App\Models\Vendor::EDITABLE_PROFILE_FIELDS as $field)
                            @continue(!array_key_exists($field, $pc))
                            <tr>
                                <td class="py-2 text-gray-500 text-xs capitalize">{{ str_replace('_', ' ', $field) }}</td>
                                <td class="py-2 text-xs text-gray-600">
                                    @if($field === 'logo' && $vendor->$field)
                                        <img src="{{ Storage::url($vendor->$field) }}" class="h-10 rounded-lg border border-gray-100">
                                    @elseif($field === 'payout_details')
                                        {{ collect($vendor->payout_details ?? [])->filter()->map(fn($v, $k) => ucfirst($k).': '.$v)->implode(', ') ?: '—' }}
                                    @else
                                        {{ $vendor->$field ?: '—' }}
                                    @endif
                                </td>
                                <td class="py-2 text-xs text-gray-900 font-medium bg-yellow-50">
                                    @if($field === 'logo' && $pc[$field])
                                        <img src="{{ Storage::url($pc[$field]) }}" class="h-10 rounded-lg border border-gray-100">
                                    @elseif($field === 'payout_details')
                                        {{ collect($pc[$field] ?? [])->filter()->map(fn($v, $k) => ucfirst($k).': '.$v)->implode(', ') ?: '—' }}
                                    @else
                                        {{ $pc[$field] ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($vendor->profile_status === 'pending')
            <div class="flex flex-col gap-2 max-w-sm" x-data="{ rejectingProfile: false }">
                <form action="{{ route('admin.vendors.approve-profile', $vendor) }}" method="POST">
                    @csrf
                    <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Approve Profile Changes</button>
                </form>
                <button type="button" @click="rejectingProfile = !rejectingProfile" class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-100 transition">Reject Profile Changes</button>
                <div x-show="rejectingProfile" x-cloak class="pt-2">
                    <form action="{{ route('admin.vendors.reject-profile', $vendor) }}" method="POST" class="space-y-2">
                        @csrf
                        <textarea name="profile_rejection_reason" rows="2" required placeholder="Why are these changes being rejected?"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-700 transition">Confirm Rejection</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Products</h2>
                <a href="{{ route('admin.products.index', ['seller' => $vendor->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                    {{ $vendor->products_count }} product(s) &middot; View all →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 text-left">Product</th>
                            <th class="py-2 text-left">Category</th>
                            <th class="py-2 text-right">Price</th>
                            <th class="py-2 text-center">Stock</th>
                            <th class="py-2 text-center">Status</th>
                            <th class="py-2 text-center">Approval</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($products as $product)
                        <tr>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                        @if($product->image)<img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">@endif
                                    </div>
                                    <span class="text-gray-800 truncate max-w-40">{{ $product->name }}</span>
                                </div>
                            </td>
                            <td class="py-2 text-gray-500 text-xs">{{ $product->category->name ?? '—' }}</td>
                            <td class="py-2 text-right text-xs font-semibold">৳{{ number_format($product->price) }}</td>
                            <td class="py-2 text-center text-xs">{{ $product->stock }}</td>
                            <td class="py-2 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-2 text-center">
                                @php
                                    $prodApprovalColor = match($product->approval_status) {
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default => 'bg-green-100 text-green-700',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $prodApprovalColor }}">{{ ucfirst($product->approval_status) }}</span>
                            </td>
                            <td class="py-2 text-right">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="py-8 text-center text-gray-400 text-sm">No products listed yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $products->links() }}</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6" x-data="{ payingOut: false }">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Earnings &amp; Payouts</h2>
                @if($earnings['available'] > 0)
                <button type="button" @click="payingOut = !payingOut" class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">
                    Settle Payout (৳{{ number_format($earnings['available']) }})
                </button>
                @endif
            </div>

            @if($earnings['available'] > 0)
            <div x-show="payingOut" x-cloak class="bg-gray-50 rounded-xl p-4 mb-5">
                <form action="{{ route('admin.vendors.payout', $vendor) }}" method="POST" class="space-y-3">
                    @csrf
                    <p class="text-sm text-gray-600">Settling <strong>৳{{ number_format($earnings['available']) }}</strong> across every transaction currently marked "Available".</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Payout Method</label>
                            <input type="text" name="method" value="{{ $vendor->payout_method }}" placeholder="e.g. bKash, Bank Transfer"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Reference / Transaction ID</label>
                            <input type="text" name="reference" placeholder="e.g. bKash TrxID"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes (optional)</label>
                        <input type="text" name="notes" placeholder="Any additional note for this payout"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-green-700 transition">
                        Confirm Payout of ৳{{ number_format($earnings['available']) }}
                    </button>
                </form>
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider">On Hold</p>
                    <p class="text-lg font-bold text-yellow-600">৳{{ number_format($earnings['hold']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Available</p>
                    <p class="text-lg font-bold text-blue-600">৳{{ number_format($earnings['available']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Paid Out</p>
                    <p class="text-lg font-bold text-green-600">৳{{ number_format($earnings['paid']) }}</p>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-700 mb-2">Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 text-left">Date</th>
                            <th class="py-2 text-left">Order</th>
                            <th class="py-2 text-right">Sale</th>
                            <th class="py-2 text-right">Commission</th>
                            <th class="py-2 text-right">Net</th>
                            <th class="py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($transactions as $tx)
                        <tr>
                            <td class="py-2 text-xs text-gray-500">{{ $tx->created_at->format('M d, Y') }}</td>
                            <td class="py-2 text-xs font-mono text-gray-600">{{ $tx->order->order_number ?? '#'.$tx->order_id }}</td>
                            <td class="py-2 text-right text-xs">৳{{ number_format($tx->sale_amount) }}</td>
                            <td class="py-2 text-right text-xs text-gray-400">৳{{ number_format($tx->commission_amount) }}</td>
                            <td class="py-2 text-right text-xs font-semibold">৳{{ number_format($tx->net_amount) }}</td>
                            <td class="py-2 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tx->statusBadge() }}">{{ ucfirst($tx->status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-400 text-sm">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $transactions->links() }}</div>

            <h3 class="text-sm font-semibold text-gray-700 mb-2 mt-6">Payout History</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 text-left">Date</th>
                            <th class="py-2 text-left">Method</th>
                            <th class="py-2 text-left">Reference</th>
                            <th class="py-2 text-left">Processed By</th>
                            <th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($payouts as $payout)
                        <tr>
                            <td class="py-2 text-xs text-gray-500">{{ $payout->created_at->format('M d, Y') }}</td>
                            <td class="py-2 text-xs text-gray-600">{{ $payout->method ?: '—' }}</td>
                            <td class="py-2 text-xs font-mono text-gray-500">{{ $payout->reference ?: '—' }}</td>
                            <td class="py-2 text-xs text-gray-500">{{ $payout->processedBy->name ?? '—' }}</td>
                            <td class="py-2 text-right text-xs font-semibold text-green-700">৳{{ number_format($payout->amount) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="py-8 text-center text-gray-400 text-sm">No payouts recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $payouts->links() }}</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-3">Identity Verification</h2>
            @if($vendor->document_type === 'nid')
                <p class="text-sm text-gray-600 mb-3">National ID @if($vendor->nid_number)&mdash; <span class="font-mono">{{ $vendor->nid_number }}</span>@endif</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($vendor->nid_front_image)
                    <a href="{{ route('admin.vendors.document', [$vendor, 'nid_front_image']) }}" target="_blank" class="block">
                        <img src="{{ route('admin.vendors.document', [$vendor, 'nid_front_image']) }}" class="rounded-xl border border-gray-100 w-full h-32 object-cover hover:opacity-80 transition">
                        <p class="text-xs text-gray-400 mt-1 text-center">Front</p>
                    </a>
                    @endif
                    @if($vendor->nid_back_image)
                    <a href="{{ route('admin.vendors.document', [$vendor, 'nid_back_image']) }}" target="_blank" class="block">
                        <img src="{{ route('admin.vendors.document', [$vendor, 'nid_back_image']) }}" class="rounded-xl border border-gray-100 w-full h-32 object-cover hover:opacity-80 transition">
                        <p class="text-xs text-gray-400 mt-1 text-center">Back</p>
                    </a>
                    @endif
                </div>
            @elseif($vendor->document_type === 'birth_certificate' && $vendor->birth_certificate_image)
                <p class="text-sm text-gray-600 mb-3">Birth Certificate</p>
                <a href="{{ route('admin.vendors.document', [$vendor, 'birth_certificate_image']) }}" target="_blank" class="block max-w-xs">
                    <img src="{{ route('admin.vendors.document', [$vendor, 'birth_certificate_image']) }}" class="rounded-xl border border-gray-100 w-full h-40 object-cover hover:opacity-80 transition">
                </a>
            @else
                <p class="text-sm text-gray-400">No identity document on file.</p>
            @endif
        </div>
    </div>
</div>
@endsection
