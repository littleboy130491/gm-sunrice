// Fitur floating WhatsApp

const root = document.getElementById('floating-whatsapp');

if (root) {
    const toggle = root.querySelector('.floating-whatsapp-toggle');
    const panel = root.querySelector('.floating-whatsapp-panel');

    if (toggle && panel) {
        const isOpen = () => root.classList.contains('is-open');

        const setOpen = (open) => {
            root.classList.toggle('is-open', open);
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', () => setOpen(!isOpen()));

        document.addEventListener('click', (event) => {
            if (isOpen() && !root.contains(event.target)) setOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) setOpen(false);
        });
    }
}
