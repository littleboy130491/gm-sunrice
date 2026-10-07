// Slider kategori produk halaman Beranda

import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

// Inisialisasi Swiper
const setupCategorySlider = () => {
    if (!document.querySelector('.category-swiper')) return;

    new Swiper('.category-swiper', {
        modules: [Navigation],
        slidesPerView: 2,
        spaceBetween: 10,
        loop: true,
        navigation: {
            nextEl: '.category-next',
            prevEl: '.category-prev',
        },
        breakpoints: {
            768: { slidesPerView: 3 },
            1024: { slidesPerView: 4 },
        },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCategorySlider);
} else {
    setupCategorySlider();
}