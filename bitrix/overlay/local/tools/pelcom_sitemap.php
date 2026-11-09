<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/lib/pelcom_routes.php';
$config=require $_SERVER['DOCUMENT_ROOT'].'/local/config/pelcom.php';$urls=[];$paths=pelcomPagePaths();
foreach(pelcomManifest()['routes'] as $r) if($r['kind']!=='model' && $r['url']!=='/404/') $urls[]=$paths[$r['source']] ?? $r['url'];
if(\Bitrix\Main\Loader::includeModule('iblock')) foreach(pelcomManifest()['categories'] as $c) {
    $b=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$c['block_code'],'ACTIVE'=>'Y'])->Fetch();if(!$b) continue;
    $rows=CIBlockElement::GetList([],['IBLOCK_ID'=>$b['ID'],'ACTIVE'=>'Y'],false,false,['CODE']);while($r=$rows->Fetch()) $urls[]=$c['url'].rawurlencode($r['CODE']).'/';
}
$APPLICATION->RestartBuffer();header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach(array_unique($urls) as $url) echo '<url><loc>'.htmlspecialchars($config['origin'].$url,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>';
echo '</urlset>';exit;
