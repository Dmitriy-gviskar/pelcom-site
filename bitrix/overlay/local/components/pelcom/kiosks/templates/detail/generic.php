<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$image=$p['DETAIL_PHOTO']['SRC'] ?: $p['CARD_PHOTO']['SRC'];
?>
<main style="max-width:1200px;margin:0 auto;padding:150px 24px 80px">
<nav aria-label="Хлебные крошки"><a href="/">Главная</a> / <a href="<?=htmlspecialcharsbx($arResult['CATEGORY']['url'])?>"><?=htmlspecialcharsbx($arResult['CATEGORY']['name'])?></a> / <?=htmlspecialcharsbx($item['~NAME'])?></nav>
<h1 style="font-size:clamp(30px,6vw,56px);overflow-wrap:anywhere;margin:32px 0"><?=htmlspecialcharsbx($item['~NAME'])?></h1>
<?php if ($image): ?><img style="max-width:100%;max-height:520px;object-fit:contain" src="<?=htmlspecialcharsbx($image)?>" alt="<?=htmlspecialcharsbx($item['~NAME'])?>"><?php endif; ?>
<p style="white-space:pre-line;margin:24px 0"><?=htmlspecialcharsbx($item['~PREVIEW_TEXT'])?></p>
<p style="font-size:28px"><?php if ($p['PRICE_FROM']['VALUE']!==''): ?>от <?=number_format((float)$p['PRICE_FROM']['VALUE'],0,',',' ')?> ₽<?php else: ?>Цена по запросу<?php endif; ?></p>
<?php if($specs): ?><h2>Характеристики</h2><table class="st" style="width:100%;margin:24px 0"><?php foreach($specs as $row): ?><tr><td><?=htmlspecialcharsbx($row[0])?></td><td><?=htmlspecialcharsbx($row[1])?></td></tr><?php endforeach; ?></table><?php endif; ?>
<?php if(!empty($item['~DETAIL_TEXT'])): ?><div style="white-space:pre-line;margin:32px 0"><?=htmlspecialcharsbx($item['~DETAIL_TEXT'])?></div><?php endif; ?>
<a class="btn" href="/contacts/">Связаться с нами</a>
</main>
