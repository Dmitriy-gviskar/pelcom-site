<?php

function pelcomUsesInteriorTheme(string $page): bool
{
    return $page !== 'pelcom-landing' && $page !== 'solutions' && !str_starts_with($page, 'pelcom-solution-');
}

function pelcomInteriorBrand(): string
{
    return '<a class="solution-brand" href="/" aria-label="PELCOM — главная"><img src="/img/home-redesign/pelcom-logo.webp" width="585" height="138" alt="PELCOM"></a>';
}

function pelcomInteriorHeader(string $page, string $original): void
{
    $id = preg_match('/<header\b[^>]*\bid=["\'](hd|hdr)["\']/i', $original, $match) ? $match[1] : 'hdr';
    $phone = preg_match('~<a\b[^>]*href=["\']?(tel:\+?[0-9]+)["\']?[^>]*>(.*?)</a>~is', $original, $match) ? $match : [];
    $nav = [
        '/catalog/' => 'Оборудование', '/solutions/' => 'Решения', '/software/' => 'Программное обеспечение',
        '/#engineering' => 'Инжиниринг', '/#group' => 'Группа компаний', '/about/' => 'О компании',
        '/service/' => 'Сервис', '/contacts/' => 'Контакты',
    ];
    $active = match (true) {
        $page === 'software' || str_starts_with($page, 'pelcom-software-') => '/software/',
        $page === 'pelcom-about' => '/about/',
        $page === 'pelcom-service' => '/service/',
        $page === 'contacts' => '/contacts/',
        $page === 'products' || (str_starts_with($page, 'pelcom-') && $page !== 'pelcom-partners') => '/catalog/',
        default => null,
    };
    ?>
<header id="<?= $id ?>" class="solution-header">
  <div class="solution-header__inner">
    <?= pelcomInteriorBrand() ?>
    <nav class="solution-header__nav" id="pelcom-main-navigation" aria-label="Основная навигация">
      <?php foreach ($nav as $href => $label): ?>
      <a href="<?= htmlspecialcharsbx($href) ?>"<?= $active === $href ? ' aria-current="location"' : '' ?>><?= htmlspecialcharsbx($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="solution-header__right">
      <?php if ($phone): ?><a class="solution-phone" href="<?= htmlspecialcharsbx($phone[1]) ?>"><?= htmlspecialcharsbx(trim(strip_tags($phone[2]))) ?></a><?php endif; ?>
      <a class="solution-button" href="/#contact">Обсудить проект</a>
    </div>
    <button class="burger" type="button" aria-label="Меню" aria-controls="pelcom-main-navigation" aria-expanded="false" onclick="this.setAttribute('aria-expanded', String(this.closest('header').classList.toggle('open')))"><span></span><span></span><span></span></button>
  </div>
</header>
    <?php
}

function pelcomInteriorFooter(string $original): string
{
    $footer = preg_replace('~<footer(?=[\s>])~i', '<footer class="solution-footer interior-footer"', $original, 1);
    $brand = pelcomInteriorBrand();
    $footer = preg_replace_callback('~<a\b(?=[^>]*\bclass=["\']?(?:h-logo|logo)\b)[^>]*>.*?</a>~is', static fn () => $brand, $footer, 1, $replaced);
    if (!$replaced) {
        $footer = preg_replace_callback('~<div\s+class=["\']?(?:footer-col|ft-brand)["\']?\s*>~i', static fn ($m) => $m[0].$brand, $footer, 1);
    }
    return $footer;
}
