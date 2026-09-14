const initPublicSite = () => {
    const body = document.body;

    if (!body?.classList.contains('pm-public-site')) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    body.classList.toggle('pm-motion-ready', !reduceMotion);

    const header = document.querySelector('[data-pm-public-header]');
    const progress = document.querySelector('[data-pm-scroll-progress]');
    const hero = document.querySelector('[data-pm-home-hero]');
    const heroImage = hero?.querySelector('[data-pm-hero-image]');

    let scrollFrame = null;

    const syncScrollState = () => {
        scrollFrame = null;
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const scrollable = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
        const ratio = Math.min(Math.max(scrollY / scrollable, 0), 1);

        header?.classList.toggle('pm-public-header--scrolled', scrollY > 16);
        progress?.style.setProperty('--pm-scroll-progress', ratio.toFixed(4));

        if (heroImage && !reduceMotion) {
            const heroHeight = Math.max(hero?.offsetHeight ?? 1, 1);
            const shift = Math.min(scrollY / heroHeight, 1) * 28;
            heroImage.style.setProperty('--pm-hero-shift', `${shift.toFixed(2)}px`);
        }
    };

    const requestScrollSync = () => {
        if (scrollFrame !== null) {
            return;
        }

        scrollFrame = window.requestAnimationFrame(syncScrollState);
    };

    window.addEventListener('scroll', requestScrollSync, { passive: true });
    window.addEventListener('resize', requestScrollSync, { passive: true });
    syncScrollState();

    if (!reduceMotion && 'IntersectionObserver' in window) {
        const revealTargets = [
            ...document.querySelectorAll('main > section:not([data-pm-home-hero]), main > article, main .pm-race-card, main .pm-media-frame'),
        ];

        revealTargets.forEach((element, index) => {
            element.dataset.pmReveal = '';
            element.style.setProperty('--pm-reveal-delay', `${Math.min(index % 4, 3) * 55}ms`);
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('pm-reveal--visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px',
        });

        revealTargets.forEach((element) => observer.observe(element));
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPublicSite, { once: true });
} else {
    initPublicSite();
}
