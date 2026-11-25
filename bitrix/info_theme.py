"""Prepare the software, company, service and contacts layouts for Bitrix."""
import re
from urllib.parse import urlsplit

from bs4 import BeautifulSoup


SOFTWARE_VISUALS = {
    'nav': ('Карта объекта', ('Вход', 'Вы здесь', 'Ресепшен', 'Лифт', 'Переход', 'Выход')),
    'queue': ('Табло вызова', ('А 021 → Окно 1', 'Б 014 → Окно 3', 'В 007 → Окно 5')),
    'kiosk': ('Управление киоском', ('Устройства', 'Контент', 'Обновления', 'Интеграции')),
}


def fragment_update(body, pattern, update):
    def render(match):
        fragment = BeautifulSoup(match[0], 'html.parser')
        update(fragment)
        return str(fragment)

    updated, count = re.subn(pattern, render, body, count=1, flags=re.S)
    if count != 1:
        raise ValueError(f'Missing layout fragment: {pattern}')
    return updated


def tag(fragment, name, text=None, **attributes):
    element = fragment.new_tag(name, attrs={key.removesuffix('_'): value for key, value in attributes.items()})
    if text is not None:
        element.string = text
    return element


def software_visual(fragment, code, compact=False):
    heading, entries = SOFTWARE_VISUALS[code]
    graphic = tag(fragment, 'div', class_=f'info-screen info-screen--{code}')
    if compact:
        graphic['aria-hidden'] = 'true'
    graphic.append(tag(fragment, 'span', heading, class_='info-screen-title'))
    cells = tag(fragment, 'div', class_='info-screen-cells')
    for entry in entries:
        cells.append(tag(fragment, 'span', entry))
    graphic.append(cells)
    if not compact:
        graphic.append(tag(fragment, 'span', 'Схема интерфейса', class_='info-screen-note'))
    return graphic


def software_devices_markup(landing):
    graphic = BeautifulSoup(landing, 'html.parser').select_one('.software-devices')
    if graphic is None:
        raise ValueError('Missing the shared software device illustration')
    graphic['class'] = ['software-devices', 'info-software-devices']
    return str(graphic)


def info_theme_styles(styles, home_styles):
    device_rules = re.findall(r'^\.software-device[^{}]*\{[^{}]*\}', home_styles, re.M)
    if not device_rules:
        raise ValueError('Missing the shared software device styles')
    return styles.rstrip() + '\n\n' + '\n\n'.join(device_rules) + '\n'


def software_body(body, key, devices):
    def hero(fragment):
        title = fragment.select_one('h1')
        if title.br:
            title.br.replace_with(' ')
        if key == 'software':
            graphic = BeautifulSoup(devices, 'html.parser').select_one('.software-devices')
        else:
            graphic = software_visual(fragment, key.rsplit('-', 1)[-1])
        fragment.select_one('.hero-inner').append(graphic)

    body = fragment_update(body, r'''<section\b[^>]*class=(?:"hero"|'hero'|hero(?=[\s>]))[^>]*>.*?</section>''', hero)
    if key != 'software':
        return body

    def branded(fragment):
        for card in fragment.select('.branded-card'):
            code = urlsplit(card['href']).path.strip('/').split('/')[-1]
            card.select_one('.branded-icon').replace_with(software_visual(fragment, code, compact=True))

    body = fragment_update(body, r'<section\b[^>]*>(?:(?!<section\b).)*?class="branded-grid".*?</section>', branded)

    legacy_filter = r'// Фильтрация карточек\s*\(function\(\)\{.*?\}\)\(\);'
    body, count = re.subn(legacy_filter, '', body, count=1, flags=re.S)
    if not count:
        raise ValueError('Missing the software directory filter')
    body = fragment_update(
        body, r'<section\b[^>]*>(?:(?!<section\b).)*?id="swGrid".*?</section>',
        lambda fragment: fragment.select_one('#swGrid').insert_after(
            tag(fragment, 'button', 'Показать ещё', class_='info-software-more', type='button',
                id='softwareMore', hidden='', **{'aria-controls': 'swGrid'})
        ),
    )
    return body + '\n<script src="/software-directory.js?v=20261124" defer></script>\n'


