{{-- Invisible to real visitors (including keyboard/screen-reader users, via
     aria-hidden + tabindex="-1") and never rendered in the normal flex/flow of
     the form — a bot that blindly fills every input it finds trips this.
     Paired with a render timestamp so instant scripted POSTs get caught too.
     See App\Services\SpamGuard. --}}
<div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
    <input type="text" name="{{ \App\Services\SpamGuard::honeypotField() }}" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Services\SpamGuard::timestampField() }}" value="{{ now()->timestamp }}">
