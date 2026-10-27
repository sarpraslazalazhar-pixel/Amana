import Alpine from 'alpinejs';
import { animate, stagger, spring, inView, scroll } from 'motion';
import ApexCharts from 'apexcharts';

window.Alpine = Alpine;
window.Motion = { animate, stagger, spring, inView, scroll };
window.animate = animate;
window.stagger = stagger;
window.spring = spring;
window.ApexCharts = ApexCharts;

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

// Directive Alpine: x-drag-scroll
// Memungkinkan tabel / kontainer overflow digeser (drag-to-scroll) dengan kursor mouse
Alpine.directive('drag-scroll', (el, {}, { cleanup }) => {
    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;
    let hasMoved = false;

    el.classList.add('cursor-grab');

    const onMouseDown = (e) => {
        if (e.button !== 0) return;
        if (e.target.closest('input, button, select, textarea, [data-no-drag]')) return;

        isDown = true;
        hasMoved = false;
        startX = e.pageX - el.offsetLeft;
        scrollLeft = el.scrollLeft;
    };

    const onMouseMove = (e) => {
        if (!isDown) return;
        const x = e.pageX - el.offsetLeft;
        const walk = x - startX;

        if (Math.abs(walk) > 4) {
            hasMoved = true;
            el.classList.add('cursor-grabbing', 'select-none');
            document.body.classList.add('select-none');
        }

        el.scrollLeft = scrollLeft - walk;
    };

    const onMouseUp = () => {
        if (!isDown) return;
        isDown = false;
        el.classList.remove('cursor-grabbing', 'select-none');
        document.body.classList.remove('select-none');

        if (hasMoved) {
            const preventClick = (ev) => {
                ev.stopPropagation();
                ev.preventDefault();
            };
            window.addEventListener('click', preventClick, { capture: true, once: true });
            setTimeout(() => {
                window.removeEventListener('click', preventClick, { capture: true });
            }, 80);
        }
    };

    el.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);

    cleanup(() => {
        el.removeEventListener('mousedown', onMouseDown);
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
    });
});

// Initialize Alpine
Alpine.start();

// Initialize SPA Navigator for persistent sidebar and smooth One Motion page transitions
initSpaNavigator();
