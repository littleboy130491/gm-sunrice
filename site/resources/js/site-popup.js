// Popup informasi website

document.addEventListener('DOMContentLoaded', function () {
    const popups = document.querySelectorAll('dialog.site-popup');
    if (!popups.length) return;

    popups.forEach(function (popup) {

        // Klik overlay > menutup
        popup.addEventListener('click', function (e) {
            const box = popup.querySelector('.site-popup-inner');
            if (box && !box.contains(e.target)) {
                popup.close();
            }
        });

        // Buka otomatis
        if (popup.dataset.autoOpen === 'true') {
            popup.showModal();
        }
    });
});
