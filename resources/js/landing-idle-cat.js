const IDLE_DELAY = 7000;
const PEEK_DURATION = 3000;
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let hasPeeked = false;
let cleanupIdleCat;

function initIdleCat() {
    cleanupIdleCat?.();
    cleanupIdleCat = undefined;

    const cat = document.querySelector('[data-landing-idle-cat]');
    if (!cat || hasPeeked || reducedMotion.matches) return;

    const listeners = new AbortController();
    let idleTimer;
    let hideTimer;
    let disposed = false;

    function clearTimers() {
        window.clearTimeout(idleTimer);
        window.clearTimeout(hideTimer);
    }

    function dispose() {
        disposed = true;
        clearTimers();
        cat.classList.remove('is-peeking');
        listeners.abort();
    }

    function showCat() {
        if (disposed || hasPeeked || document.hidden || !document.hasFocus() || reducedMotion.matches || !cat.isConnected) return;
        if (document.activeElement?.closest('input, textarea, select, [contenteditable="true"]')) return;

        hasPeeked = true;
        cat.classList.add('is-peeking');
        hideTimer = window.setTimeout(dispose, PEEK_DURATION);
    }

    function resetIdleTimer() {
        clearTimers();
        cat.classList.remove('is-peeking');

        if (hasPeeked) {
            dispose();
            return;
        }

        if (disposed || document.hidden || !document.hasFocus() || reducedMotion.matches) return;
        idleTimer = window.setTimeout(showCat, IDLE_DELAY);
    }

    for (const eventName of ['pointermove', 'pointerdown', 'keydown', 'scroll', 'touchstart']) {
        window.addEventListener(eventName, resetIdleTimer, {
            passive: true,
            capture: true,
            signal: listeners.signal,
        });
    }
    window.addEventListener('blur', resetIdleTimer, { signal: listeners.signal });
    window.addEventListener('focus', resetIdleTimer, { signal: listeners.signal });
    document.addEventListener('visibilitychange', resetIdleTimer, { signal: listeners.signal });

    cleanupIdleCat = dispose;
    resetIdleTimer();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIdleCat, { once: true });
} else {
    initIdleCat();
}

document.addEventListener('livewire:navigated', initIdleCat);
document.addEventListener('livewire:navigating', () => cleanupIdleCat?.());
window.addEventListener('pageshow', initIdleCat);
window.addEventListener('pagehide', () => cleanupIdleCat?.());
reducedMotion.addEventListener('change', initIdleCat);
