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
<style>html.pelcom-cookie-accepted #cookieBar,html.pelcom-cookie-accepted .cookie{display:none!important}#cookieBar[hidden],.cookie[hidden]{display:none!important}.hd,#hdr{top:var(--pelcom-admin-offset,0px)!important}.hero>.crumbs,.pg-hero>.crumbs{top:86px}.crumbs-i{flex-wrap:wrap}@media(max-width:640px){.hero>.crumbs,.pg-hero>.crumbs{top:80px}}@media(max-width:640px){section[style*="padding:100px"]{padding:48px 20px!important}.s-title{font-size:clamp(22px,6vw,32px);overflow-wrap:anywhere}.pelcom-model .gl{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.pelcom-model .st td{padding:12px 8px;overflow-wrap:anywhere}}@media(max-width:640px){.pg-h{font-size:clamp(30px,10vw,48px)}}</style>
<script id="pelcom-cookie-consent">
(function(){
  var root=document.documentElement,key='pelcom_cookie';
  try{if(localStorage.getItem(key))root.classList.add('pelcom-cookie-accepted');}catch(e){}
  document.addEventListener('click',function(e){
    var target=e.target;
    if(!target || !target.closest || !target.closest('#cookieBar button,.cookie button'))return;
    try{localStorage.setItem(key,'1');}catch(err){}
    root.classList.add('pelcom-cookie-accepted');
    document.querySelectorAll('#cookieBar,.cookie').forEach(function(b){b.hidden=true;});
  },true);
})();
</script>
<style id="pelcom-gallery-layout">
.hero-inner:has(.hero-media){grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
.hero-inner:has(.hero-media)>*{min-width:0}
.hero-media,.hero-media .media-view,.hero-media .spin360{width:100%;min-width:0}
.hero-media img{display:block;width:100%;height:auto;max-width:100%}
.hero-media .media-view[hidden]{display:none!important}
.hero-media .media-tabs{display:flex;gap:8px;margin-bottom:12px}
.hero-media .mt{border:1px solid #49628a;background:#20273d;color:#fff;border-radius:20px;padding:9px 18px;font:inherit;cursor:pointer}
.hero-media .mt.active{background:#1670ed;border-color:#1670ed}
.hero-media .spin360{position:relative;overflow:hidden;border-radius:18px;background:#eeeeec;cursor:grab;touch-action:pan-y;user-select:none}
.hero-media .spin360:active{cursor:grabbing}
.hero-media .spin360-hint{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:#182139d9;color:#fff;border-radius:20px;padding:7px 16px;white-space:nowrap}
@media(max-width:900px){.hero-inner:has(.hero-media){grid-template-columns:minmax(0,1fr)}.hero:has(.hero-media){padding-top:210px!important}}
</style>
</head><body class="pelcom-page-<?=htmlspecialcharsbx($page)?> <?= !empty($pelcomModel) ? 'pelcom-model' : '' ?>"><div id="panel"><?php $APPLICATION->ShowPanel(); ?></div>
<script>(function(){var p=document.getElementById('panel');if(!p)return;function sync(){document.documentElement.style.setProperty('--pelcom-admin-offset',Math.max(0,(document.getElementById('bx-panel')||p).getBoundingClientRect().bottom)+'px')}sync();window.addEventListener('scroll',sync,{passive:true});if(window.ResizeObserver)new ResizeObserver(sync).observe(p)})();</script>
<?php require __DIR__.'/parts/'.$page.'-header.php'; ?>
