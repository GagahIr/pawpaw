const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const seen = new WeakSet();
let revealObserver;
let removeClinicHeaderListeners;
let activeHeroObject;
let removeHeroAnimationListeners;

function observeClinicHero() {
    const heroObject = document.querySelector('[data-clinic-hero-object]');
    if (heroObject === activeHeroObject) return;

    removeHeroAnimationListeners?.();
    activeHeroObject = heroObject;
    removeHeroAnimationListeners = undefined;
    if (!heroObject) return;

    let heroObserver;
    let animationRoot;
    const bounds = heroObject.getBoundingClientRect();
    let isVisible = bounds.bottom > 0 && bounds.top < window.innerHeight;

    const updateAnimation = () => {
        const root = heroObject.contentDocument?.documentElement;
        if (root?.localName !== 'svg') return;

        animationRoot = root;
        root.classList.toggle('hero-reduced', reducedMotion.matches);
        root.classList.add('hero-ready');
        root.classList.toggle('hero-running', isVisible && !document.hidden && !reducedMotion.matches);
    };

    heroObject.addEventListener('load', updateAnimation);
    document.addEventListener('visibilitychange', updateAnimation);
    reducedMotion.addEventListener('change', updateAnimation);

    if ('IntersectionObserver' in window) {
        heroObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.target !== heroObject) return;
                isVisible = entry.isIntersecting;
                updateAnimation();
            });
        }, { threshold: 0 });
        heroObserver.observe(heroObject);
    }

    updateAnimation();
    removeHeroAnimationListeners = () => {
        heroObserver?.disconnect();
        animationRoot?.classList.remove('hero-running');
        heroObject.removeEventListener('load', updateAnimation);
        document.removeEventListener('visibilitychange', updateAnimation);
        reducedMotion.removeEventListener('change', updateAnimation);
    };
}

function observeClinicHeader() {
    removeClinicHeaderListeners?.();
    removeClinicHeaderListeners = undefined;
    document.body.classList.remove('clinic-header-scrolled');

    const header = document.querySelector('.clinic-browse-shell [data-flux-header]');
    if (!header) return;

    const updateHeader = () => {
        document.body.classList.toggle('clinic-header-scrolled', window.scrollY >= header.offsetHeight);
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
    window.addEventListener('resize', updateHeader);
    removeClinicHeaderListeners = () => {
        window.removeEventListener('scroll', updateHeader);
        window.removeEventListener('resize', updateHeader);
    };
}

document.addEventListener('DOMContentLoaded', observeClinicHeader, { once: true });
document.addEventListener('livewire:navigated', observeClinicHeader);
document.addEventListener('livewire:navigating', () => removeClinicHeaderListeners?.());
if (document.readyState !== 'loading') observeClinicHeader();

function reveal(element) {
    element.classList.remove('clinic-reveal-waiting');
    element.classList.add('clinic-revealed');
}

function observeClinicElements() {
    observeClinicHero();
    const elements = document.querySelectorAll('.clinic-page .clinic-card, .clinic-footer .clinic-footer-peek, .clinic-detail [data-clinic-reveal]');
    if (reducedMotion.matches || !('IntersectionObserver' in window)) {
        elements.forEach(reveal);
        return;
    }
    revealObserver ??= new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            reveal(target);
            revealObserver.unobserve(target);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });

    elements.forEach((element, index) => {
        if (seen.has(element)) return;
        seen.add(element);
        element.style.setProperty('--clinic-reveal-delay', ((index % 3) * 65) + 'ms');
        element.classList.add('clinic-reveal-waiting');
        revealObserver.observe(element);
    });
}

function resetClinicAnimations() {
    revealObserver?.disconnect();
    revealObserver = undefined;
    document.querySelectorAll('.clinic-reveal-waiting').forEach(reveal);
    observeClinicElements();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observeClinicElements, { once: true });
} else {
    observeClinicElements();
}
document.addEventListener('livewire:navigated', observeClinicElements);
document.addEventListener('livewire:navigating', () => {
    removeHeroAnimationListeners?.();
    removeHeroAnimationListeners = undefined;
    activeHeroObject = undefined;
    revealObserver?.disconnect();
    revealObserver = undefined;
    document.querySelectorAll('.clinic-reveal-waiting').forEach(reveal);
});
reducedMotion.addEventListener('change', resetClinicAnimations);

function registerClinicHooks() {
    window.Livewire.hook('morphed', observeClinicElements);
}
if (window.Livewire) {
    registerClinicHooks();
} else {
    document.addEventListener('livewire:init', registerClinicHooks, { once: true });
}
