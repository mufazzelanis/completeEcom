import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Site-wide date/datetime picker upgrade.
 *
 * Every `<input type="date">` / `<input type="datetime-local">` in the app (admin, seller,
 * and customer-facing forms) previously relied on the browser's own native calendar popup —
 * which looks completely different per-OS/browser, ignores the site's dark mode, and (as
 * reported) renders as a tiny, unstyled, hard-to-use popup on mobile. Flatpickr replaces it
 * with one consistent, themeable calendar everywhere, styled to match the app (rounded
 * cards, the same accent color as the surrounding panel, full dark-mode support), and
 * `disableMobile: true` below is what actually fixes the mobile case — without it, flatpickr
 * defers back to the native mobile picker on touch devices, which is the exact "baje" native
 * popup the user's screenshot showed.
 *
 * The original <input> keeps its `name`/`value`/`min`/`max`/`required` attributes and the
 * exact value format the backend already expects (`Y-m-d`, or `Y-m-d\TH:i` for
 * datetime-local) — flatpickr only repurposes it as the element it renders the calendar next
 * to; nothing server-side needed to change. `altInput` gives the user a friendly formatted
 * display field while the real input (hidden, same name) still submits the raw format.
 */
function accentForPath(pathname) {
    // Indigo matches every admin/seller panel screen; orange matches the customer storefront
    // (both already the accent color used site-wide in their respective layouts).
    return (pathname.startsWith('/admin') || pathname.startsWith('/seller')) ? 'indigo' : 'orange';
}

function enhance(input) {
    if (input.dataset.flatpickrInit) return;
    input.dataset.flatpickrInit = '1';

    const isDateTime = input.type === 'datetime-local';
    // Must read min/max and switch away from the native type *before* flatpickr touches the
    // input — left as type="date"/"datetime-local", the browser's own calendar icon/popup
    // stays active inside the field and would pop up alongside (or instead of) flatpickr's,
    // which is the exact double-calendar confusion this is meant to fix.
    const min = input.min || undefined;
    const max = input.max || undefined;
    input.type = 'text';

    flatpickr(input, {
        dateFormat: isDateTime ? 'Y-m-d\\TH:i' : 'Y-m-d',
        altInput: true,
        altFormat: isDateTime ? 'M j, Y — h:i K' : 'M j, Y',
        enableTime: isDateTime,
        time_24hr: false,
        disableMobile: true,
        minDate: min,
        maxDate: max,
        allowInput: true,
        monthSelectorType: 'dropdown',
        onReady(_sel, _str, instance) {
            // Carry the accent + dark-mode state onto flatpickr's own calendar DOM (it's
            // appended straight to <body>, outside any of the app's normal theme wrappers).
            instance.calendarContainer.classList.add('app-datepicker');
            instance.calendarContainer.dataset.accent = accentForPath(window.location.pathname);
            // The altInput flatpickr creates is a brand-new element with none of the
            // Tailwind classes (border/rounded/padding/w-full/focus ring) the original field
            // was given per-form — add them on top (not a straight overwrite, so flatpickr's
            // own classes on this element, e.g. "form-control input", are kept) so every
            // existing date field keeps looking exactly like its neighbors, just with the
            // nicer calendar behind it.
            instance.altInput.classList.add(...input.className.split(/\s+/).filter(Boolean));
            if (input.disabled) instance.altInput.disabled = true;

            // Flatpickr ships no equivalent of the native date picker's own "Today"/"Clear"
            // shortcuts (visible in the old browser popup) — add them back as a small footer.
            const footer = document.createElement('div');
            footer.className = 'fp-footer';
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.textContent = 'Clear';
            clearBtn.addEventListener('click', () => { instance.clear(); instance.close(); });
            const todayBtn = document.createElement('button');
            todayBtn.type = 'button';
            todayBtn.textContent = 'Today';
            todayBtn.addEventListener('click', () => { instance.setDate(new Date(), true); if (!isDateTime) instance.close(); });
            footer.append(clearBtn, todayBtn);
            instance.calendarContainer.appendChild(footer);
        },
    });
}

function enhanceAll(root = document) {
    root.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(enhance);
}

document.addEventListener('DOMContentLoaded', () => enhanceAll());

// Some admin forms (variant matrices, repeatable line items) add rows — and their date
// fields — dynamically via Alpine after page load, with no other hook to enhance them from.
new MutationObserver((mutations) => {
    for (const m of mutations) {
        for (const node of m.addedNodes) {
            if (node.nodeType !== 1) continue;
            if (node.matches?.('input[type="date"], input[type="datetime-local"]')) enhance(node);
            node.querySelectorAll?.('input[type="date"], input[type="datetime-local"]').forEach(enhance);
        }
    }
}).observe(document.documentElement, { childList: true, subtree: true });
