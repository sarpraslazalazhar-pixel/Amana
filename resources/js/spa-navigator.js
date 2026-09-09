/**
 * AMANA Single-Page Content Navigator
 * Smooth page transitions for Laravel Blade powered by One Motion and Alpine.js.
 * Keeps the sidebar permanently mounted without reload or flicker.
 */

let isNavigating = false;

export function initSpaNavigator() {
    // Intercept clicks on links
    document.addEventListener('click', handleLinkClick);

    // Handle browser back/forward buttons
    window.addEventListener('popstate', () => {
        navigateTo(window.location.href, false);
    });
}

function handleLinkClick(e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    // Check if it's an internal GET link
    if (
        href.startsWith('#') ||
        href.startsWith('javascript:') ||
        href.startsWith('mailto:') ||
        href.startsWith('tel:') ||
        link.getAttribute('target') === '_blank' ||
        link.hasAttribute('download') ||
        link.hasAttribute('data-native') ||
        link.closest('form')
    ) {
        return;
    }

    // Check origin
    try {
        const url = new URL(href, window.location.origin);
        if (url.origin !== window.location.origin) return;

        // Skip same exact URL with hash or same page
        if (url.pathname === window.location.pathname && url.search === window.location.search) {
            if (url.hash) return;
            e.preventDefault();
            return;
        }

        e.preventDefault();
        navigateTo(url.href, true);
    } catch (err) {
        // Not a valid URL, let browser handle it
    }
}

export async function navigateTo(url, pushHistory = true) {
    if (isNavigating) return;
    isNavigating = true;

    const mainEl = document.getElementById('page-main');
    const scrollContainer = document.getElementById('main-scroll-container') || window;
    const headerTitleEl = document.getElementById('header-page-title');

    // 1. One Motion Exit Animation on main content area only
    if (mainEl && window.animate) {
        try {
            await window.animate(
                mainEl,
                { opacity: [1, 0], y: [0, -6] },
                { duration: 0.12, easing: 'ease-in' }
            ).finished;
        } catch (e) {
            // Ignore animation interruption
        }
    }

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!response.ok) {
            window.location.href = url;
            return;
        }

        const htmlText = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');

        // 2. Update Document Title
        if (doc.title) {
            document.title = doc.title;
        }

        // 3. Update Header Page Title
        const newHeaderTitle = doc.getElementById('header-page-title');
        if (headerTitleEl && newHeaderTitle) {
            headerTitleEl.textContent = newHeaderTitle.textContent;
        }

        // 4. Update Sidebar Nav Active States without remounting sidebar
        const newNav = doc.getElementById('sidebar-nav');
        const currentNav = document.getElementById('sidebar-nav');
        if (newNav && currentNav) {
            // Destroy Alpine trees on old nav items before replacing
            destroyAlpineTree(currentNav);
            currentNav.innerHTML = newNav.innerHTML;
            if (window.Alpine) {
                window.Alpine.initTree(currentNav);
            }
        }

        // 5. Update Main Content
        const newMain = doc.getElementById('page-main');
        if (mainEl && newMain) {
            // Destroy Alpine component trees on old content BEFORE replacing innerHTML
            destroyAlpineTree(mainEl);

            // Remove any previously injected page scripts
            document.querySelectorAll('script[data-spa-page-script]').forEach(s => s.remove());

            // Replace content
            mainEl.innerHTML = newMain.innerHTML;

            // Collect ALL page-specific scripts (inside main + @stack('scripts') at body level)
            const scripts = Array.from(doc.querySelectorAll('script')).filter(s => {
                const src = s.getAttribute('src') || '';
                // Skip the app bundle and vite client
                if (src.includes('app-') || src.includes('vite/client')) return false;
                // Skip scripts that are part of the head
                if (s.closest('head')) return false;
                return true;
            });

            // Execute scripts synchronously — the key fix:
            // Scripts that use `document.addEventListener('alpine:init', cb)` need
            // their callback invoked BEFORE Alpine.initTree runs.
            // We execute them first, then manually fire 'alpine:init', then initTree.
            for (const oldScript of scripts) {
                const newScript = document.createElement('script');
                newScript.setAttribute('data-spa-page-script', 'true');
                Array.from(oldScript.attributes).forEach(attr =>
                    newScript.setAttribute(attr.name, attr.value)
                );

                if (oldScript.src) {
                    await new Promise(resolve => {
                        newScript.onload = resolve;
                        newScript.onerror = resolve;
                        document.body.appendChild(newScript);
                    });
                } else {
                    newScript.textContent = oldScript.textContent;
                    document.body.appendChild(newScript);
                }
            }

            // Now dispatch alpine:init — this triggers the addEventListener callbacks
            // that were just registered by the page scripts (e.g. Alpine.data('asetEditForm', ...))
            document.dispatchEvent(new CustomEvent('alpine:init'));

            // Give Alpine a microtask to process the data registration
            await new Promise(resolve => queueMicrotask(resolve));

            // NOW init the Alpine tree — all Alpine.data() components are registered
            if (window.Alpine) {
                window.Alpine.initTree(mainEl);
            }

            // Dispatch DOMContentLoaded & custom event for other page scripts
            document.dispatchEvent(new Event('DOMContentLoaded'));
            document.dispatchEvent(new CustomEvent('amana:page-loaded', { detail: { url } }));
        } else {
            window.location.href = url;
            return;
        }

        // 6. Close mobile sidebar drawer if open
        if (window.Alpine && document.body._x_dataStack && document.body._x_dataStack[0]) {
            document.body._x_dataStack[0].sidebarOpen = false;
        }

        // 7. Update Browser History
        if (pushHistory) {
            history.pushState({ url }, '', url);
        }

        // 8. Reset Scroll Position of main container
        if (scrollContainer.scrollTop !== undefined) {
            scrollContainer.scrollTop = 0;
        }

        // 9. One Motion Enter Animation on main content
        if (mainEl && window.animate) {
            window.animate(
                mainEl,
                { opacity: [0, 1], y: [10, 0] },
                { duration: 0.22, easing: [0.16, 1, 0.3, 1] }
            );
        }
    } catch (error) {
        console.error('SPA Navigation error:', error);
        window.location.href = url;
    } finally {
        isNavigating = false;
    }
}

/**
 * Properly tear down Alpine component trees before replacing DOM content.
 * This prevents memory leaks and stale reactive bindings.
 */
function destroyAlpineTree(rootEl) {
    if (!window.Alpine) return;
    // Walk all elements that Alpine has initialized
    const walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_ELEMENT);
    let node = walker.currentNode;
    while (node) {
        if (node._x_dataStack) {
            window.Alpine.destroyTree(node);
            break; // destroyTree is recursive
        }
        node = walker.nextNode();
    }
}
