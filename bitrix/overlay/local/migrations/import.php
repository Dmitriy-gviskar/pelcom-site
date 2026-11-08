<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$_SERVER['DOCUMENT_ROOT']=realpath($argv[1] ?? dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='127.0.0.1:18080';
define('NO_KEEP_STATISTIC',true);define('NOT_CHECK_PERMISSIONS',true);define('BX_NO_ACCELERATOR_RESET',true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
if (!\Bitrix\Main\Loader::includeModule('iblock')) throw new RuntimeException('iblock missing');
function checked($result,$message) { if (!$result) throw new RuntimeException($message); return $result; }
$type=new CIBlockType;
if (!CIBlockType::GetByID('pelcom')->Fetch()) checked($type->Add(['ID'=>'pelcom','SECTIONS'=>'Y','IN_RSS'=>'N','SORT'=>100,'LANG'=>['ru'=>['NAME'=>'Пелком','SECTION_NAME'=>'Категории','ELEMENT_NAME'=>'Модели'],'en'=>['NAME'=>'Pelcom']]]),$type->LAST_ERROR);
$manifest=json_decode(file_get_contents(__DIR__.'/catalog.json'),true,512,JSON_THROW_ON_ERROR);
$created=0;$preserved=0;
foreach ($manifest['categories'] as $category) {
    $block=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$category['block_code']])->Fetch();
    $ib=new CIBlock;
    $id=$block ? (int)$block['ID'] : checked($ib->Add(['ACTIVE'=>'Y','NAME'=>$category['name'],'CODE'=>$category['block_code'],'IBLOCK_TYPE_ID'=>'pelcom','SITE_ID'=>['s1'],'GROUP_ID'=>[2=>'R'],'LIST_PAGE_URL'=>$category['url'],'DETAIL_PAGE_URL'=>$category['url'].'#ELEMENT_CODE#/']),$ib->LAST_ERROR);
    $defs=['PRICE_FROM'=>['Цена от, ₽','N','N'],'CARD_SPECS'=>['Исходные характеристики списка (JSON)','S','N'],'SPECS_JSON'=>['Исходные строки характеристик (JSON)','S','N'],'CARD_IMAGE'=>['Исходный путь фото списка','S','N'],'DETAIL_IMAGE'=>['Исходный путь фото модели','S','N'],'FEATURED'=>['Популярная модель (Y/N)','S','N'],'SOURCE_FILE'=>['Исходная страница','S','N'],'CARD_LINES'=>['Характеристики в списке','S','Y'],'CARD_PHOTO'=>['Фото в списке','F','N'],'DETAIL_PHOTO'=>['Фото модели','F','N']];
    foreach ($manifest['spec_codes'] as $label=>$code) $defs[$code]=[$label,'S','N'];
    foreach ($defs as $code=>$def) {
        if (!CIBlockProperty::GetList([],['IBLOCK_ID'=>$id,'CODE'=>$code])->Fetch()) {
            $prop=new CIBlockProperty;checked($prop->Add(['IBLOCK_ID'=>$id,'ACTIVE'=>'Y','NAME'=>$def[0],'CODE'=>$code,'PROPERTY_TYPE'=>$def[1],'MULTIPLE'=>$def[2]]),$prop->LAST_ERROR);
        }
    }
    foreach ($manifest['models'] as $m) {
        if ($m['category']!==$category['code']) continue;
        $existing=CIBlockElement::GetList([],['IBLOCK_ID'=>$id,'=CODE'=>$m['code']],false,false,['ID'])->Fetch();
        if ($existing) { $preserved++;continue; }
        $fields=['IBLOCK_ID'=>$id,'ACTIVE'=>'Y','NAME'=>$m['name'],'CODE'=>$m['code'],'SORT'=>$m['sort'],'PREVIEW_TEXT'=>$m['description'],'PREVIEW_TEXT_TYPE'=>'text'];
        $values=['PRICE_FROM'=>$m['price'],'CARD_SPECS'=>json_encode($m['card_specs'],JSON_UNESCAPED_UNICODE),'SPECS_JSON'=>json_encode($m['specs'],JSON_UNESCAPED_UNICODE),'CARD_IMAGE'=>$m['card_image']?'/'.$m['card_image']:'','DETAIL_IMAGE'=>$m['detail_image']?'/'.$m['detail_image']:'','FEATURED'=>$m['featured']?'Y':'N','SOURCE_FILE'=>$m['source'],'CARD_LINES'=>$m['card_specs']];
        foreach ($m['specs'] as $row) $values[$manifest['spec_codes'][$row[0]]]=$row[1];
        foreach (['card_image'=>'CARD_PHOTO','detail_image'=>'DETAIL_PHOTO'] as $key=>$code) {
            if ($m[$key] && is_file($_SERVER['DOCUMENT_ROOT'].'/'.$m[$key])) $values[$code]=CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].'/'.$m[$key]);
        }
        $fields['PROPERTY_VALUES']=$values;
        $el=new CIBlockElement;$eid=checked($el->Add($fields),$el->LAST_ERROR);$created++;
    }
    echo $category['code'].': '.CIBlockElement::GetList([],['IBLOCK_ID'=>$id],true)." models\n";
}
$siteConfig=require $_SERVER['DOCUMENT_ROOT'].'/local/config/pelcom.php';
$site=new CSite;checked($site->Update('s1',['NAME'=>'Пелком — локальная разработка','SITE_NAME'=>'Pelcom','SERVER_NAME'=>$siteConfig['indexing'] ? parse_url($siteConfig['origin'],PHP_URL_HOST) : '','TEMPLATE'=>[['SORT'=>1,'CONDITION'=>'','TEMPLATE'=>'pelcom']]]),$site->LAST_ERROR);
echo "Created $created; preserved $preserved; categories=".count($manifest['categories'])."\n";
