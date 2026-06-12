<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = auth()->user()->addresses()->orderByDesc('is_default')->get();
        return view('account.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('account.addresses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'phone'         => 'required|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'required|string|max:100',
            'state'         => 'nullable|string|max:100',
            'zip'           => 'nullable|string|max:20',
            'country'       => 'required|string|max:100',
            'is_default'    => 'boolean',
        ]);

        $data['user_id'] = auth()->id();

        if (!empty($data['is_default'])) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        // First address is default automatically
        if (auth()->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        Address::create($data);
        return redirect()->route('account.addresses.index')->with('success', 'Address added.');
    }

    public function edit(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
        return view('account.addresses.edit', compact('address'));
    }

    public function update(Request $request, Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'phone'         => 'required|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'required|string|max:100',
            'state'         => 'nullable|string|max:100',
            'zip'           => 'nullable|string|max:20',
            'country'       => 'required|string|max:100',
            'is_default'    => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            Address::where('user_id', auth()->id())->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);
        return redirect()->route('account.addresses.index')->with('success', 'Address updated.');
    }

    public function destroy(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
        $address->delete();

        // Promote newest address to default if deleted was default
        if ($address->is_default) {
            auth()->user()->addresses()->latest()->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'Address removed.');
    }

    public function setDefault(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
        Address::where('user_id', auth()->id())->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return back()->with('success', 'Default address updated.');
    }
}
