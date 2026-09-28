@props(['name', 'title'])
@php
    // The links inside are already rendered with their own active styling by the time this slot
    // reaches us, so "does this group contain the page being viewed?" is answered by looking for
    // that styling — not by re-listing every group's route patterns here, which would silently
    // drift out of sync the next time a sidebar link is added or renamed.
    $hasActive = (bool) preg_match('/bg-(?:orange-600|red-700) text-white/', (string) $slot);

    // The collapsed icon rail (see app.css's "Collapsible icon rail" block) needs some glyph to
    // show for the group itself, since its head is otherwise just a title (hidden when
    // collapsed). Rather than adding an `icon` prop to every one of this component's ~16 call
    // sites, the first link's own icon is reused — it's already representative of the section,
    // and one less place for a group's icon to silently drift from what's actually inside it.
    $groupIcon = null;
    if (preg_match('/<svg\b.*?<\/svg>/s', (string) $slot, $m)) {
        $groupIcon = $m[0];
    }
@endphp
{{-- Open/closed state is applied server-side only for the group holding the current page;
     everything else (groups the admin pinned open) is restored by the script in
     partials/admin/sidebar-nav-behavior.blade.php before the sidebar is first shown. --}}
<div class="admin-nav-group" data-nav-group="{{ $name }}" data-has-active="{{ $hasActive ? '1' : '0' }}" data-open="{{ $hasActive ? '1' : '0' }}">
    <button type="button" class="admin-nav-group__head" aria-expanded="{{ $hasActive ? 'true' : 'false' }}" aria-controls="admin-nav-{{ $name }}">
        @if($groupIcon)
            <span class="admin-nav-group__icon" aria-hidden="true">{!! $groupIcon !!}</span>
        @endif
        <span class="admin-nav-group__title">{{ $title }}</span>
        <span class="admin-nav-group__dot" aria-hidden="true" title="Current page is in this section"></span>
        <svg class="admin-nav-group__chev" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="admin-nav-group__body" id="admin-nav-{{ $name }}" role="group" aria-label="{{ $title }}" data-flyout-title="{{ $title }}">
        <div class="admin-nav-group__inner space-y-1">
            {{ $slot }}
        </div>
    </div>
</div>
