const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const seen = new WeakSet();
const observed = new Set();
const active = new Map();
const duration = 550;
const recoveryBuffer = 200;
const offsets = {
    'fade-up': 'translateY(24px)',
    'fade-left': 'translateX(24px)',
    'fade-right': 'translateX(-24px)',
};
let observer;

function stopAnimation(element) {
    const playback = active.get(element);
    if (!playback) return;

    active.delete(element);
    window.clearTimeout(playback.timeout);
    playback.animation.cancel();
}

function animateEntry(element) {
    if (reducedMotion.matches) return;

    if (element.hasAttribute('data-motion-once')) {
        element.classList.add('is-motion-entered');
    }

    if (!element.hasAttribute('data-animate') || typeof element.animate !== 'function') return;

    try {
        const requestedDelay = Number(element.dataset.animateDelay);
        const delay = Number.isFinite(requestedDelay) ? Math.max(0, Math.min(requestedDelay, .3)) * 1000 : 0;
        const animation = element.animate([
            { opacity: 0, transform: offsets[element.dataset.animate] || offsets['fade-up'] },
            { opacity: 1, transform: 'none' },
        ], {
            duration,
            delay,
            easing: 'cubic-bezier(0, 0, 0.58, 1)',
            fill: 'backwards',
        });
        const timeout = window.setTimeout(() => stopAnimation(element), duration + delay + recoveryBuffer);
        active.set(element, { animation, timeout });
        animation.finished.then(() => stopAnimation(element), () => stopAnimation(element));
    } catch {
        stopAnimation(element);
    }
}

function getObserver() {
    if (observer) return observer;
    if (typeof IntersectionObserver !== 'function') return null;

    try {
        observer = new IntersectionObserver((entries, entryObserver) => {
            entries.forEach(({ target, isIntersecting }) => {
                if (!isIntersecting || !observed.has(target)) return;
                entryObserver.unobserve(target);
                observed.delete(target);
                seen.add(target);
                animateEntry(target);
            });
        }, { rootMargin: '0px 0px -24px 0px' });
    } catch {
        return null;
    }
    return observer;
}

function initScrollAnimations() {
    document.querySelectorAll('[data-animate], [data-motion-once]').forEach((element) => {
        if (seen.has(element) || observed.has(element)) return;
        if (reducedMotion.matches || typeof element.animate !== 'function') {
            seen.add(element);
            return;
        }

        const entryObserver = getObserver();
        if (!entryObserver) return;
        try {
            entryObserver.observe(element);
            observed.add(element);
        } catch {
            seen.add(element);
        }
    });
}

function resetAnimations() {
    observer?.disconnect();
    observer = undefined;
    observed.clear();
    active.forEach((_, element) => stopAnimation(element));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScrollAnimations, { once: true });
} else {
    initScrollAnimations();
}
document.addEventListener('livewire:navigated', initScrollAnimations);
document.addEventListener('livewire:navigating', resetAnimations);
reducedMotion.addEventListener('change', () => {
    resetAnimations();
    initScrollAnimations();
});
