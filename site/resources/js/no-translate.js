// Nama perusahaan tidak diterjemahkan GTranslate

// Varian terpanjang lebih dulu
const BRAND_TERMS = [
    'PT\\.?\\s+Gaya\\s?Makmur\\s+FAW\\s+Motors',
    'PT\\.?\\s+Gaya\\s?Makmur\\s+Mobil',
    'Gaya\\s?Makmur\\s+FAW\\s+Motors',
    'Gaya\\s?Makmur\\s+Mobil',
    'Gaya\\s?Makmur',
    'gmmobil\\.com',
    'GM\\s?Mobil',
    'GMM',
];

const BRAND_SOURCE = `(${BRAND_TERMS.join('|')})`;
const BRAND_PATTERN = new RegExp(BRAND_SOURCE, 'gi');
const BRAND_TEST = new RegExp(BRAND_SOURCE, 'i');

const SKIP_TAGS = new Set([
    'SCRIPT',
    'STYLE',
    'NOSCRIPT',
    'TEXTAREA',
    'INPUT',
    'SELECT',
    'OPTION',
    'CODE',
    'PRE',
]);

const isProtected = (element) =>
    element.closest('.notranslate, [translate="no"]') !== null;

const shouldSkip = (element) => SKIP_TAGS.has(element.tagName) || isProtected(element);

const wrapMatches = (textNode) => {
    const text = textNode.nodeValue;
    const fragment = document.createDocumentFragment();
    let lastIndex = 0;

    for (const found of text.matchAll(BRAND_PATTERN)) {
        const match = found[0];
        const start = found.index;
        let end = start + match.length;

        // Spasi ikut masuk span > tidak dipangkas Google Translate
        let before = text.slice(lastIndex, start);
        const leadingSpace = /[ \t]$/.test(before);

        if (leadingSpace) {
            before = before.replace(/[ \t]+$/, '');
        }

        const trailingSpace = /[ \t]/.test(text.charAt(end));

        if (trailingSpace) {
            end += 1;
        }

        if (before) {
            fragment.appendChild(document.createTextNode(before));
        }

        const span = document.createElement('span');
        span.className = 'notranslate';
        span.setAttribute('translate', 'no');
        span.textContent = (leadingSpace ? ' ' : '') + match + (trailingSpace ? ' ' : '');
        fragment.appendChild(span);

        lastIndex = end;
    }

    if (lastIndex < text.length) {
        fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
    }

    textNode.parentNode.replaceChild(fragment, textNode);
};

const protectBrandNames = (root = document.body) => {
    if (!root) return;

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            const parent = node.parentElement;

            if (!parent || shouldSkip(parent)) {
                return NodeFilter.FILTER_REJECT;
            }

            return BRAND_TEST.test(node.nodeValue)
                ? NodeFilter.FILTER_ACCEPT
                : NodeFilter.FILTER_REJECT;
        },
    });

    const nodes = [];
    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }

    nodes.forEach(wrapMatches);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => protectBrandNames());
} else {
    protectBrandNames();
}
