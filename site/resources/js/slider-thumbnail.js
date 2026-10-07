// Galeri slider (single Artikel & Produk)

const setupGallery = () => {
    const main = document.querySelector('.gallery-main');
    if (!main) return;

    const slides = Array.from(main.querySelectorAll('.gallery-slide'));
    const thumbs = Array.from(document.querySelectorAll('.gallery-thumb'));
    const track = document.querySelector('.gallery-thumbs-track');
    const prevBtn = document.querySelector('.gallery-prev');
    const nextBtn = document.querySelector('.gallery-next');

    if (slides.length === 0) return;

    let current = 0;

    // Slide tampil
    const show = (index) => {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== current);
        });

        thumbs.forEach((thumb, i) => {
            thumb.classList.toggle('opacity-100', i === current);
            thumb.classList.toggle('opacity-50', i !== current);
        });

        // Thumbnail aktif
        if (track && thumbs[current]) {
            track.scrollTo({
                left: thumbs[current].offsetLeft - track.offsetLeft,
                behavior: 'smooth',
            });
        }
    };

    thumbs.forEach((thumb, i) => {
        thumb.addEventListener('click', () => show(i));
    });

    if (prevBtn) prevBtn.addEventListener('click', () => show(current - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => show(current + 1));

    show(0);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupGallery);
} else {
    setupGallery();
}