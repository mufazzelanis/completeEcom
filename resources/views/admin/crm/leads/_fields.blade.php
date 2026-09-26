@php $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400'; @endphp
<div class="grid grid-cols-2 gap-3">
    <label class="col-span-2 block text-sm text-gray-600">Name *<input name="name" required maxlength="250" value="{{ old('name', $lead?->name) }}" class="{{ $sel }} mt-1"></label>
    <label class="block text-sm text-gray-600">Phone<input name="phone" value="{{ old('phone', $lead?->phone) }}" class="{{ $sel }} mt-1"></label>
    <label class="block text-sm text-gray-600">E-mail<input type="email" name="email" value="{{ old('email', $lead?->email) }}" class="{{ $sel }} mt-1"></label>
    <label class="block text-sm text-gray-600">Company<input name="company" value="{{ old('company', $lead?->company) }}" class="{{ $sel }} mt-1"></label>
    <label class="block text-sm text-gray-600">Source *
        <select name="source" class="{{ $sel }} mt-1">@foreach(\App\Models\Crm\CrmLead::SOURCES as $k => $l)<option value="{{ $k }}" @selected(old('source', $lead?->source ?? 'phone') === $k)>{{ $l }}</option>@endforeach</select></label>
    <label class="block text-sm text-gray-600">Expected value (৳)<input type="number" min="0" step="1" name="value" value="{{ old('value', $lead ? (int) $lead->value : '') }}" class="{{ $sel }} mt-1"></label>
    <label class="block text-sm text-gray-600">Expected close<input type="date" name="expected_close_on" value="{{ old('expected_close_on', optional($lead?->expected_close_on)->toDateString()) }}" class="{{ $sel }} mt-1"></label>
    <label class="col-span-2 block text-sm text-gray-600">Owner
        <select name="owner_id" class="{{ $sel }} mt-1"><option value="">Unassigned</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((int) old('owner_id', $lead?->owner_id ?? auth()->id()) === $u->id)>{{ $u->name }}</option>@endforeach</select></label>
    <label class="col-span-2 block text-sm text-gray-600">Interest / notes<textarea name="interest" rows="3" class="{{ $sel }} mt-1">{{ old('interest', $lead?->interest) }}</textarea></label>
</div>
