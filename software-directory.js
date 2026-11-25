(() => {
  const filters = document.querySelector('#filterBar');
  const grid = document.querySelector('#swGrid');
  const more = document.querySelector('#softwareMore');
  if (!filters || !grid || !more) return;

  const buttons = [...filters.querySelectorAll('.filter-btn')];
  const cards = [...grid.querySelectorAll('.sw-card')];
  const batchSize = 8;
  let limit = batchSize;
  let selected = 'all';

  function render() {
    let matches = 0;
    for (const card of cards) {
      const matchesFilter = selected === 'all' || selected === card.dataset.cat;
      if (matchesFilter) matches += 1;
      card.hidden = !matchesFilter || matches > limit;
    }
    more.hidden = matches <= limit;
  }

  for (const button of buttons) {
    button.addEventListener('click', () => {
      selected = button.dataset.filter;
      limit = batchSize;
      for (const filter of buttons) {
        const active = filter === button;
        filter.classList.toggle('active', active);
        filter.setAttribute('aria-pressed', String(active));
      }
      render();
    });
    button.setAttribute('aria-pressed', String(button.dataset.filter === selected));
  }
  more.addEventListener('click', () => {
    const previouslyHidden = cards.filter(card => card.hidden);
    limit += batchSize;
    render();
    if (more.hidden) {
      const firstNew = previouslyHidden.find(card => !card.hidden);
      if (firstNew) {
        firstNew.tabIndex = -1;
        firstNew.focus({preventScroll: true});
      }
    }
  });
  render();
})();
