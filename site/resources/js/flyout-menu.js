// Flyout (Mobile dan Tablet)

const setupFlyoutMenu = () => {
    const toggleButton = document.getElementById('menu-toggle');
    const closeButton = document.getElementById('mobile-menu-close');
    const backdropButton = document.getElementById('mobile-menu-backdrop');
    const menu = document.getElementById('mobile-menu');
    const menuPanel = document.getElementById('mobile-menu-panel');
    let closeTimer = null;

    if (!toggleButton || !menu || !menuPanel || !backdropButton) {
        return;
    }

    // Buka/tutup menu
    const setMenuState = (isOpen) => {
        toggleButton.setAttribute('aria-expanded', String(isOpen));

        if (closeTimer) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }

        if (isOpen) {
            menu.classList.remove('pointer-events-none', 'invisible', 'opacity-0');
            menu.classList.add('pointer-events-auto', 'visible');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(() => {
                menu.classList.add('opacity-100');
                backdropButton.classList.remove('opacity-0');
                backdropButton.classList.add('opacity-100');
                menuPanel.classList.remove('-translate-x-full');
                menuPanel.classList.add('translate-x-0');
            });
            return;
        }

        menu.classList.remove('pointer-events-auto', 'visible', 'opacity-100');
        menu.classList.add('opacity-0');
        backdropButton.classList.remove('opacity-100');
        backdropButton.classList.add('opacity-0');
        menuPanel.classList.remove('translate-x-0');
        menuPanel.classList.add('-translate-x-full');
        document.body.classList.remove('overflow-hidden');

        closeTimer = window.setTimeout(() => {
            menu.classList.add('pointer-events-none', 'invisible');
            closeTimer = null;
        }, 300);
    };

    toggleButton.addEventListener('click', () => {
        const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
        setMenuState(!isExpanded);
    });

    closeButton?.addEventListener('click', () => setMenuState(false));
    backdropButton?.addEventListener('click', () => setMenuState(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuState(false);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupFlyoutMenu);
} else {
    setupFlyoutMenu();
}
