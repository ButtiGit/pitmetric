const visibleSymbolReplacements = new Map([
    ['\u{1F1EE}\u{1F1F9}', ''],
    ['\u{1F1EC}\u{1F1E7}', ''],
    ['\u{1F3C1}', 'GRID'],
    ['\u2713', 'OK'],
    ['\u2190', ''],
    ['\u2191', ''],
    ['\u2192', ''],
    ['\u2197', ''],
]);

const visibleEmojiPattern = /[\u{2190}-\u{21FF}\u{2300}-\u{23FF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{1F000}-\u{1FAFF}\u{FE0F}]/gu;
const ignoredTags = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT']);

const normalizeText = (value) => {
    let normalized = value;

    visibleSymbolReplacements.forEach((replacement, symbol) => {
        normalized = normalized.split(symbol).join(replacement);
    });

    return normalized
        .replace(visibleEmojiPattern, '')
        .replace(/[ \t]{2,}/g, ' ')
        .replace(/\s+([,.;:!?])/g, '$1');
};

const normalizeNode = (node) => {
    if (node.nodeType === Node.TEXT_NODE) {
        const parentTag = node.parentElement?.tagName;

        if (!parentTag || ignoredTags.has(parentTag)) {
            return;
        }

        const normalized = normalizeText(node.nodeValue ?? '');

        if (normalized !== node.nodeValue) {
            node.nodeValue = normalized;
        }

        return;
    }

    if (!(node instanceof Element) || ignoredTags.has(node.tagName)) {
        return;
    }

    const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
    const textNodes = [];

    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    textNodes.forEach(normalizeNode);
};

const initVisibleSymbolCleanup = () => {
    if (!document.body) {
        return;
    }

    normalizeNode(document.body);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach(normalizeNode);

            if (mutation.type === 'characterData') {
                normalizeNode(mutation.target);
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        characterData: true,
        subtree: true,
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVisibleSymbolCleanup, { once: true });
} else {
    initVisibleSymbolCleanup();
}
