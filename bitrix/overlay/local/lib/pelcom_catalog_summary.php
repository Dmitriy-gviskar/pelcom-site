<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
require_once __DIR__.'/pelcom_routes.php';

function pelcomCatalogSummary(?string $categoryCode = null): array
{
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $blocks = [];
    foreach (pelcomManifest()['categories'] as $category) {
        if ($categoryCode === null || $categoryCode === $category['code']) {
            $blocks[$category['block_code']] = $category['code'];
        }
    }
    if (!$blocks) {
        return [];
    }

    $filter = ['TYPE' => 'pelcom', 'ACTIVE' => 'Y'];
    if ($categoryCode !== null) {
        $filter['CODE'] = array_key_first($blocks);
    }
    $summary = [];
    $activeBlocks = CIBlock::GetList([], $filter);
    while ($block = $activeBlocks->Fetch()) {
        $code = $blocks[$block['CODE']] ?? null;
        if ($code === null) {
            continue;
        }

        $items = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $block['ID'], 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID', 'PROPERTY_PRICE_FROM']
        );
        $count = 0;
        $minimum = null;
        while ($item = $items->Fetch()) {
            ++$count;
            $price = (float)($item['PROPERTY_PRICE_FROM_VALUE'] ?? 0);
            if ($price > 0 && ($minimum === null || $price < $minimum)) {
                $minimum = $price;
            }
        }
        $summary[$code] = ['count' => $count, 'price' => $minimum];
    }
    return $summary;
}

function pelcomCatalogFormatPrice(float $price): string
{
    return number_format($price, 0, ',', ' ').' ₽';
}

function pelcomCatalogPriceMarkup(?float $price): string
{
    return $price === null ? '' : '<div class="cat-price">от <strong>'.pelcomCatalogFormatPrice($price).'</strong></div>';
}

function pelcomCatalogCountLabel(int $count): string
{
    if ($count === 0) {
        return 'Смотреть категорию';
    }

    $label = match (true) {
        $count % 100 >= 11 && $count % 100 <= 14 => 'моделей',
        $count % 10 === 1 => 'модель',
        $count % 10 >= 2 && $count % 10 <= 4 => 'модели',
        default => 'моделей',
    };
    return $count.' '.$label;
}
