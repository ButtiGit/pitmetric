const ONBOARDING_KEY = 'pitmetric.mobile.onboarding.v1';

type OnboardingSlide = {
  kicker: string;
  title: string;
  copy: string;
};

const slides: OnboardingSlide[] = [
  {
    kicker: 'PITMETRIC',
    title: 'Tutto ciò che serve in pista.',
    copy: 'Registra tempi, sessioni, note e setup in pochi tocchi, senza dover configurare tutto prima.',
  },
  {
    kicker: 'OFFLINE FIRST',
    title: 'Continua anche senza rete.',
    copy: 'I dati restano sul dispositivo. Il cloud è opzionale e puoi sincronizzare quando vuoi.',
  },
  {
    kicker: 'AL VOLO',
    title: 'Guarda, registra, riparti.',
    copy: 'Home e Timer mostrano subito i dati essenziali. In Altro trovi setup, manutenzione, galleria e account.',
  },
];

function hasSeenOnboarding(): boolean {
  try {
    return window.localStorage.getItem(ONBOARDING_KEY) === 'done';
  } catch {
    return false;
  }
}

function markOnboardingSeen(): void {
  try {
    window.localStorage.setItem(ONBOARDING_KEY, 'done');
  } catch {
    // The onboarding can still be dismissed when storage is unavailable.
  }
}

export function installFirstRunOnboarding(): void {
  if (typeof window === 'undefined' || typeof document === 'undefined' || hasSeenOnboarding()) return;

  let current = 0;
  const root = document.createElement('div');
  root.className = 'pm-onboarding';
  root.setAttribute('role', 'dialog');
  root.setAttribute('aria-modal', 'true');
  root.setAttribute('aria-label', 'Introduzione a PitMetric');

  root.innerHTML = `
    <div class="pm-onboarding-card">
      <div class="pm-onboarding-top">
        <div class="pm-onboarding-brand"><span class="pm-onboarding-mark">PM</span><strong>PitMetric</strong></div>
        <button class="pm-onboarding-skip" type="button">Salta</button>
      </div>
      <div class="pm-onboarding-body" aria-live="polite">
        <p class="pm-onboarding-kicker"></p>
        <h1 class="pm-onboarding-title"></h1>
        <p class="pm-onboarding-copy"></p>
      </div>
      <div class="pm-onboarding-footer">
        <div class="pm-onboarding-progress" aria-hidden="true"></div>
        <button class="pm-onboarding-next" type="button"></button>
      </div>
    </div>
  `;

  const kicker = root.querySelector<HTMLElement>('.pm-onboarding-kicker');
  const title = root.querySelector<HTMLElement>('.pm-onboarding-title');
  const copy = root.querySelector<HTMLElement>('.pm-onboarding-copy');
  const progress = root.querySelector<HTMLElement>('.pm-onboarding-progress');
  const next = root.querySelector<HTMLButtonElement>('.pm-onboarding-next');
  const skip = root.querySelector<HTMLButtonElement>('.pm-onboarding-skip');

  if (!kicker || !title || !copy || !progress || !next || !skip) return;

  const close = () => {
    markOnboardingSeen();
    root.classList.add('is-closing');
    window.setTimeout(() => root.remove(), 180);
  };

  const render = () => {
    const slide = slides[current];
    kicker.textContent = slide.kicker;
    title.textContent = slide.title;
    copy.textContent = slide.copy;
    next.textContent = current === slides.length - 1 ? 'Inizia' : 'Avanti';
    progress.innerHTML = slides
      .map((_, index) => `<span class="${index === current ? 'is-active' : ''}"></span>`)
      .join('');
  };

  next.addEventListener('click', () => {
    if (current >= slides.length - 1) {
      close();
      return;
    }

    current += 1;
    root.querySelector('.pm-onboarding-body')?.classList.add('is-changing');
    window.setTimeout(() => {
      render();
      root.querySelector('.pm-onboarding-body')?.classList.remove('is-changing');
    }, 110);
  });

  skip.addEventListener('click', close);
  root.addEventListener('keydown', event => {
    if (event.key === 'Escape') close();
  });

  render();
  document.body.appendChild(root);
  window.requestAnimationFrame(() => root.classList.add('is-visible'));
  window.setTimeout(() => next.focus(), 220);
}
