<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/lib/pelcom_routes.php';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);$manifest=pelcomManifest();
if(preg_match('~^/catalog/([a-z0-9-]+)/([a-z0-9-]+)/$~D',$path,$match)) {
    $category=null;foreach($manifest['categories'] as $c) if($c['code']===$match[1]) $category=$c;
    if($category && \Bitrix\Main\Loader::includeModule('iblock')) {
        $block=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$category['block_code'],'ACTIVE'=>'Y'])->Fetch();
        $item=$block ? CIBlockElement::GetList([],['IBLOCK_ID'=>$block['ID'],'=CODE'=>$match[2],'ACTIVE'=>'Y'],false,false,['ID','NAME','PREVIEW_TEXT','IBLOCK_ID'])->GetNextElement() : false;
        if($item) {
            $fields=$item->GetFields();$properties=$item->GetProperties();$source=basename($properties['SOURCE_FILE']['VALUE'] ?? '');
            $keys=array_column($manifest['models'],'source');
            if(!in_array($source,$keys,true)) {foreach($manifest['models'] as $m) if($m['category']===$category['code']) {$source=$m['source'];break;}}
            $pelcomPage=substr($source,0,-5);$pelcomModel=true;
            require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
            $title=$fields['~NAME'].' — '.$category['name'].' — Pelcom';
            foreach($manifest['routes'] as $r) if($r['source']===($properties['SOURCE_FILE']['VALUE'] ?? '')) $title=$r['title'];
            $APPLICATION->SetTitle($title);
            $APPLICATION->IncludeComponent('pelcom:kiosks','detail',['CATEGORY'=>$category['code'],'CODE'=>$match[2]]);
            require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';return;
        }
    }
}
foreach(pelcomPagePaths() as $source=>$current) if($path===$current) foreach($manifest['routes'] as $route) if($route['source']===$source && $route['kind']==='page') {
    require $_SERVER['DOCUMENT_ROOT'].($route['url']==='/' ? '/index.php' : $route['url'].'index.php');return;
}
if($path!=='/' && !str_ends_with($path,'/')) {
    $candidate=$path.'/';$valid=in_array($candidate,pelcomPagePaths(),true);
    if(preg_match('~^/catalog/([a-z0-9-]+)/([a-z0-9-]+)/$~D',$candidate,$m) && \Bitrix\Main\Loader::includeModule('iblock')) foreach($manifest['categories'] as $c) if($c['code']===$m[1]) {
        $b=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$c['block_code'],'ACTIVE'=>'Y'])->Fetch();
        $valid=$b && CIBlockElement::GetList([],['IBLOCK_ID'=>$b['ID'],'=CODE'=>$m[2],'ACTIVE'=>'Y'],false,false,['ID'])->Fetch();
    }
    if($valid) { $q=parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);header('Location: '.$candidate.($q?'?'.$q:''),true,301);exit; }
}
if(!defined('ERROR_404')) define('ERROR_404','Y');
CHTTP::SetStatus('404 Not Found');
http_response_code(404);require $_SERVER['DOCUMENT_ROOT'].'/404/index.php';
