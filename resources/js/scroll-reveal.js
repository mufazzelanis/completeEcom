// Adds `.is-visible` to `.reveal` / `.reveal-group` elements the first time
// each one scrolls into view, then stops watching it — a one-shot entrance,
// not a re-triggering scroll effect. All the actual animation (timing,
// distance, stagger) lives in CSS; this file only decides *when* to flip the
// class. See layouts/app.blade.php's <style> block for the `.reveal` rules.
document.addEventListener('DOMContentLoaded', () => {
    const targets = document.querySelectorAll('.reveal, .reveal-group');
    if (!targets.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || !('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    targets.forEach((el) => observer.observe(el));
});
