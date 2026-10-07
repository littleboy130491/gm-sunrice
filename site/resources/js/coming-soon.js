// Countdown halaman coming soon

const setupCountdown = () => {
    const el = document.getElementById('countdown');
    if (!el) return;

    const target = new Date(el.dataset.target).getTime();
    if (isNaN(target)) return;

    const units = ['days', 'hours', 'minutes', 'seconds'];
    const set = (u, v) => {
        const node = el.querySelector(`[data-unit="${u}"]`);
        if (node) node.textContent = String(v).padStart(2, '0');
    };

    // Hitung waktu (hari/jam/menit/detik)
    const tick = () => {
        const diff = target - Date.now();
        if (diff <= 0) {
            units.forEach((u) => set(u, 0));
            clearInterval(timer);
            // Muat ulang > mode sudah nonaktif
            window.location.reload();
            return;
        }
        set('days', Math.floor(diff / 86400000));
        set('hours', Math.floor((diff % 86400000) / 3600000));
        set('minutes', Math.floor((diff % 3600000) / 60000));
        set('seconds', Math.floor((diff % 60000) / 1000));
    };

    tick();
    const timer = setInterval(tick, 1000);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCountdown);
} else {
    setupCountdown();
}
