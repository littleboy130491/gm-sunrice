// Share link (Single Artikel)

// Salin teks > clipboard
const copyText = async (text) => {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = Object.assign(document.createElement('textarea'), {
        value: text,
    });
    textarea.style.cssText = 'position:fixed; top:-1000px; left:-1000px;';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
};

// Button share
const setupShareCopy = () => {
    const links = document.querySelectorAll('.share-link');
    if (!links.length) return;

    links.forEach((el) => {
        el.addEventListener('click', async (e) => {
            e.preventDefault();

            if (el.querySelector('.copied-label')) return;

            try {
                await copyText(window.location.href);
            } catch (err) {
                console.error('Gagal menyalin link:', err);
                return;
            }

            const label = Object.assign(document.createElement('span'), {
                className: 'copied-label',
                textContent: 'Link copied!',
            });

            label.style.cssText =
                'position:absolute; top:-40px; left:50%; transform:translateX(-72%); white-space:nowrap; padding:6px 10px; background:#000; color:#fff; border-radius:6px; font-size:12px; z-index:100;';

            el.style.position = 'relative';
            el.appendChild(label);

            setTimeout(() => label.remove(), 1500);
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupShareCopy);
} else {
    setupShareCopy();
}