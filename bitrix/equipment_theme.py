"""Build the equipment and Solutions presentation while keeping editable Bitrix content."""
import re
from urllib.parse import urlsplit

from bs4 import BeautifulSoup

CATALOG_ASSETS = '/img/home-redesign/'
PRODUCT_IMAGES = {
    'kiosk', 'tables', 'panels', 'payment', 'outdoor', 'lectern',
    'kids', 'nav', 'queue', 'pylon', 'holo', 'accessible',
}
INDUSTRY_IMAGES = {
    'gos': CATALOG_ASSETS + 'industry-government.webp',
    'med': CATALOG_ASSETS + 'industry-clinic.webp',
    'edu': CATALOG_ASSETS + 'industry-education.webp',
    'retail': CATALOG_ASSETS + 'industry-retail.webp',
    'mfg': CATALOG_ASSETS + 'industry-manufacturing.webp',
    'bank': CATALOG_ASSETS + 'industry-business.webp',
    'museum': '/img/solution-illustrations/museum.webp',
    'transport': '/img/solution-illustrations/transport.webp',
}
SUMMARY_INCLUDE = "<?php require_once $_SERVER['DOCUMENT_ROOT'].'/local/lib/pelcom_catalog_summary.php'; ?>\n"


def image(fragment, source, cls, alt=''):
    return fragment.new_tag(
        'img', src=source, alt=alt,
        attrs={'class': cls, 'loading': 'lazy', 'decoding': 'async'},
    )


def card_copy(fragment, *items):
    copy = fragment.new_tag('div', attrs={'class': 'interior-card-copy'})
    for item in items:
        if item is not None:
            copy.append(item.extract())
    return copy


def arrow_link(fragment, cls, label):
    link = fragment.new_tag('span', attrs={'class': cls})
    link.append(label + ' ')
    arrow = fragment.new_tag('span', attrs={'aria-hidden': 'true'})
    arrow.string = '→'
    link.append(arrow)
    return link


def change_section(body, cls, edit):
    pattern = re.compile(r'<section\b[^>]*class="[^"]*\b' + re.escape(cls) + r'\b[^"]*"[^>]*>.*?</section>', re.S)
    body, count = pattern.subn(lambda match: edit(BeautifulSoup(match[0], 'html.parser')), body, count=1)
    if count != 1:
        raise ValueError(f'Missing {cls} section')
    return body


def catalog_overview(body):
    def add_cover(fragment):
        heading = fragment.select_one('h1')
        if br := heading.br:
            br.replace_with(' ')
        cover = fragment.new_tag('div', attrs={'class': 'interior-cover'})
        cover.append(image(
            fragment, CATALOG_ASSETS + 'kiosk-lobby.webp', 'interior-cover-photo',
            'Интерактивный киоск PELCOM в интерьере',
        ))
        fragment.select_one('.hero-inner').append(cover)
        return str(fragment)

    def update_tiles(fragment):
        replacements = {}
        for tile in fragment.select('.cat-card'):
            slug = urlsplit(tile['href']).path.strip('/').split('/')[-1]
            if not re.fullmatch(r'[a-z0-9-]+', slug):
                raise ValueError(f'Unexpected equipment category URL: {tile["href"]}')
            if slug in PRODUCT_IMAGES:
                tile.img['src'] = f'{CATALOG_ASSETS}product-{slug}-480.webp'
            price_marker, count_marker = f'PELCOMCATPRICE-{slug}-END', f'PELCOMCATCOUNT-{slug}-END'
            copy = card_copy(fragment, tile.h3)
            copy.append(price_marker)
            copy.append(arrow_link(fragment, 'cat-arrow', count_marker))
            tile.select_one('.cat-card__body').replace_with(copy)
            replacements[price_marker] = f"<?=pelcomCatalogPriceMarkup($pelcomCategoryStats['{slug}']['price'] ?? null)?>"
            replacements[count_marker] = f"<?=pelcomCatalogCountLabel($pelcomCategoryStats['{slug}']['count'] ?? 0)?>"
        html = str(fragment)
        for marker, php in replacements.items():
            html = html.replace(marker, php)
        return html

    body = change_section(body, 'hero', add_cover)
    return (SUMMARY_INCLUDE + '<?php $pelcomCategoryStats = pelcomCatalogSummary(); ?>\n'
            + change_section(body, 'cat-section', update_tiles))


def catalog_category(body, code):
    if code == 'kiosk':
        body = re.sub(r'(<h1\b[^>]*>[^<]+)<br\s*/?>([^<]*</h1>)', r'\1 \2', body, count=1)
    pattern = re.compile(r'''<div\b[^>]*\bclass=(?:"hero-price"|'hero-price'|hero-price(?=[\s>]))[^>]*>([^<]*)<strong>[^<]+</strong>(.*?)</div>''', re.S)
    def dynamic_price(match):
        tail = match[2]
        return (
            f"<?php $pelcomCategoryPrice = pelcomCatalogSummary('{code}')['{code}']['price'] ?? null; "
            'if ($pelcomCategoryPrice !== null): ?>'
            f'<div class="hero-price">{match[1]}<strong><?=pelcomCatalogFormatPrice($pelcomCategoryPrice)?></strong>{tail}</div>'
            '<?php endif; ?>'
        )
    body, count = pattern.subn(dynamic_price, body, count=1)
    return SUMMARY_INCLUDE + body if count else body


def solutions_overview(body):
    def update_industries(fragment):
        grid = fragment.select_one('.sol-grid')
        tasks = fragment.new_tag('div', attrs={'class': 'sol-grid'})
        for card in list(grid.select('.sol-card')):
            path = urlsplit(card['href']).path
            icon = card.select_one('.sol-icon')
            if path.startswith('/solutions/'):
                slug = path.strip('/').split('/')[-1]
                icon.replace_with(image(fragment, INDUSTRY_IMAGES[slug], 'interior-solution-photo'))
            else:
                art = fragment.new_tag('div', attrs={'class': 'interior-solution-icon', 'aria-hidden': 'true'})
                icon.replace_with(art)
                art.append(icon)
            if tags := card.select_one('.sol-tags'):
                tags.decompose()
            card.append(card_copy(
                fragment, card.select_one('.sol-name'), card.select_one('.sol-desc'),
                card.select_one('.solution-more'),
            ))
            if path.startswith('/catalog/'):
                tasks.append(card.extract())
        heading = fragment.new_tag('h3', attrs={'class': 'interior-subheading'})
        heading.string = 'Решения по задачам'
        grid.insert_after(heading)
        heading.insert_after(tasks)
        return str(fragment)

    return change_section(body, 'sol-section', update_industries)


def solution_illustration(body, slug):
    def add_image(fragment):
        grid = fragment.select_one('.solution-hero__grid')
        if not grid.select_one('.solution-hero__media'):
            media = fragment.new_tag('div', attrs={'aria-hidden': 'true', 'class': 'solution-hero__media'})
            media.append(image(fragment, INDUSTRY_IMAGES[slug], 'interior-solution-photo'))
            grid.append(media)
        return str(fragment)

    return change_section(body, 'solution-hero', add_image)
