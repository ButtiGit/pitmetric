const initPublicSite = () => {
    const body = document.body;

    if (!body?.classList.contains('pm-public-site')) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const finePointer = window.matchMedia('(pointer: fine)').matches;

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

    if (finePointer && !reduceMotion) {
        let pointerFrame = null;
        let pointerX = window.innerWidth / 2;
        let pointerY = window.innerHeight / 2;

        window.addEventListener('pointermove', (event) => {
            pointerX = event.clientX;
            pointerY = event.clientY;

            if (pointerFrame !== null) {
                return;
            }

            pointerFrame = window.requestAnimationFrame(() => {
                body.style.setProperty('--pm-pointer-x', `${pointerX}px`);
                body.style.setProperty('--pm-pointer-y', `${pointerY}px`);
                pointerFrame = null;
            });
        }, { passive: true });

        document.querySelectorAll('.pm-race-card').forEach((card) => {
            card.addEventListener('pointermove', (event) => {
                const rect = card.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width;
                const y = (event.clientY - rect.top) / rect.height;
                const rotateY = (x - 0.5) * 3.2;
                const rotateX = (0.5 - y) * 2.6;

                card.style.setProperty('--pm-card-x', `${(x * 100).toFixed(1)}%`);
                card.style.setProperty('--pm-card-y', `${(y * 100).toFixed(1)}%`);
                card.style.setProperty('--pm-card-rx', `${rotateX.toFixed(2)}deg`);
                card.style.setProperty('--pm-card-ry', `${rotateY.toFixed(2)}deg`);
            }, { passive: true });

            card.addEventListener('pointerleave', () => {
                card.style.setProperty('--pm-card-rx', '0deg');
                card.style.setProperty('--pm-card-ry', '0deg');
                card.style.setProperty('--pm-card-x', '50%');
                card.style.setProperty('--pm-card-y', '50%');
            });
        });
    }

    const cursorCar = document.querySelector('[data-pm-cursor-car]');

    if (cursorCar && finePointer && !reduceMotion) {
        let targetX = -120;
        let targetY = -120;
        let currentX = -120;
        let currentY = -120;
        let previousTargetX = targetX;
        let previousTargetY = targetY;
        let angle = 0;
        let targetAngle = 0;
        let speed = 0;
        let lastMoveAt = 0;
        let active = false;

        const normalizeAngleDelta = (from, to) => {
            let delta = (to - from) % 360;

            if (delta > 180) delta -= 360;
            if (delta < -180) delta += 360;

            return delta;
        };

        window.addEventListener('pointermove', (event) => {
            const dx = event.clientX - previousTargetX;
            const dy = event.clientY - previousTargetY;
            const distance = Math.hypot(dx, dy);

            previousTargetX = event.clientX;
            previousTargetY = event.clientY;
            targetX = event.clientX + 22;
            targetY = event.clientY + 18;
            lastMoveAt = performance.now();

            if (distance > 1.5) {
                targetAngle = Math.atan2(dy, dx) * (180 / Math.PI);
            }

            speed = Math.min(distance / 34, 1);

            if (!active) {
                active = true;
                currentX = targetX;
                currentY = targetY;
                cursorCar.dataset.active = 'true';
            }
        }, { passive: true });

        window.addEventListener('blur', () => {
            cursorCar.dataset.active = 'false';
        });

        document.addEventListener('mouseleave', () => {
            cursorCar.dataset.active = 'false';
        });

        document.addEventListener('mouseenter', () => {
            if (active) cursorCar.dataset.active = 'true';
        });

        const animateCar = (time) => {
            currentX += (targetX - currentX) * 0.145;
            currentY += (targetY - currentY) * 0.145;
            angle += normalizeAngleDelta(angle, targetAngle) * 0.18;
            speed += (0 - speed) * 0.055;

            cursorCar.style.setProperty('--pm-car-x', `${currentX.toFixed(2)}px`);
            cursorCar.style.setProperty('--pm-car-y', `${currentY.toFixed(2)}px`);
            cursorCar.style.setProperty('--pm-car-rotation', `${angle.toFixed(2)}deg`);
            cursorCar.style.setProperty('--pm-car-speed', Math.max(speed, 0).toFixed(3));

            if (active && time - lastMoveAt > 1700) {
                cursorCar.dataset.active = 'false';
            } else if (active && time - lastMoveAt <= 1700) {
                cursorCar.dataset.active = 'true';
            }

            window.requestAnimationFrame(animateCar);
        };

        window.requestAnimationFrame(animateCar);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPublicSite, { once: true });
} else {
    initPublicSite();
}
