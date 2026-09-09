import Alpine from 'alpinejs';
import { animate, stagger, spring, inView, scroll } from 'motion';

window.Alpine = Alpine;
window.Motion = { animate, stagger, spring, inView, scroll };
window.animate = animate;
window.stagger = stagger;
window.spring = spring;

// Helper animations powered by One Motion (motion)
window.animateFlyout = (el) => {
    if (!el || !window.animate) return;
    window.animate(
        el,
        { opacity: [0, 1], scale: [0.94, 1], x: [-6, 0] },
        { duration: 0.18, easing: [0.16, 1, 0.3, 1] }
    );
};

window.animateTooltip = (el) => {
    if (!el || !window.animate) return;
    window.animate(
        el,
        { opacity: [0, 1], scale: [0.92, 1], x: [-4, 0] },
        { duration: 0.15, easing: [0.16, 1, 0.3, 1] }
    );
};

import { initSpaNavigator, navigateTo } from './spa-navigator.js';

window.navigateTo = navigateTo;

// Initialize Alpine
Alpine.start();

// Initialize SPA Navigator for persistent sidebar and smooth One Motion page transitions
initSpaNavigator();
