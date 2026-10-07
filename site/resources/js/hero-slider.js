// Slider hero banner (Beranda)

const setupHeroSlider = () => {
    const SLIDE_TRANSITION_MS = 500;
    const AUTOPLAY_DELAY_MS = 5000;
    const slider = document.querySelector('[data-hero-slider]');

    if (!slider) return;

    const track = slider.querySelector('[data-hero-track]');
    const originalSlides = Array.from(slider.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(slider.querySelectorAll('[data-hero-dot]'));

    if (!track || originalSlides.length <= 1 || dots.length !== originalSlides.length) return;

    // Infinite slide
    const firstClone = originalSlides[0].cloneNode(true);
    const lastClone = originalSlides[originalSlides.length - 1].cloneNode(true);

    firstClone.setAttribute('data-hero-clone', 'true');
    lastClone.setAttribute('data-hero-clone', 'true');

    track.prepend(lastClone);
    track.append(firstClone);

    const totalSlides = originalSlides.length;

    let activeIndex = 0;
    let currentIndex = 1;
    let autoplayId = null;
    let isDragging = false;
    let hasDragged = false;
    let isAnimating = false;
    let startX = 0;
    let currentTranslate = 0;
    let previousTranslate = 0;
    let sliderWidth = 0;
    let resizeTimeoutId = null;

    slider.style.setProperty('--hero-slider-transition-duration', `${SLIDE_TRANSITION_MS}ms`);
    slider.style.setProperty('--hero-slider-autoplay-duration', `${AUTOPLAY_DELAY_MS}ms`);

    const getTranslateForIndex = (index) => -(index * sliderWidth);

    const restartActiveDotProgress = () => {
        const activeDot = dots[activeIndex];

        if (!activeDot) return;

        dots.forEach((dot, index) => {
            if (index !== activeIndex) {
                dot.classList.remove('is-active');
            }
        });

        activeDot.classList.remove('is-active');
        void activeDot.offsetWidth;
        activeDot.classList.add('is-active');
    };

    const updateDots = () => {
        dots.forEach((dot, index) => {
            const isActive = index === activeIndex;
            dot.classList.toggle('is-current', isActive);
            dot.setAttribute('aria-current', String(isActive));
        });
    };

    const syncDimensions = () => {
        sliderWidth = slider.offsetWidth || 1;
    };

    const setTrackPosition = (translateX, withTransition = false) => {
        track.classList.toggle('transition-none', !withTransition);
        track.style.transform = `translate3d(${translateX}px, 0, 0)`;
    };

    const jumpToIndex = (index) => {
        currentIndex = index;
        currentTranslate = getTranslateForIndex(currentIndex);
        previousTranslate = currentTranslate;
        setTrackPosition(currentTranslate, false);
    };

    // Pindah ke slide: geser, klik dot
    const renderTrackIndex = (nextTrackIndex, { animate = true } = {}) => {
        currentIndex = nextTrackIndex;

        if (currentIndex === 0) {
            activeIndex = totalSlides - 1;
        } else if (currentIndex === totalSlides + 1) {
            activeIndex = 0;
        } else {
            activeIndex = currentIndex - 1;
        }

        const nextTranslate = getTranslateForIndex(currentIndex);
        const shouldAnimate = animate && nextTranslate !== currentTranslate;

        currentTranslate = nextTranslate;
        previousTranslate = currentTranslate;
        isAnimating = shouldAnimate;
        setTrackPosition(currentTranslate, shouldAnimate);
        updateDots();

        if (!shouldAnimate) {
            startAutoplay();
        }
    };

    const stopAutoplay = () => {
        if (autoplayId) {
            window.clearTimeout(autoplayId);
            autoplayId = null;
        }

        slider.classList.add('is-autoplay-paused');
    };

    // Autoplay
    const startAutoplay = () => {
        stopAutoplay();
        slider.classList.remove('is-autoplay-paused');
        restartActiveDotProgress();
        autoplayId = window.setTimeout(() => {
            if (!isDragging && !isAnimating) {
                renderTrackIndex(currentIndex + 1);
            }
        }, AUTOPLAY_DELAY_MS);
    };

    const resetDragState = () => {
        isDragging = false;
        hasDragged = false;
        startX = 0;
        currentTranslate = previousTranslate;
        track.classList.remove('transition-none');
    };

    const normalizeAfterResize = () => {
        isAnimating = false;
        resetDragState();
        syncDimensions();

        if (currentIndex <= 0) {
            jumpToIndex(totalSlides);
        } else if (currentIndex >= totalSlides + 1) {
            jumpToIndex(1);
        } else {
            jumpToIndex(currentIndex);
        }

        updateDots();
        startAutoplay();
    };

    const handleDragEnd = (pointerId) => {
        if (!isDragging) return;

        if (pointerId !== undefined && slider.hasPointerCapture(pointerId)) {
            slider.releasePointerCapture(pointerId);
        }

        if (!hasDragged) {
            resetDragState();
            return;
        }

        const movedBy = currentTranslate - previousTranslate;
        const threshold = Math.min(60, sliderWidth * 0.1);

        if (Math.abs(movedBy) > threshold) {
            renderTrackIndex(currentIndex + (movedBy < 0 ? 1 : -1));
        } else {
            renderTrackIndex(currentIndex);
        }

        resetDragState();
    };

    dots.forEach((dot, index) => {
        dot.addEventListener('pointerdown', (event) => {
            event.stopPropagation();
        });

        dot.addEventListener('click', () => {
            if (isDragging) return;
            stopAutoplay();
            renderTrackIndex(index + 1);
        });
    });

    slider.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || isAnimating || event.target.closest('[data-hero-dot], a, button')) return;

        syncDimensions();
        isDragging = true;
        hasDragged = false;
        startX = event.clientX;
        previousTranslate = getTranslateForIndex(currentIndex);
        currentTranslate = previousTranslate;
        slider.setPointerCapture(event.pointerId);
    });

    slider.addEventListener('pointermove', (event) => {
        if (!isDragging) return;

        const deltaX = event.clientX - startX;
        const dragThreshold = 6;

        if (!hasDragged) {
            if (Math.abs(deltaX) < dragThreshold) return;

            hasDragged = true;
            track.classList.add('transition-none');
            stopAutoplay();
        }

        const edgeLimit = sliderWidth * 0.35;
        currentTranslate = previousTranslate + Math.max(-edgeLimit, Math.min(edgeLimit, deltaX));
        setTrackPosition(currentTranslate, false);
    });

    slider.addEventListener('pointerup', (event) => {
        handleDragEnd(event.pointerId);
    });

    slider.addEventListener('pointercancel', (event) => {
        handleDragEnd(event.pointerId);
    });

    slider.addEventListener('lostpointercapture', () => {
        handleDragEnd();
    });

    slider.addEventListener('dragstart', (event) => {
        event.preventDefault();
    });

    // Animasi selesai > slide kloning
    track.addEventListener('transitionend', (event) => {
        if (event.target !== track || event.propertyName !== 'transform' || !isAnimating) return;

        isAnimating = false;

        if (currentIndex === 0) {
            activeIndex = totalSlides - 1;
            jumpToIndex(totalSlides);
            updateDots();
            startAutoplay();
            return;
        }

        if (currentIndex === totalSlides + 1) {
            activeIndex = 0;
            jumpToIndex(1);
            updateDots();
            startAutoplay();
            return;
        }

        startAutoplay();
    });

    window.addEventListener('resize', () => {
        stopAutoplay();

        if (resizeTimeoutId) {
            window.clearTimeout(resizeTimeoutId);
        }

        resizeTimeoutId = window.setTimeout(() => {
            normalizeAfterResize();
            resizeTimeoutId = null;
        }, 120);
    });

    syncDimensions();
    jumpToIndex(1);
    updateDots();
    startAutoplay();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupHeroSlider);
} else {
    setupHeroSlider();
}
