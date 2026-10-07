// Counter animation

const setupCounters = () => {
    const counters = document.querySelectorAll('.counter-number');
    if (!counters.length) return;

    const formatNumber = (num) => num.toLocaleString('id-ID');

    // Animasi dari 0
    const animate = (el) => {
        const target = parseInt(el.dataset.target, 10) || 0;
        const duration = 1500;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out
            el.textContent = formatNumber(Math.floor(eased * target));
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = formatNumber(target);
        };

        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.5 },
    );

    counters.forEach((el) => observer.observe(el));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCounters);
} else {
    setupCounters();
}