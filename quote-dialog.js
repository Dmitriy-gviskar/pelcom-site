(() => {
  const dialog = document.querySelector('[data-quote-dialog]');
  if (!dialog) return;

  const form = dialog.querySelector('form[data-lead]');
  const submit = form.querySelector('[type="submit"]');
  const success = document.getElementById(form.dataset.ok);
  const message = form.elements.message;
  let suggestedMessage;

  function open(title) {
    if (dialog.open) return;
    document.querySelector('dialog[open]')?.close();
    if (!success.hidden) {
      form.reset();
      form.style.removeProperty('display');
      delete form.dataset.pending;
      success.style.removeProperty('display');
      success.hidden = true;
      submit.disabled = false;
      submit.textContent = submit.dataset.originalLabel || 'Отправить заявку';
    }
    if (title && (!message.value.trim() || message.value === suggestedMessage)) {
      message.value = title;
      suggestedMessage = title;
    }
    dialog.showModal();
  }

  document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const trigger = event.target.closest('[data-dialog="project"], a[href]');
    if (!trigger || trigger.target === '_blank') return;
    const url = trigger.href && new URL(trigger.href, location.href);
    const legacyQuoteLink = url?.origin === location.origin &&
      (url.pathname === '/' || url.pathname.endsWith('/pelcom-landing.html')) && url.hash === '#contact';
    if (!legacyQuoteLink && trigger.dataset.dialog !== 'project') return;
    event.preventDefault();
    open(trigger.querySelector('.branded-name')?.textContent.trim());
  });

  function openFromHash() {
    if (location.hash === '#contact') open();
  }
  window.addEventListener('hashchange', openFromHash);
  openFromHash();

  dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => {
    if (location.hash === '#contact') history.replaceState(history.state, '', location.pathname + location.search);
  });
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const {left, right, top, bottom} = dialog.getBoundingClientRect();
    if (event.clientX < left || event.clientX > right || event.clientY < top || event.clientY > bottom) dialog.close();
  });
})();
