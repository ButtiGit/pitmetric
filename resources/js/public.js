import '../css/public.css';
import '../css/public-cover-mobile.css';
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
    let partnerNotice = document.querySelector('[data-partner-notice]');
    const mobileNoticeMedia = window.matchMedia('(max-width: 767px)');

    const syncMobileNotices = () => {
        if (!partnerNotice?.isConnected) return;

        if (!mobileNoticeMedia.matches) {
            partnerNotice.removeAttribute('data-mobile-notice-suppressed');
            partnerNotice.removeAttribute('aria-hidden');
            return;
        }

        const cookieIsVisible = Boolean(cookieBanner && !cookieBanner.classList.contains('hidden'));

        if (cookieIsVisible) {
            partnerNotice.setAttribute('data-mobile-notice-suppressed', 'true');
            partnerNotice.setAttribute('aria-hidden', 'true');
            return;
        }

        partnerNotice.removeAttribute('data-mobile-notice-suppressed');
        partnerNotice.removeAttribute('aria-hidden');
    };

    const showCookieBanner = () => {
        cookieBanner?.classList.remove('hidden');
        syncMobileNotices();
    };

    if (!readCookie('pitmetric_cookie_consent')) showCookieBanner();

    document.querySelectorAll('[data-cookie-choice]').forEach(button => {
        button.addEventListener('click', () => {
            writeCookie('pitmetric_cookie_consent', button.dataset.cookieChoice);
            cookieBanner?.classList.add('hidden');
            syncMobileNotices();
        });
    });
    document.querySelectorAll('[data-cookie-settings]').forEach(button => {
        button.addEventListener('click', showCookieBanner);
    });

    if (partnerNotice && sessionStorage.getItem('pitmetric_partner_notice_hidden') === '1') {
        partnerNotice.remove();
        partnerNotice = null;
    }

    document.querySelectorAll('[data-partner-notice-close]').forEach(button => {
        button.addEventListener('click', () => {
            sessionStorage.setItem('pitmetric_partner_notice_hidden', '1');
            button.closest('[data-partner-notice]')?.remove();
            partnerNotice = null;
        });
    });

    if (typeof mobileNoticeMedia.addEventListener === 'function') {
        mobileNoticeMedia.addEventListener('change', syncMobileNotices);
    } else {
        mobileNoticeMedia.addListener(syncMobileNotices);
    }

    syncMobileNotices();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePublicShell, { once: true });
} else {
    initializePublicShell();
}
