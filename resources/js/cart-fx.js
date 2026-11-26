// Add-to-cart feedback: the product's photo flies along an arc into the cart (the bottom bar's
// orb on phones, the header cart on desktop); on landing the cart "gulps" (squash + bounce), a
// ripple rings out, "+1" floats up, the count pops to its new number and the phone gives a tiny
// tap. Used by the product cards' quick add and the product page's own Add to Cart / 🛒 button
// (layouts/app.blade.php, products/show.blade.php). Plain DOM + Web Animations — no library.

const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const visible = (el) => {
    if (!el) return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < innerHeight && getComputedStyle(el).visibility !== 'hidden';
};

// where the cart is on screen right now
function cartTarget() {
    const orb = document.querySelector('[data-cart-orb]');
    if (visible(orb)) return orb;
    const header = document.getElementById('header-cart-count');
    const link = header && (header.closest('a') || header.parentElement);
    if (visible(link)) return link;
    return orb || link || null;
}

// the picture to fly: the product card's / page's photo, else nothing (the cart still pops)
function sourceImage(from) {
    if (!from) return null;
    if (from.tagName === 'IMG') return from;
    const card = from.closest('[data-product-card], .product-card, article, li, .group');
    const img = card && card.querySelector('img');
    return img && img.currentSrc ? img : null;
}

function setCount(count) {
    if (count === undefined || count === null) return;
    ['header-cart-count', 'bottom-nav-cart-count'].forEach((id) => {
        const badge = document.getElementById(id);
        if (!badge) return;
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.toggle('hidden', count <= 0);
        badge.classList.remove('cfx-count-pop');
        void badge.offsetWidth;
        badge.classList.add('cfx-count-pop');
    });
    document.querySelectorAll('[data-cart-orb]').forEach((o) => o.classList.toggle('has-items', count > 0));
}

function land(target, qty) {
    if (!target) return;
    target.classList.remove('cfx-gulp');
    void target.offsetWidth;
    target.classList.add('cfx-gulp');
    const r = target.getBoundingClientRect();
    const cx = r.left + r.width / 2, cy = r.top + r.height / 2;

    const ripple = document.createElement('span');
    ripple.className = 'cfx-ripple';
    ripple.style.cssText = `left:${cx}px;top:${cy}px;width:${r.width}px;height:${r.width}px`;
    document.body.appendChild(ripple);
    setTimeout(() => ripple.remove(), 700);

    const plus = document.createElement('span');
    plus.className = 'cfx-plus';
    plus.textContent = '+' + (qty || 1);
    plus.style.cssText = `left:${cx}px;top:${r.top}px`;
    document.body.appendChild(plus);
    setTimeout(() => plus.remove(), 900);

    try { navigator.vibrate && navigator.vibrate(12); } catch (e) { /* not allowed */ }
}

/**
 * fly(fromElement, { count, qty }) → Promise, resolved when the photo has landed.
 * fromElement: the button pressed, a product card inside, or an <img>.
 */
export function fly(from, { count, qty = 1 } = {}) {
    const target = cartTarget();
    const img = sourceImage(from);
    if (!target || !img || reduced()) {
        land(target, qty);
        setCount(count);
        return Promise.resolve();
    }

    const s = img.getBoundingClientRect();
    const t = target.getBoundingClientRect();
    const size = Math.min(84, Math.max(48, Math.min(s.width, s.height) * 0.55));
    const startX = s.left + s.width / 2 - size / 2, startY = s.top + s.height / 2 - size / 2;
    const endX = t.left + t.width / 2 - size / 2, endY = t.top + t.height / 2 - size / 2;
    const dx = endX - startX, dy = endY - startY;
    const lift = Math.min(160, Math.max(60, Math.abs(dy) * 0.35)); // arc height

    const ghost = document.createElement('div');
    ghost.className = 'cfx-ghost';
    ghost.style.cssText = `left:${startX}px;top:${startY}px;width:${size}px;height:${size}px`;
    const pic = document.createElement('img');
    pic.src = img.currentSrc || img.src;
    pic.alt = '';
    ghost.appendChild(pic);
    document.body.appendChild(ghost);

    const anim = ghost.animate([
        { transform: 'translate(0,0) scale(.6) rotate(0deg)', opacity: 0 },
        { transform: 'translate(0,-12px) scale(1.08) rotate(-6deg)', opacity: 1, offset: 0.12 },
        { transform: `translate(${dx * 0.45}px, ${dy * 0.45 - lift}px) scale(.85) rotate(8deg)`, opacity: 1, offset: 0.5 },
        { transform: `translate(${dx}px, ${dy}px) scale(.22) rotate(18deg)`, opacity: 0.85 },
    ], { duration: 780, easing: 'cubic-bezier(.45,.05,.55,.95)', fill: 'forwards' });

    return anim.finished.catch(() => {}).then(() => {
        ghost.remove();
        land(target, qty);
        setCount(count);
    });
}

export default { fly, setCount };
