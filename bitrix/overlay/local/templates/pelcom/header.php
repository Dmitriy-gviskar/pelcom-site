<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $pelcomPage, $pelcomModel;
$page = preg_match('/^[a-z0-9-]+$/D', $pelcomPage ?? '') ? $pelcomPage : 'pelcom-landing';
$siteConfig=require $_SERVER['DOCUMENT_ROOT'].'/local/config/pelcom.php';
$origin=$siteConfig['origin'];
$canonical=$origin.(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/');
?><!DOCTYPE html><html lang="ru"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php $APPLICATION->ShowTitle(); ?></title><meta name="robots" content="<?= $siteConfig['indexing'] ? 'index,follow' : 'noindex,nofollow' ?>">
<meta property="og:type" content="website"><meta property="og:site_name" content="Pelcom"><meta property="og:locale" content="ru_RU">
<meta property="og:title" content="<?php $APPLICATION->AddBufferContent(static function() use ($APPLICATION) { return htmlspecialcharsbx($APPLICATION->GetTitle()); }); ?>">
<meta property="og:description" content="<?php $APPLICATION->AddBufferContent(static function() use ($APPLICATION) { return htmlspecialcharsbx($APPLICATION->GetPageProperty('description')); }); ?>">
<meta property="og:url" content="<?=htmlspecialcharsbx($canonical)?>"><meta property="og:image" content="<?=htmlspecialcharsbx($origin)?>/og-image.png">
<meta name="twitter:card" content="summary_large_image"><link rel="canonical" href="<?=htmlspecialcharsbx($canonical)?>">
<?php $APPLICATION->ShowHead(); require __DIR__.'/parts/'.$page.'-head.php'; ?>
<style>.hd,#hdr{top:var(--pelcom-admin-offset,0px)!important}.hero>.crumbs,.pg-hero>.crumbs{top:86px}.crumbs-i{flex-wrap:wrap}@media(max-width:640px){.hero>.crumbs,.pg-hero>.crumbs{top:80px}}@media(max-width:640px){section[style*="padding:100px"]{padding:48px 20px!important}.s-title{font-size:clamp(22px,6vw,32px);overflow-wrap:anywhere}.pelcom-model .gl{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.pelcom-model .st td{padding:12px 8px;overflow-wrap:anywhere}}@media(max-width:640px){.pg-h{font-size:clamp(30px,10vw,48px)}}</style>
</head><body class="pelcom-page-<?=htmlspecialcharsbx($page)?> <?= !empty($pelcomModel) ? 'pelcom-model' : '' ?>"><div id="panel"><?php $APPLICATION->ShowPanel(); ?></div>
<script>(function(){var p=document.getElementById('panel');if(!p)return;function sync(){document.documentElement.style.setProperty('--pelcom-admin-offset',Math.max(0,(document.getElementById('bx-panel')||p).getBoundingClientRect().bottom)+'px')}sync();window.addEventListener('scroll',sync,{passive:true});if(window.ResizeObserver)new ResizeObserver(sync).observe(p)})();</script>
<?php require __DIR__.'/parts/'.$page.'-header.php'; ?>