def company_or_service_body(body, service):
    stats = 'sla' if service else 'nums'
    section_pattern = re.compile(fr'<section class="s {stats}-bg">.*?</section>', re.S)
    match = section_pattern.search(body)
    if match is None:
        raise ValueError(f'Missing {stats} cards')
    cards = BeautifulSoup(match[0], 'html.parser').select_one(f'.{stats}-grid')
    cards['class'] = [name for name in cards['class'] if name != 'fi']
    for label in cards.select('.sla-label, .num-label'):
        for br in label.select('br'):
            br.replace_with(' ')
    body = body[:match.start()] + body[match.end():]

    def hero(fragment):
        inner = fragment.select_one('.pg-hero-i')
        heading = inner.select_one('h1')
        if heading.br:
            heading.br.replace_with(' ')
        copy = tag(fragment, 'div', class_='info-hero-copy')
        for item in list(inner.contents):
            copy.append(item.extract())
        if service:
            status = copy.select_one('.live-badge')
            status.replace_with(tag(fragment, 'a', 'Позвонить в PELCOM', class_='info-button', href='tel:+74955483103'))
        else:
            desc = copy.select_one('.pg-desc')
            if desc.string:
                desc.string = desc.string.replace('12 лет создаём', 'Создаём')
        inner.append(copy)
        inner.append(cards)

    body = fragment_update(body, r'<section class="pg-hero">.*?</section>', hero)
    if service:
        return body

    body = re.sub(r'Год основания\.\s*Работаем и развиваемся уже 12 лет', 'Год основания', body, count=1)
    def production(fragment):
        photo = fragment.select_one('.prod-right img')
        photo['src'] = '/img/home-redesign/production-detail.webp'
        photo['srcset'] = '/img/home-redesign/production-detail-836.webp 836w, /img/home-redesign/production-detail.webp 1672w'
        photo['sizes'] = '(max-width: 760px) 92vw, 44vw'
        photo['alt'] = 'Сборка интерактивного оборудования'
        photo['width'], photo['height'] = '1672', '941'
        badge = fragment.select_one('.factory-badge')
        if badge:
            badge.decompose()

    return fragment_update(body, r'<section\b[^>]*>(?:(?!<section\b).)*?class="prod-grid\b.*?</section>', production)


def contacts_body(body):
    def contacts(fragment):
        main = fragment.main
        for card in main.select('.ccard'):
            label = card.select_one('.lbl').get_text(strip=True)
            if label not in ('Телефон', 'E-mail'):
                continue
            value = card.select_one('.val')
            text = value.get_text(strip=True)
            href = 'tel:+' + re.sub(r'\D', '', text) if label == 'Телефон' else 'mailto:' + text
            value.clear()
            value.append(tag(fragment, 'a', text, href=href))

        form = main.select_one('form[data-lead]')
        form.attrs.pop('style', None)
        for field in form.select('.ff'):
            label = field.label
            control = field.select_one('input, textarea')
            control['id'] = 'contact-' + control['name']
            label['for'] = control['id']
            label.attrs.pop('style', None)
            field.attrs.pop('style', None)

        columns = tag(fragment, 'div', class_='info-contact-layout')
        details = tag(fragment, 'section', class_='info-contact-form', **{'aria-labelledby': 'contact-form-title'})
        title = main.h2
        title['id'] = 'contact-form-title'
        title.attrs.pop('style', None)
        details.append(title.extract())
        details.append(form.extract())
        details.append(main.select_one('#contactOk').extract())
        columns.append(details)
        cmap = main.select_one('.cmap')
        if cmap.iframe and not cmap.select_one('.pelcom-map-link'):
            cmap.iframe['title'] = 'Расположение PELCOM на карте'
            maps_page = cmap.iframe['src'].replace('/map-widget/v1/', '/maps/', 1)
            cmap.insert(0, tag(fragment, 'a', 'Открыть карту', class_='info-map-link', href=maps_page))
        columns.append(cmap.extract())
        main.append(columns)

    return fragment_update(body, r'<main\b[^>]*class="page-wrap[^"]*"[^>]*>.*?</main>', contacts)


def apply_info_theme(body, key, devices=''):
    if key == 'software' or key.startswith('pelcom-software-'):
        return software_body(body, key, devices)
    if key in ('pelcom-about', 'pelcom-service'):
        return company_or_service_body(body, service=key == 'pelcom-service')
    if key == 'contacts':
        return contacts_body(body)
    return body
