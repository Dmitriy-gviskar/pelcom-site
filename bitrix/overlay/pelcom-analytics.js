(function () {
  'use strict';

  const banner = document.getElementById('pelcom-analytics-choice');
  if (!banner) return;

  const preferenceKey = 'pelcom_analytics_v1';
  const counterId = Number(banner.dataset.counter);
  const map = document.querySelector('iframe[data-analytics-map]');
  let counterStarted = false;
  let settingsButton;

  function readPreference() {
    try {
      return localStorage.getItem(preferenceKey);
    } catch {
      return null;
    }
  }

  function startCounter() {
    if (!counterId || counterStarted) return;
    counterStarted = true;
    window.ym = window.ym || function () {
      (window.ym.a = window.ym.a || []).push(arguments);
    };
    window.ym.l = Date.now();
    window.ym(counterId, 'init', {
      ssr: true,
      webvisor: true,
      clickmap: true,
      ecommerce: 'dataLayer',
      referrer: document.referrer,
      url: location.href,
      accurateTrackBounce: true,
      trackLinks: true
    });

    const script = document.createElement('script');
    script.async = true;
    script.src = 'https://mc.yandex.ru/metrika/tag.js?id=' + counterId;
    document.head.appendChild(script);
  }

  function applyPreference(value) {
    banner.hidden = value === 'accepted' || value === 'declined';
    if (value === 'accepted') {
      startCounter();
      if (map && !map.hasAttribute('src')) {
        map.src = map.dataset.analyticsMap;
        map.hidden = false;
      }
    } else if (counterStarted) {
      // Discard the running counter on withdrawal.
      location.reload();
    }
  }

  banner.addEventListener('click', event => {
    const button = event.target.closest('button[data-analytics-choice]');
    if (!button) return;

    const value = button.dataset.analyticsChoice;
    try {
      localStorage.setItem(preferenceKey, value);
    } catch {
      if (counterStarted && value === 'declined') {
        banner.hidden = true;
        location.replace(location.href);
        return;
      }
    }
    applyPreference(value);
    if (settingsButton) settingsButton.focus();
  });

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-analytics-settings]');
    if (!button) return;

    settingsButton = button;
    banner.hidden = false;
    banner.querySelector('button[data-analytics-choice="declined"]').focus();
  });

  window.addEventListener('storage', event => {
    if (event.key === preferenceKey || event.key === null) {
      applyPreference(readPreference());
    }
  });

  applyPreference(readPreference());
})();
