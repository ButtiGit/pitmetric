import './public-site';

function readCookie(name) {
    return document.cookie
        .split('; ')
        .find(row => row.startsWith(`${name}=`))
        ?.split('=')[1];
}

function writeCookie(name, value, days = 365) {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${name}=${value}; Max-Age=${days * 86400}; Path=/; SameSite=Lax${secure}`;
}

function initializePublicShell() {
    if (!document.body?.classList.contains('pm-public-site')) return;

    const languageModal = document.querySelector('[data-language-modal]');
    const openLanguage = () => {
        languageModal?.classList.remove('hidden');
        languageModal?.classList.add('flex');
    };
    const closeLanguage = () => {
        languageModal?.classList.add('hidden');
        languageModal?.classList.remove('flex');
    };

    if (!readCookie('pitmetric_locale')) openLanguage();

    document.querySelectorAll('[data-language-open]').forEach(button => {
        button.addEventListener('click', openLanguage);
    });
    document.querySelectorAll('[data-language-close]').forEach(button => {
        button.addEventListener('click', closeLanguage);
    });
    languageModal?.addEventListener('click', event => {
        if (event.target === languageModal && readCookie('pitmetric_locale')) closeLanguage();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && readCookie('pitmetric_locale')) closeLanguage();
    });

    const cookieBanner = document.querySelector('[data-cookie-banner]');
    const showCookieBanner = () => cookieBanner?.classList.remove('hidden');
    if (!readCookie('pitmetric_cookie_consent')) showCookieBanner();

    document.querySelectorAll('[data-cookie-choice]').forEach(button => {
        button.addEventListener('click', () => {
            writeCookie('pitmetric_cookie_consent', button.dataset.cookieChoice);
            cookieBanner?.classList.add('hidden');
        });
    });
    document.querySelectorAll('[data-cookie-settings]').forEach(button => {
        button.addEventListener('click', showCookieBanner);
    });

    const partnerNotice = document.querySelector('[data-partner-notice]');
    if (partnerNotice && sessionStorage.getItem('pitmetric_partner_notice_hidden') === '1') {
        partnerNotice.remove();
    }

    document.querySelectorAll('[data-partner-notice-close]').forEach(button => {
        button.addEventListener('click', () => {
            sessionStorage.setItem('pitmetric_partner_notice_hidden', '1');
            button.closest('[data-partner-notice]')?.remove();
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePublicShell, { once: true });
} else {
    initializePublicShell();
}
