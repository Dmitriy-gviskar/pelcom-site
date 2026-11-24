(() => {
  const header = document.querySelector('.png-header');
  const menu = header.querySelector('.png-menu');
  const nav = header.querySelector('nav');
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
  document.querySelectorAll('.png-group [data-dialog],.png-footer [data-detail]').forEach(trigger => {
    trigger.addEventListener('click', event => {
      const dialog = document.getElementById('dialog-' + (trigger.dataset.dialog || trigger.dataset.detail));
      if (!dialog) return;
      event.preventDefault();
      dialog.showModal();
    });
  });
  document.querySelectorAll('dialog.png-dialog').forEach(dialog => {
    dialog.querySelector('.png-dialog-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const {left,right,top,bottom} = dialog.getBoundingClientRect();
      if (event.clientX<left || event.clientX>right || event.clientY<top || event.clientY>bottom) dialog.close();
    });
  });
})();
