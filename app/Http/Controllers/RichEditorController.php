<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RichEditorController extends Controller
{
    /**
     * Backs the Summernote picture button (resources/views/partials/rich-editor.blade.php).
     * Images go through a real upload rather than inline base64 — the purifier's
     * default URI validation strips data: URIs on save, so an inline image
     * would silently vanish the moment the content is stored.
     */
    public function uploadImage(Request $request)
    {
        abort_unless(auth()->user()->role === 'admin' || auth()->user()->vendor, 403);

        $request->validate([
            'image' => 'required|image|max:4096',
        ]);

        $path = $request->file('image')->store('editor-uploads', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }
}
