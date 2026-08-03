# Деплой

Статический сайт — только HTML/CSS/JS, без сборки. Деплой = загрузить файлы на любой хостинг.

## Варианты хостинга
- **Свой VPS / nginx** (Beget, Timeweb, Selectel): залить файлы в корень сайта, `pelcom-landing.html` сделать индексом (переименовать в `index.html` или настроить `index pelcom-landing.html;` в nginx).
- **Cloudflare Pages** — бесплатно, работает с приватным репо. Подключить github-репо, build command пустой, output dir `/`.
- **Netlify / Vercel** — drag-and-drop папки или из репо.
- **GitHub Pages** — репозиторий сейчас приватный; Pages для приватных репо требует платный план. Либо сделать репо публичным, либо использовать Cloudflare Pages.

## Домен
`pelcom.ru` (используется в canonical, og:url, sitemap.xml, robots.txt). Настроить A/CNAME-запись на выбранный хостинг.

## Чек-лист перед запуском
- [ ] `pelcom-landing.html` → индекс сайта (index.html или конфиг хостинга)
- [ ] Заменить плейсхолдеры контактов на реальные:
  - телефоны: `+7 (495) 123-45-67`, `8 800 123-45-67`
  - email в форме/отзывах: `ivan@company.ru` (плейсхолдер)
- [ ] Проверить, что домен в og/canonical/sitemap совпадает с боевым (если не pelcom.ru — заменить во всех файлах + sitemap.xml + robots.txt)
- [ ] Загрузить вместе со страницами: `favicon.svg`, `favicon-32.png`, `apple-touch-icon.png`, `og-image.png`, `sitemap.xml`, `robots.txt`
- [ ] HTTPS (Let's Encrypt / Cloudflare)
- [ ] Отправить sitemap в Яндекс.Вебмастер и Google Search Console
