<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
if(!$USER->IsAdmin()) {http_response_code(403);die('Требуется администратор');}
require_once $_SERVER['DOCUMENT_ROOT'].'/local/lib/pelcom_routes.php';
$APPLICATION->SetTitle('Pelcom: адреса страниц');$error='';$message='';$paths=pelcomPagePaths();$routes=pelcomManifest()['routes'];
try {if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!check_bitrix_sessid()) throw new RuntimeException('Обновите страницу: сессия истекла.');
    $source=(string)($_POST['source'] ?? '');$new=trim((string)($_POST['path'] ?? ''));$selected=null;
    foreach($routes as $r) if($r['source']===$source && $r['kind']==='page' && !in_array($r['url'],['/','/404/'])) $selected=$r;
    if(!$selected) throw new RuntimeException('Эту страницу нельзя перемещать.');
    if(!preg_match('~^/(?:[a-z0-9]+(?:-[a-z0-9]+)*/)+$~D',$new) || preg_match('~^/(?:bitrix|local|upload|catalog|img|specs)(?:/|$)~',$new)) throw new RuntimeException('Введите адрес вида /novaya-stranica/. Только строчная латиница, цифры и дефисы. Служебные адреса и /catalog/ зарезервированы.');
    foreach($routes as $r) if($r['source']!==$source && (($paths[$r['source']] ?? $r['url'])===$new || $r['url']===$new)) throw new RuntimeException('Адрес занят другой страницей.');
    $history=json_decode(COption::GetOptionString('main','pelcom_page_redirects','{}'),true) ?: [];
    if(isset($history[$new]) && $history[$new]!==$source) throw new RuntimeException('Адрес занят прежним адресом другой страницы.');
    $old=$paths[$source] ?? $selected['url'];
    if($new!==$old && $new!==$selected['url'] && file_exists($_SERVER['DOCUMENT_ROOT'].$new)) throw new RuntimeException('Адрес занят файлом или папкой.');
    $history[$old]=$source;$paths[$source]=$new;
    COption::SetOptionString('main','pelcom_page_redirects',json_encode($history));COption::SetOptionString('main','pelcom_page_paths',json_encode($paths));$message='Сохранено. Старый адрес перенаправляет на новый.';
}} catch(Throwable $e) {$error=$e->getMessage();}
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php'; ?>
<h2>Адреса текстовых страниц</h2><p>Адреса товаров меняются в карточке товара, поле «Символьный код». Категории каталога и главная имеют постоянные адреса.</p>
<?php if($error): ?><p role="alert" style="color:#b42318"><?=htmlspecialcharsbx($error)?></p><?php endif; ?>
<?php if($message): ?><p role="status"><?=htmlspecialcharsbx($message)?></p><?php endif; ?>
<?php foreach($routes as $r): if($r['kind']!=='page' || in_array($r['url'],['/','/404/'])) continue; ?>
<form method="post" style="background:white;padding:16px;margin:12px 0"><?=bitrix_sessid_post()?>
<strong><?=htmlspecialcharsbx($r['title'])?></strong><input type="hidden" name="source" value="<?=htmlspecialcharsbx($r['source'])?>">
<p><label>Адрес <input name="path" value="<?=htmlspecialcharsbx($paths[$r['source']] ?? $r['url'])?>" size="45" required></label> <button>Сохранить</button></p>
</form><?php endforeach; ?>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
