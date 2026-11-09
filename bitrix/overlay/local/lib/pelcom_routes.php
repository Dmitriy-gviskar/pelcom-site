<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
function pelcomManifest(): array {static $m; return $m ??= json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/migrations/catalog.json'),true);}
function pelcomPagePaths(): array {return json_decode(COption::GetOptionString('main','pelcom_page_paths','{}'),true) ?: [];}
function pelcomCategoryByBlock(int $id): ?array {
    if (!\Bitrix\Main\Loader::includeModule('iblock')) return null;
    $block=CIBlock::GetByID($id)->Fetch();
    foreach(pelcomManifest()['categories'] as $c) if($block && $block['IBLOCK_TYPE_ID']==='pelcom' && $c['block_code']===$block['CODE']) return $c;
    return null;
}
function pelcomValidateProduct(&$f): bool {
    global $APPLICATION;
    $old=isset($f['ID']) ? CIBlockElement::GetList([],['ID'=>(int)$f['ID']],false,false,['ID','IBLOCK_ID','CODE'])->Fetch() : false;
    $id=(int)($f['IBLOCK_ID'] ?? ($old['IBLOCK_ID'] ?? 0));$c=pelcomCategoryByBlock($id);
    if(!$c) return true;
    $code=(string)($f['CODE'] ?? ($old['CODE'] ?? ''));
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$code)) { $APPLICATION->ThrowException('Адрес товара: заполните символьный код латинскими строчными буквами, цифрами и дефисами.'); return false; }
    $other=CIBlockElement::GetList([],['IBLOCK_ID'=>$id,'=CODE'=>$code,'!ID'=>(int)($f['ID'] ?? 0)],false,['nTopCount'=>1],['ID'])->Fetch();
    $new=$c['url'].$code.'/';$redirects=json_decode(COption::GetOptionString('main','pelcom_product_redirects','{}'),true) ?: [];
    if($other || (isset($redirects[$new]) && (int)$redirects[$new] !== (int)($f['ID'] ?? 0))) { $APPLICATION->ThrowException('Этот адрес уже занят другим товаром или его прежним адресом. Выберите другой символьный код.'); return false; }
    if($old && $old['CODE']!==$code) $GLOBALS['pelcom_old_urls'][(int)$f['ID']]=$c['url'].$old['CODE'].'/';
    return true;
}
function pelcomRememberProductUrl(&$f): void {
    if(empty($f['RESULT']) || empty($GLOBALS['pelcom_old_urls'][(int)$f['ID']])) return;
    $r=json_decode(COption::GetOptionString('main','pelcom_product_redirects','{}'),true) ?: [];
    $r[$GLOBALS['pelcom_old_urls'][(int)$f['ID']]]=(int)$f['ID'];
    COption::SetOptionString('main','pelcom_product_redirects',json_encode($r));unset($GLOBALS['pelcom_old_urls'][(int)$f['ID']]);
}
function pelcomRedirectPage(): void {
    $path=parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH);$target=null;
    foreach(pelcomPagePaths() as $source=>$current) {
        $route=null;foreach(pelcomManifest()['routes'] as $r) if($r['source']===$source) $route=$r;
        if($route && $path===$route['url'] && $path!==$current) $target=$current;
    }
    foreach((json_decode(COption::GetOptionString('main','pelcom_page_redirects','{}'),true) ?: []) as $old=>$source) if($path===$old && isset(pelcomPagePaths()[$source])) $target=pelcomPagePaths()[$source];
    $redirects=json_decode(COption::GetOptionString('main','pelcom_product_redirects','{}'),true) ?: [];
    if(isset($redirects[$path]) && \Bitrix\Main\Loader::includeModule('iblock')) {
        $e=CIBlockElement::GetList([],['ID'=>(int)$redirects[$path],'ACTIVE'=>'Y'],false,false,['ID','IBLOCK_ID','CODE'])->Fetch();
        if($e && ($c=pelcomCategoryByBlock((int)$e['IBLOCK_ID']))) $target=$c['url'].$e['CODE'].'/';
    }
    if($target && $target!==$path) { $q=parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);header('Location: '.$target.($q?'?'.$q:''),true,301);exit; }
}
function pelcomRewriteLinks(&$html): void {
    if(str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH),'/bitrix/')) return;
    $html=preg_replace_callback('~<a\b[^>]*>.*?</a>~is',static function($m) {
        $label=trim(html_entity_decode(strip_tags($m[0]),ENT_QUOTES,'UTF-8'));
        if(!in_array($label,['Связаться','Запросить КП','Обсудить задачу','Оставить заявку'],true)) return $m[0];
        $anchor=preg_replace('~(href\s*=\s*)(["\'])/\2~i','$1$2/#contact$2',$m[0]);
        return preg_replace('~(href\s*=\s*)/(?=[\s>])~i','$1/#contact',$anchor);
    },$html);
    $html=preg_replace('~(<script\b[^>]*src=["\']?)/lead\.js(?:\?[^"\' >]*)?~i','$1/lead.js?v=20261109-3',$html);
    $config=require $_SERVER['DOCUMENT_ROOT'].'/local/config/pelcom.php';$map=[];
    foreach(pelcomPagePaths() as $source=>$current) foreach(pelcomManifest()['routes'] as $r) if($r['source']===$source) $map[$r['url']]=$current;
    if(\Bitrix\Main\Loader::includeModule('iblock')) foreach((json_decode(COption::GetOptionString('main','pelcom_product_redirects','{}'),true) ?: []) as $old=>$id) {
        $e=CIBlockElement::GetList([],['ID'=>(int)$id,'ACTIVE'=>'Y'],false,false,['IBLOCK_ID','CODE'])->Fetch();
        if($e && ($c=pelcomCategoryByBlock((int)$e['IBLOCK_ID']))) $map[$old]=$c['url'].$e['CODE'].'/';
    }
    $html=preg_replace_callback('~(href\s*=\s*)(["\']?)([^\s"\'<>]+)\2~i',static function($m) use($map,$config) {
        $url=$m[3];$prefix='';if(str_starts_with($url,$config['origin'].'/')) {$prefix=$config['origin'];$url=substr($url,strlen($prefix));}
        foreach($map as $old=>$current) if($url===$old || str_starts_with($url,$old.'#') || str_starts_with($url,$old.'?')) return $m[1].$m[2].$prefix.$current.substr($url,strlen($old)).$m[2];
        return $m[0];
    },$html);
    foreach($map as $old=>$current) $html=str_replace($config['origin'].$old.'"',$config['origin'].$current.'"',$html);
}
