<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?php if (($arParams['WRAP'] ?? 'Y') !== 'N'): ?><div class="models-grid"><?php endif; ?>
<?php foreach ($arResult['ITEMS'] as $item):
$p=$item['PROPERTIES']; $specs=$p['CARD_LINES']['~VALUE'] ?? [];
$image=$p['CARD_PHOTO']['SRC'] ?: $p['CARD_IMAGE']['VALUE']; ?>
<a class="model-card fi <?= $p['FEATURED']['VALUE']==='Y' ? 'featured' : '' ?>" href="<?=htmlspecialcharsbx($item['URL'])?>">
<?php if ($image): ?><img class="model-img" src="<?=htmlspecialcharsbx($image)?>" alt="<?=htmlspecialcharsbx($item['~NAME'])?>" loading="lazy">
<?php else: ?><div class="model-img" style="background:#1a1a1a;display:flex;align-items:center;justify-content:center;height:180px"><?=htmlspecialcharsbx($item['~NAME'])?></div><?php endif; ?>
<div class="model-name"><?=htmlspecialcharsbx($item['~NAME'])?></div>
<div class="model-price">от <strong><?=number_format((float)$p['PRICE_FROM']['VALUE'],0,',',' ')?> ₽</strong></div>
<ul class="model-specs"><?php foreach ($specs as $spec): ?><li><?=htmlspecialcharsbx($spec)?></li><?php endforeach; ?></ul>
</a><?php endforeach; ?><?php if (($arParams['WRAP'] ?? 'Y') !== 'N'): ?></div><?php endif; ?>
