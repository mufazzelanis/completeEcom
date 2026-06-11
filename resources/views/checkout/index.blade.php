@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Checkout</h1>

    {{-- Validation error summary --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-700 mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm text-red-600">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('checkout.store') }}" method="POST"
        x-data="{
            selected: '{{ old('payment_method', $paymentMethods->first()?->slug) }}',
            methods:  {{ Js::from($paymentMethods->map(fn($m) => [
                'slug'           => $m->slug,
                'name'           => $m->name,
                'type'           => $m->type,
                'description'    => $m->description,
                'instructions'   => $m->instructions,
                'account_name'   => $m->account_name,
                'account_number' => $m->account_number,
                'bank_name'      => $m->bank_name,
                'charge'         => $methodCharges[$m->slug] ?? 0,
            ])) }},
            base: {{ $base }},
            get current() { return this.methods.find(m => m.slug === this.selected) || {} },
            get total() { return this.base + (parseFloat(this.current.charge) || 0) },
            needsTxn(type) { return ['mobile_banking','bank_transfer'].includes(type) }
        }">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Shipping Info -->
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Shipping Address</h2>

                    @if($addresses->isNotEmpty())
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Use saved address</label>
                            <div class="space-y-2">
                                @foreach($addresses as $address)
                                    <label class="flex items-start space-x-3 cursor-pointer bg-gray-50 rounded-xl p-4 hover:bg-indigo-50 transition"
                                        onclick="fillAddress({{ json_encode($address) }})">
                                        <input type="radio" name="saved_address" value="{{ $address->id }}" class="mt-1 text-indigo-600">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800">{{ $address->name }}</p>
                                            <p class="text-gray-500">{{ $address->address_line1 }}, {{ $address->city }}</p>
                                            <p class="text-gray-500">{{ $address->phone }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Or fill in a new address below</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_name" id="shipping_name"
                                value="{{ old('shipping_name', auth()->user()->name) }}"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('shipping_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                            @error('shipping_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_phone" id="shipping_phone"
                                value="{{ old('shipping_phone', auth()->user()->phone) }}"
                                placeholder="01XXXXXXXXX"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('shipping_phone') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                            @error('shipping_phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_address" id="shipping_address"
                                value="{{ old('shipping_address') }}"
                                placeholder="Street address, house number, area..."
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('shipping_address') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                            @error('shipping_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_city" id="shipping_city"
                                value="{{ old('shipping_city') }}"
                                placeholder="Dhaka"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('shipping_city') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                            @error('shipping_city')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                            <input type="text" name="shipping_state" id="shipping_state"
                                value="{{ old('shipping_state') }}"
                                placeholder="Dhaka"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                            <input type="text" name="shipping_zip" id="shipping_zip"
                                value="{{ old('shipping_zip') }}"
                                placeholder="1207"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <input type="text" name="shipping_country" value="Bangladesh" readonly
                                class="w-full border border-gray-100 rounded-xl px-4 py-2.5 text-sm bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes <span class="text-gray-400">(optional)</span></label>
                        <textarea name="notes" rows="2" placeholder="Any special instructions..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Payment Method — dynamic via Alpine.js (state lives on parent form) -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Payment Method</h2>

                    @error('payment_method')
                        <p class="text-red-500 text-xs mb-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $message }}</p>
                    @enderror

                    {{-- Method selector --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                        @foreach($paymentMethods as $pm)
                        <label @click="selected = '{{ $pm->slug }}'"
                            class="flex items-center space-x-3 border rounded-xl p-4 cursor-pointer transition"
                            :class="selected === '{{ $pm->slug }}' ? 'border-indigo-500 bg-indigo-50 shadow-sm' : 'border-gray-200 hover:border-indigo-300'">
                            <input type="radio" name="payment_method" value="{{ $pm->slug }}"
                                {{ old('payment_method', $paymentMethods->first()?->slug) === $pm->slug ? 'checked' : '' }}
                                x-model="selected" class="text-indigo-600 sr-only">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden flex items-center justify-center
                                @if($pm->type === 'cod') bg-green-100
                                @elseif($pm->type === 'mobile_banking') bg-pink-100
                                @elseif($pm->type === 'card') bg-purple-100
                                @else bg-blue-100 @endif">
                                @if($pm->logo)
                                    <img src="{{ Storage::url($pm->logo) }}" class="w-8 h-8 object-contain">
                                @elseif($pm->type === 'cod')
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                @elseif($pm->type === 'mobile_banking')
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 text-sm">{{ $pm->name }}</p>
                                <p class="text-xs text-gray-400">{{ $pm->description }}</p>
                                @if($pm->charge_type !== 'none')
                                    <p class="text-xs text-orange-500 mt-0.5">
                                        + @if($pm->charge_type === 'percent'){{ $pm->charge_value }}% fee@else৳{{ number_format($pm->charge_value, 2) }} fee@endif
                                    </p>
                                @endif
                            </div>
                            <div :class="selected === '{{ $pm->slug }}' ? 'text-indigo-600' : 'text-transparent'">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    {{-- Instructions panel (shown when method selected) --}}
                    <div x-show="current.instructions" x-cloak
                        class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-xs font-semibold text-blue-700 mb-1" x-text="current.name + ' Instructions'"></p>
                        <template x-if="current.account_number">
                            <div class="bg-white border border-blue-200 rounded-lg p-3 mb-2 flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500" x-text="current.type === 'mobile_banking' ? 'Send to this number' : 'Account Number'"></p>
                                    <p class="font-bold text-gray-900 text-lg font-mono" x-text="current.account_number"></p>
                                    <p class="text-xs text-gray-400" x-text="current.account_name"></p>
                                </div>
                                <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                        </template>
                        <p class="text-xs text-blue-700 whitespace-pre-line" x-text="current.instructions"></p>
                    </div>

                    {{-- TXN fields for mobile banking / bank transfer --}}
                    <div x-show="needsTxn(current.type)" x-cloak class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID *</label>
                                <input type="text" name="transaction_id" value="{{ old('transaction_id') }}"
                                    placeholder="e.g. 8M3R6TXYZ"
                                    class="w-full border rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('transaction_id') border-red-400 bg-red-50 @else border-gray-200 @enderror">
                                @error('transaction_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Mobile Number *</label>
                                <input type="text" name="sender_number" value="{{ old('sender_number') }}"
                                    placeholder="01XXXXXXXXX"
                                    class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('sender_number') border-red-400 bg-red-50 @else border-gray-200 @enderror">
                                @error('sender_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">Your order will be confirmed after we verify your payment (usually within 1–2 hours).</p>
                    </div>

                    {{-- COD confirmation --}}
                    <div x-show="current.type === 'cod'" x-cloak
                        class="bg-green-50 border border-green-200 rounded-xl p-3 flex items-center space-x-3">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-green-700">Pay in cash when your order arrives. No advance payment needed.</p>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Summary</h2>

                    <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                        @foreach($cartItems as $item)
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($item->product->image)
                                        <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 truncate">{{ $item->product->name }}</p>
                                    <p class="text-xs text-gray-400">× {{ $item->quantity }}</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 flex-shrink-0">৳{{ number_format($item->subtotal) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>৳{{ number_format($subtotal) }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount ({{ $coupon }})</span>
                                <span>-৳{{ number_format($discount) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>৳{{ number_format($shipping) }}</span>
                        </div>
                        {{-- Payment charge — shown dynamically --}}
                        <div class="flex justify-between text-orange-600" x-show="(current.charge||0) > 0" x-cloak>
                            <span x-text="(current.name || 'Payment') + ' Fee'"></span>
                            <span x-text="'৳' + parseFloat(current.charge||0).toFixed(2)"></span>
                        </div>
                        <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900 text-base">
                            <span>Total</span>
                            <span x-text="'৳' + total.toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0})">৳{{ number_format($base) }}</span>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full mt-6 bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Place Order</span>
                    </button>

                    <p class="text-xs text-gray-400 text-center mt-3">
                        By placing an order you agree to our Terms &amp; Conditions
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function fillAddress(address) {
    document.getElementById('shipping_name').value    = address.name          || '';
    document.getElementById('shipping_phone').value   = address.phone         || '';
    document.getElementById('shipping_address').value = address.address_line1 || '';
    document.getElementById('shipping_city').value    = address.city          || '';
    document.getElementById('shipping_state').value   = address.state         || '';
    document.getElementById('shipping_zip').value     = address.zip           || '';
}
</script>
@endsection
