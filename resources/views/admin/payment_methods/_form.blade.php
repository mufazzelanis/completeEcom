{{-- $method = existing PaymentMethod or null --}}
<div class="space-y-5" x-data="{ type: '{{ old('type', $method?->type ?? 'cod') }}', chargeType: '{{ old('charge_type', $method?->charge_type ?? 'none') }}' }">

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Display Name *</label>
            <input type="text" name="name" value="{{ old('name', $method?->name) }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select name="type" x-model="type"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="cod"            :selected="type==='cod'">Cash on Delivery</option>
                <option value="mobile_banking" :selected="type==='mobile_banking'">Mobile Banking (bKash / Nagad etc.)</option>
                <option value="bank_transfer"  :selected="type==='bank_transfer'">Bank Transfer</option>
                <option value="card"           :selected="type==='card'">Credit / Debit Card</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <input type="text" name="description" value="{{ old('description', $method?->description) }}" placeholder="Short customer-facing description"
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    {{-- Account info: shown for mobile_banking and bank_transfer --}}
    <div x-show="type === 'mobile_banking' || type === 'bank_transfer'" x-cloak>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                <input type="text" name="account_name" value="{{ old('account_name', $method?->account_name) }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="type === 'bank_transfer' ? 'Account Number' : 'Mobile Number'">Mobile Number</label>
                <input type="text" name="account_number" value="{{ old('account_number', $method?->account_number) }}" placeholder="01XXXXXXXXX"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    {{-- Bank info: shown for bank_transfer only --}}
    <div x-show="type === 'bank_transfer'" x-cloak>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $method?->bank_name) }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                <input type="text" name="branch" value="{{ old('branch', $method?->branch) }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Routing Number</label>
                <input type="text" name="routing_number" value="{{ old('routing_number', $method?->routing_number) }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Instructions</label>
        <textarea name="instructions" rows="4" placeholder="Step-by-step instructions shown to customer after they select this method…"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('instructions', $method?->instructions) }}</textarea>
    </div>

    {{-- Charges --}}
    <div class="border border-gray-100 rounded-xl p-4 space-y-3">
        <p class="text-sm font-semibold text-gray-700">Gateway Charge (added to order total)</p>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Charge Type</label>
                <select name="charge_type" x-model="chargeType"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="none">None</option>
                    <option value="fixed">Fixed (৳)</option>
                    <option value="percent">Percent (%)</option>
                </select>
            </div>
            <div x-show="chargeType !== 'none'" x-cloak>
                <label class="block text-xs font-medium text-gray-500 mb-1" x-text="chargeType === 'percent' ? 'Rate (%)' : 'Amount (৳)'">Amount</label>
                <input type="number" name="charge_value" value="{{ old('charge_value', $method?->charge_value ?? 0) }}" min="0" step="0.01"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <input type="hidden" name="charge_value" value="0" x-show="chargeType === 'none'">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Min Order Amount (৳)</label>
                <input type="number" name="min_amount" value="{{ old('min_amount', $method?->min_amount) }}" min="0" step="0.01" placeholder="No minimum"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Max Order Amount (৳)</label>
                <input type="number" name="max_amount" value="{{ old('max_amount', $method?->max_amount) }}" min="0" step="0.01" placeholder="No maximum"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Logo / Icon</label>
            @if($method?->logo)
                <img src="{{ Storage::url($method->logo) }}" class="h-10 mb-2 object-contain">
            @endif
            <input type="file" name="logo" accept="image/*"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <p class="text-xs text-gray-400 mt-1">Max 512KB. PNG with transparent background works best.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $method?->sort_order ?? 0) }}" min="0"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>

    <div class="flex items-center space-x-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $method?->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
        <span class="text-sm font-medium text-gray-700">Active (visible to customers)</span>
    </div>
</div>
