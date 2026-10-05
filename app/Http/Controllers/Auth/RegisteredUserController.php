<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        if (request()->filled('ref')) {
            session(['ref_code' => strtoupper(request()->query('ref'))]);
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $isVendor = $request->input('account_type') === 'vendor';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'account_type' => ['nullable', 'in:customer,vendor'],
        ];

        // "Sell on :site" folds the seller application (resources/views/vendor-registration/
        // create.blade.php's fields, normally filled by an already-logged-in customer) directly
        // into registration — same validation that controller applies to a fresh application.
        if ($isVendor) {
            $rules += [
                'business_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'business_email' => ['nullable', 'email', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
                'description' => ['nullable', 'string', 'max:2000'],
                'document_type' => ['required', 'in:nid,birth_certificate'],
                'nid_number' => ['required_if:document_type,nid', 'nullable', 'string', 'max:50'],
                'nid_front_image' => ['required_if:document_type,nid', 'image', 'max:4096'],
                'nid_back_image' => ['required_if:document_type,nid', 'image', 'max:4096'],
                'birth_certificate_image' => ['required_if:document_type,birth_certificate', 'image', 'max:4096'],
            ];
        }

        $request->validate($rules);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($refCode = session('ref_code')) {
            $referralCode = ReferralCode::where('code', $refCode)->where('user_id', '!=', $user->id)->first();
            if ($referralCode) {
                $user->update(['referred_by' => $referralCode->user_id]);
                $referralCode->increment('total_uses');
            }
            session()->forget('ref_code');
        }

        event(new Registered($user));

        Auth::login($user);

        if ($isVendor) {
            $data = $request->only(['business_name', 'phone', 'website', 'description', 'document_type', 'nid_number']);
            $data['email'] = $request->input('business_email');
            $data += Vendor::storeDocumentFiles($request, $user->id, $request->document_type);

            Vendor::create($data + ['user_id' => $user->id, 'status' => 'pending']);

            return redirect()->route('vendor.apply')
                ->with('success', 'Your seller application has been submitted and is pending review.');
        }

        return redirect(route('home', absolute: false));
    }
}
