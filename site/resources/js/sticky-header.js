// Sticky Header

const headers = document.querySelectorAll('#header, #single-header');

if (headers.length) {
    const SCROLL_THRESHOLD = 10;
    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    // Status sticky
    const updateHeaders = () => {
        const scrolled = window.scrollY > SCROLL_THRESHOLD;

        headers.forEach((header) => {
            const stickyDesktop = header.dataset.stickyDesktop === 'true';
            const stickyMobile = header.dataset.stickyMobile === 'true';
            const stickyEnabled = desktopQuery.matches ? stickyDesktop : stickyMobile;

            header.classList.toggle('is-sticky', scrolled && stickyEnabled);
        });
    };

    window.addEventListener('scroll', updateHeaders, { passive: true });
    desktopQuery.addEventListener('change', updateHeaders);
    updateHeaders();
}
