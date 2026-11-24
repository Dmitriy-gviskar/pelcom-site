(() => {
  const header = document.querySelector('.home-hd');
  const menu = header?.querySelector('.burger');
  const navigation = header?.querySelector('.hd-nav');

  function syncMenu() {
    menu?.setAttribute('aria-expanded', String(header.classList.contains('open')));
  }

  function closeMenu() {
    header?.classList.remove('open');
    syncMenu();
  }

  menu?.addEventListener('click', syncMenu);
  navigation?.addEventListener('click', event => {
    if (event.target.closest('a')) closeMenu();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && header?.classList.contains('open')) {
      closeMenu();
      menu?.focus();
    }
  });

  const leadDialog = document.getElementById('lpOverlay');
  if (leadDialog && typeof window.openLeadPopup === 'function') {
    let opener;
    leadDialog.querySelector('.lp-box')?.setAttribute('role', 'dialog');
    leadDialog.querySelector('.lp-box')?.setAttribute('aria-label', 'Запросить коммерческое предложение');
    document.querySelectorAll('[data-home-lead]').forEach(button => {
      button.setAttribute('aria-haspopup', 'dialog');
      button.addEventListener('click', () => {
        opener = button;
        leadDialog.querySelector('input[name="name"]')?.focus({preventScroll: true});
      });
    });
    new MutationObserver(() => {
      if (!leadDialog.classList.contains('open') && opener) {
        opener.focus({preventScroll: true});
        opener = undefined;
      }
    }).observe(leadDialog, {attributes: true, attributeFilter: ['class']});
  }

  const track = document.getElementById('home-products');
  const controls = document.querySelector('.home-product-controls');
  if (!track || !controls) return;

  const previous = controls.querySelector('[data-product-direction="-1"]');
  const next = controls.querySelector('[data-product-direction="1"]');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function syncControls() {
    const maxScroll = track.scrollWidth - track.clientWidth;
    controls.hidden = maxScroll <= 1;
    previous.disabled = track.scrollLeft <= 1;
    next.disabled = track.scrollLeft >= maxScroll - 1;
  }

  function scrollProducts(direction) {
    const cards = [...track.children];
    if (cards.length < 2) return;
    const cardStep = cards[1].offsetLeft - cards[0].offsetLeft;
    if (cardStep <= 0) return;
    const visibleCards = Math.max(1, Math.floor((track.clientWidth + 8) / cardStep));
    track.scrollBy({left: direction * visibleCards * cardStep, behavior: reduceMotion.matches ? 'instant' : 'smooth'});
  }

  previous.addEventListener('click', () => scrollProducts(-1));
  next.addEventListener('click', () => scrollProducts(1));
  track.addEventListener('scroll', syncControls, {passive: true});
  new ResizeObserver(syncControls).observe(track);
  syncControls();
})();
