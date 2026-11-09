<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { http_response_code(403); exit; }
function pelcomChecked($result,$message) { if (!$result) throw new RuntimeException($message); return $result; }
function pelcomEnsureType(): void {
    if (!\Bitrix\Main\Loader::includeModule('iblock')) throw new RuntimeException('Не установлен модуль инфоблоков');
    $type=new CIBlockType;
    if (!CIBlockType::GetByID('pelcom')->Fetch()) pelcomChecked($type->Add(['ID'=>'pelcom','SECTIONS'=>'Y','IN_RSS'=>'N','SORT'=>100,'LANG'=>['ru'=>['NAME'=>'Пелком','SECTION_NAME'=>'Категории','ELEMENT_NAME'=>'Модели'],'en'=>['NAME'=>'Pelcom']]]),$type->LAST_ERROR);
}
function pelcomImportCategory(array $category,array $manifest,string $siteId='s1'): array {
    pelcomEnsureType(); $created=0; $preserved=0;
    $block=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$category['block_code']])->Fetch();
    $ib=new CIBlock;
    $id=$block ? (int)$block['ID'] : pelcomChecked($ib->Add(['ACTIVE'=>'Y','NAME'=>$category['name'],'CODE'=>$category['block_code'],'IBLOCK_TYPE_ID'=>'pelcom','SITE_ID'=>[$siteId],'GROUP_ID'=>[2=>'R'],'LIST_PAGE_URL'=>$category['url'],'DETAIL_PAGE_URL'=>$category['url'].'#ELEMENT_CODE#/']),$ib->LAST_ERROR);
    // Product CODE is validated by the Pelcom event handler.
    $defs=['PRICE_FROM'=>['Цена от, ₽','N','N'],'CARD_SPECS'=>['Исходные характеристики списка (JSON)','S','N'],'SPECS_JSON'=>['Исходные строки характеристик (JSON)','S','N'],'CARD_IMAGE'=>['Исходный путь фото списка','S','N'],'DETAIL_IMAGE'=>['Исходный путь фото модели','S','N'],'FEATURED'=>['Популярная модель (Y/N)','S','N'],'SOURCE_FILE'=>['Исходная страница','S','N'],'CARD_LINES'=>['Характеристики в списке','S','Y'],'CARD_PHOTO'=>['Фото в списке','F','N'],'DETAIL_PHOTO'=>['Фото модели','F','N']];
    foreach ($manifest['spec_codes'] as $label=>$code) $defs[$code]=[$label,'S','N'];
    foreach ($defs as $code=>$def) {
        if (!CIBlockProperty::GetList([],['IBLOCK_ID'=>$id,'CODE'=>$code])->Fetch()) {
            $prop=new CIBlockProperty;pelcomChecked($prop->Add(['IBLOCK_ID'=>$id,'ACTIVE'=>'Y','NAME'=>$def[0],'CODE'=>$code,'PROPERTY_TYPE'=>$def[1],'MULTIPLE'=>$def[2]]),$prop->LAST_ERROR);
        }
    }
    foreach ($manifest['models'] as $m) {
        if ($m['category']!==$category['code']) continue;
        $existing=CIBlockElement::GetList([],['IBLOCK_ID'=>$id,['LOGIC'=>'OR',['=CODE'=>$m['code']],['=PROPERTY_SOURCE_FILE'=>$m['source']]]],false,false,['ID'])->Fetch();
        if ($existing) { $preserved++;continue; }
        $fields=['IBLOCK_ID'=>$id,'ACTIVE'=>'Y','NAME'=>$m['name'],'CODE'=>$m['code'],'SORT'=>$m['sort'],'PREVIEW_TEXT'=>$m['description'],'PREVIEW_TEXT_TYPE'=>'text'];
        $values=['PRICE_FROM'=>$m['price'],'CARD_SPECS'=>json_encode($m['card_specs'],JSON_UNESCAPED_UNICODE),'SPECS_JSON'=>json_encode($m['specs'],JSON_UNESCAPED_UNICODE),'CARD_IMAGE'=>$m['card_image']?'/'.$m['card_image']:'','DETAIL_IMAGE'=>$m['detail_image']?'/'.$m['detail_image']:'','FEATURED'=>$m['featured']?'Y':'N','SOURCE_FILE'=>$m['source'],'CARD_LINES'=>$m['card_specs']];
        foreach ($m['specs'] as $row) $values[$manifest['spec_codes'][$row[0]]]=$row[1];
        foreach (['card_image'=>'CARD_PHOTO','detail_image'=>'DETAIL_PHOTO'] as $key=>$code) {
            if ($m[$key] && is_file($_SERVER['DOCUMENT_ROOT'].'/'.$m[$key])) $values[$code]=CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].'/'.$m[$key]);
        }
        $fields['PROPERTY_VALUES']=$values;
        $el=new CIBlockElement;$eid=pelcomChecked($el->Add($fields),$el->LAST_ERROR);$created++;
    }

    return ['created'=>$created,'preserved'=>$preserved,'count'=>(int)CIBlockElement::GetList([],['IBLOCK_ID'=>$id],true)];
}
function pelcomConfigureSite(string $siteId='s1'): void {
    $config=require $_SERVER['DOCUMENT_ROOT'].'/local/config/pelcom.php';
    $site=new CSite;
    pelcomChecked($site->Update($siteId,['NAME'=>'Пелком','SITE_NAME'=>'Pelcom','SERVER_NAME'=>parse_url($config['production_origin'],PHP_URL_HOST),'TEMPLATE'=>[['SORT'=>1,'CONDITION'=>'','TEMPLATE'=>'pelcom']]]),$site->LAST_ERROR);
}
