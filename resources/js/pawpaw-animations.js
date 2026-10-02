import { animate, inView } from "motion";

const ANIMATIONS = {
    "fade-up": { from: { opacity: 0, y: 24 }, to: { opacity: 1, y: 0 } },
    "fade-left": { from: { opacity: 0, x: 40 }, to: { opacity: 1, x: 0 } },
    "fade-right": { from: { opacity: 0, x: -40 }, to: { opacity: 1, x: 0 } },
};

function initScrollAnimations() {
    const elements = document.querySelectorAll("[data-animate]");

    elements.forEach((el) => {
        const type = el.dataset.animate;
        const delay = parseFloat(el.dataset.animateDelay || "0");
        const preset = ANIMATIONS[type] || ANIMATIONS["fade-up"];

        // State awal (sebelum masuk viewport)
        Object.assign(el.style, {
            opacity: 0,
            transform:
                preset.from.y !== undefined
                    ? `translateY(${preset.from.y}px)`
                    : preset.from.x !== undefined
                    ? `translateX(${preset.from.x}px)`
                    : "none",
        });

        inView(
            el,
            () => {
                animate(
                    el,
                    { opacity: 1, x: 0, y: 0 },
                    { duration: 0.6, delay, easing: "ease-out" }
                );
            },
            { margin: "-10% 0px -10% 0px" }
        );
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initScrollAnimations);
} else {
    initScrollAnimations();
}