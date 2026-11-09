<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if (!\Bitrix\Main\Loader::includeModule('iblock')) { ShowError('Модуль инфоблоков не установлен'); return; }
$manifest=json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/migrations/catalog.json'),true);
$category=null;
foreach ($manifest['categories'] as $c) if ($c['code']===($arParams['CATEGORY'] ?? 'kiosk')) $category=$c;
if (!$category) { CHTTP::SetStatus('404 Not Found');http_response_code(404); ShowError('Категория не найдена'); return; }
$arResult['CATEGORY']=$category;
$block=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$category['block_code'],'ACTIVE'=>'Y'])->Fetch();
if (!$block) { ShowError('Каталог не импортирован'); return; }
$filter=['IBLOCK_ID'=>$block['ID'],'ACTIVE'=>'Y'];
if (!empty($arParams['CODE'])) $filter['=CODE']=$arParams['CODE'];
$items=CIBlockElement::GetList(['SORT'=>'ASC','ID'=>'ASC'],$filter,false,false,['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT']);
$arResult['ITEMS']=[];
while ($ob=$items->GetNextElement()) {
    $item=$ob->GetFields();$item['PROPERTIES']=$ob->GetProperties();
    foreach (['CARD_PHOTO','DETAIL_PHOTO'] as $photo) $item['PROPERTIES'][$photo]['SRC']= !empty($item['PROPERTIES'][$photo]['VALUE']) ? CFile::GetPath($item['PROPERTIES'][$photo]['VALUE']) : '';
    $item['URL']=$category['url'].rawurlencode($item['CODE']).'/';
    $arResult['ITEMS'][]=$item;
}
if (!empty($arParams['CODE'])) {
    if (!$arResult['ITEMS']) { CHTTP::SetStatus('404 Not Found');http_response_code(404); ShowError('Модель не найдена'); return; }
    $title=$APPLICATION->GetTitle();
    foreach ($manifest['models'] as $source) {
        if ($source['category']===$category['code'] && $source['code']===$arParams['CODE'] && str_starts_with($title,$source['name'])) {
            $APPLICATION->SetTitle($arResult['ITEMS'][0]['~NAME'].substr($title,strlen($source['name'])));
            break;
        }
    }
    $APPLICATION->SetPageProperty('description',$arResult['ITEMS'][0]['~PREVIEW_TEXT']);
}
$this->IncludeComponentTemplate();
