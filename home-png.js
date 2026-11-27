(() => {
  const menu = document.querySelector('.menu');
  const nav = document.querySelector('#site-nav');
  const track = document.querySelector('#equipment-track');
  const controls = [...document.querySelectorAll('[data-step]')];
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');

  function setMenu(open) {
    nav.classList.toggle('open', open);
    menu.setAttribute('aria-expanded', String(open));
  }
  menu.addEventListener('click', () => setMenu(!nav.classList.contains('open')));
  nav.addEventListener('click', event => { if (event.target.closest('a')) setMenu(false); });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && nav.classList.contains('open')) {
      setMenu(false);
      menu.focus();
    }
  });

  function syncRail() {
    controls[0].disabled = track.scrollLeft <= 2;
    controls[1].disabled = track.scrollWidth - track.clientWidth - track.scrollLeft <= 2;
  }
  controls.forEach(control => control.addEventListener('click', () => {
    const cardWidth = track.children[1].offsetLeft - track.children[0].offsetLeft;
    const step = cardWidth * Math.max(1, Math.floor((track.clientWidth + 2) / cardWidth));
    track.scrollBy({left: step * Number(control.dataset.step), behavior: reducedMotion.matches ? 'instant' : 'smooth'});
  }));
  track.addEventListener('scroll', syncRail, {passive:true});
  new ResizeObserver(syncRail).observe(track);
  syncRail();

  function openDialog(key) {
    const dialog = document.getElementById('dialog-' + key);
    if (!dialog || dialog.open) return;
    document.querySelector('dialog[open]')?.close();
    dialog.showModal();
  }

  for (const trigger of document.querySelectorAll('[data-dialog]:not([data-dialog=project])')) {
    trigger.addEventListener('click', event => {
      event.preventDefault();
      openDialog(trigger.dataset.dialog || trigger.dataset.detail);
    });
  }

  const cookie = document.getElementById('cookieBar');
  try { cookie.hidden = Boolean(localStorage.getItem('pelcom_cookie')); }
  catch { cookie.hidden = false; }
  cookie.querySelector('button').addEventListener('click', () => {
    try { localStorage.setItem('pelcom_cookie', '1'); }
    finally { cookie.hidden = true; }
  });

  document.querySelectorAll('dialog:not([data-quote-dialog])').forEach(dialog => {
    dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
  });
})();
