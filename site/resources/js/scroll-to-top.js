// Fitur scroll top

const button = document.querySelector('#scroll-to-top');

if (button) {
    const SCROLL_THRESHOLD = 400;

    const updateVisibility = () => {
        button.classList.toggle('is-visible', window.scrollY > SCROLL_THRESHOLD);
    };

    button.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', updateVisibility, { passive: true });
    updateVisibility();
}
