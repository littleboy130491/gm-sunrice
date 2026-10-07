// Slider Fitur & benefit (Single produk)

import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

// Inisialisasi Swiper
const setupFeaturesSlider = () => {
    if (!document.querySelector('.features-swiper')) return;

    new Swiper('.features-swiper', {
        modules: [Navigation],
        slidesPerView: 1,
        spaceBetween: 20,
        watchOverflow: true,
        navigation: {
            nextEl: '.features-next',
            prevEl: '.features-prev',
        },
        breakpoints: {
            768: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 20,
            },
        },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupFeaturesSlider);
} else {
    setupFeaturesSlider();
}
