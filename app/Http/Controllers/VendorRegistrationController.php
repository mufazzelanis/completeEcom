<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorRegistrationController extends Controller
{
    private const DOCUMENT_FIELDS = ['nid_front_image', 'nid_back_image', 'birth_certificate_image'];

    public function create()
    {
        $vendor = auth()->user()->vendor;

        // Nothing left to show once approved — send them straight into the
        // dashboard where they actually manage products/orders/reports.
        if ($vendor && $vendor->status === 'approved') {
            return redirect()->route('seller.dashboard');
        }

        return view('vendor-registration.create', compact('vendor'));
    }

    /**
     * Lets a seller view their own previously-uploaded document while
     * resubmitting a corrected application — scoped strictly to their own
     * vendor row, same whitelist-the-field-name safeguard as the admin route.
     */
    public function document(string $field)
    {
        abort_unless(in_array($field, self::DOCUMENT_FIELDS, true), 404);

        $vendor = auth()->user()->vendor;
        abort_unless($vendor, 404);

        $path = $vendor->$field;
        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response($path);
    }

    public function store(Request $request)
    {
        $vendor = auth()->user()->vendor;
        $isCorrection = $vendor && $vendor->status === 'needs_correction';

        if ($vendor && !$isCorrection) {
            return redirect()->route('vendor.apply')->with('error', 'You have already applied to become a seller.');
        }

        // On a correction resubmission, documents are optional — the seller only
        // re-uploads whichever file admin flagged, everything else stays as-is.
        $imageRule = $isCorrection ? 'nullable' : 'required_if:document_type,nid';
        $birthRule = $isCorrection ? 'nullable' : 'required_if:document_type,birth_certificate';

        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'document_type' => 'required|in:nid,birth_certificate',
            'nid_number' => 'required_if:document_type,nid|nullable|string|max:50',
            'nid_front_image' => "{$imageRule}|image|max:4096",
            'nid_back_image' => "{$imageRule}|image|max:4096",
            'birth_certificate_image' => "{$birthRule}|image|max:4096",
        ]);

        $data = $request->only(['business_name', 'phone', 'email', 'website', 'description', 'document_type', 'nid_number']);
        $folder = 'vendor-documents/' . auth()->id();

        if ($request->document_type === 'nid') {
            if ($request->hasFile('nid_front_image')) {
                $data['nid_front_image'] = $request->file('nid_front_image')->store($folder, 'private');
            }
            if ($request->hasFile('nid_back_image')) {
                $data['nid_back_image'] = $request->file('nid_back_image')->store($folder, 'private');
            }
        } elseif ($request->hasFile('birth_certificate_image')) {
            $data['birth_certificate_image'] = $request->file('birth_certificate_image')->store($folder, 'private');
        }

        if ($isCorrection) {
            $vendor->update($data + ['status' => 'pending', 'correction_notes' => null]);

            return redirect()->route('vendor.apply')->with('success', 'Your updated application has been resubmitted for review.');
        }

        Vendor::create($data + [
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('vendor.apply')->with('success', 'Your seller application has been submitted and is pending review.');
    }
}
