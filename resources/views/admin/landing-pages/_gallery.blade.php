{{-- Shared multi-image gallery control for testimonial_images / certificates — plain JSON
     array of stored paths (see LandingPageController@applyGalleries). Existing thumbnails
     get a "remove" checkbox; new files are appended on submit. --}}
@if(!empty($existing))
<div class="grid grid-cols-1 sm:grid-cols-4 gap-2 mb-2">
    @foreach($existing as $path)
    <label class="relative block cursor-pointer group">
        <img src="{{ Storage::url($path) }}" class="w-full h-20 object-cover rounded-lg border border-gray-100">
        <div class="absolute inset-0 bg-black/0 group-has-[:checked]:bg-black/50 rounded-lg transition flex items-center justify-center">
            <input type="checkbox" name="remove_{{ $field }}[]" value="{{ $path }}" class="opacity-0 group-hover:opacity-100 group-has-[:checked]:opacity-100 w-5 h-5 rounded text-red-600 transition">
        </div>
    </label>
    @endforeach
</div>
<p class="text-xs text-gray-400 mb-2">Tick a thumbnail to remove it when you save.</p>
@endif
<input type="file" name="{{ $field }}[]" accept="image/*" multiple class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
