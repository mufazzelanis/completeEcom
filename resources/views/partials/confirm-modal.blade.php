{{-- Site-wide replacement for the browser's native confirm() dialog — used to look like a
     generic OS/browser alert with the raw domain name in the title ("mitavin.com says…"),
     completely off-brand. Included once near the top of <body> in every layout (app, admin,
     seller, account), so it's available everywhere confirm() used to be called.

     Two ways to use it from a Blade view, covering every pattern already in the codebase:
       1. Drop-in replacement for `onsubmit="return confirm('msg')"` /
          `onclick="return confirm('msg')"` — just rename confirm(...) to uiConfirm(event, ...).
          Handles the plain single-button "confirm this form" case: prevents the original
          submit, shows the modal, and (only if confirmed) resubmits the SAME form via the
          real, unhookable form.submit() — which never re-fires onsubmit, so there's no risk
          of re-prompting.
       2. showConfirmModal('message') directly — returns a Promise<boolean>, for the handful
          of places (Alpine @submit/@click handlers) that need to decide what to submit
          themselves, e.g. when which named button/value was clicked matters for the backend.

     Hidden by default; a visitor with JS disabled never sees it, and every guarded form/button
     falls back to submitting immediately with no confirmation at all in that case (same as any
     onsubmit-attribute approach — there's no way to get a blocking native prompt AND graceful
     no-JS degradation at the same time; erring toward "still works" over "still confirms"). --}}
<div id="ui-confirm-modal" hidden class="fixed inset-0 z-[9999] bg-gray-900/50 backdrop-blur-sm" data-confirm-backdrop>
    <div class="w-full h-full flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 uc-pop-in">
            <div class="w-11 h-11 rounded-full bg-red-50 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
            </div>
            <p data-confirm-message class="text-sm text-gray-700 leading-relaxed mb-6">Are you sure?</p>
            <div class="flex justify-end gap-3">
                <button type="button" data-cancel-btn
                    class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" data-confirm-btn
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes uc-pop-in-kf { from { opacity: 0; transform: scale(0.92) translateY(4px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .uc-pop-in { animation: uc-pop-in-kf 0.16s ease-out; }
    @media (prefers-reduced-motion: reduce) { .uc-pop-in { animation: none !important; } }
</style>

<script>
(function () {
    let resolveCurrent = null;

    function modalEl() { return document.getElementById('ui-confirm-modal'); }

    function settle(result) {
        const modal = modalEl();
        if (modal) modal.hidden = true;
        if (resolveCurrent) { const r = resolveCurrent; resolveCurrent = null; r(result); }
    }

    // Core primitive — shows the modal, resolves true/false with the user's choice. Falls
    // back to the native confirm() if the modal markup isn't on the page for some reason
    // (e.g. a stray page that doesn't extend one of the four layouts), so nothing silently
    // stops working.
    window.showConfirmModal = function (message) {
        const modal = modalEl();
        if (!modal) return Promise.resolve(window.confirm(message));
        return new Promise(function (resolve) {
            resolveCurrent = resolve;
            modal.querySelector('[data-confirm-message]').textContent = message;
            modal.hidden = false;
        });
    };

    // Drop-in for onsubmit="return confirm('msg')" / onclick="return confirm('msg')" —
    // rename confirm(...) to uiConfirm(event, ...) and nothing else about the markup needs
    // to change. Resubmits via the real form.submit() (never re-fires onsubmit/'submit'
    // listeners), so this never re-prompts itself.
    window.uiConfirm = function (event, message) {
        event.preventDefault();
        const target = event.target;
        const form = (target instanceof HTMLFormElement) ? target : (target.form || target.closest('form'));
        showConfirmModal(message).then(function (ok) {
            if (ok && form) form.submit();
        });
        return false;
    };

    document.addEventListener('DOMContentLoaded', function () {
        const modal = modalEl();
        if (!modal) return;
        modal.querySelector('[data-confirm-btn]').addEventListener('click', function () { settle(true); });
        modal.querySelector('[data-cancel-btn]').addEventListener('click', function () { settle(false); });
        modal.addEventListener('click', function (e) {
            if (e.target.hasAttribute('data-confirm-backdrop')) settle(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) settle(false);
        });
    });
})();
</script>
