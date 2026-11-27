<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?php if (($arParams['WRAP'] ?? 'Y') !== 'N'): ?><div class="models-grid"><?php endif; ?>
<?php foreach ($arResult['ITEMS'] as $item):
    $p = $item['PROPERTIES'];
    $image = $p['CARD_PHOTO']['SRC'] ?: $p['CARD_IMAGE']['VALUE'];
    $name = htmlspecialcharsbx($item['~NAME']);
?>
<a class="model-card fi <?= $p['FEATURED']['VALUE'] === 'Y' ? 'featured' : '' ?>" href="<?=htmlspecialcharsbx($item['URL'])?>">
    <?php if ($image): ?>
    <div class="model-photo">
        <img class="model-img" src="<?=htmlspecialcharsbx($image)?>" alt="<?=$name?>" loading="lazy">
    </div>
    <?php else: ?>
    <div class="model-img" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="interior-card-copy">
        <div class="model-name"><?=$name?></div>
        <?php if ((float)$p['PRICE_FROM']['VALUE'] > 0): ?>
        <div class="model-price">от <strong><?=number_format((float)$p['PRICE_FROM']['VALUE'],0,',',' ')?> ₽</strong></div>
        <?php endif; ?>
        <span class="interior-more">Характеристики <span aria-hidden="true">→</span></span>
    </div>
</a>
<?php endforeach; ?>
<?php if (($arParams['WRAP'] ?? 'Y') !== 'N'): ?></div><?php endif; ?>
