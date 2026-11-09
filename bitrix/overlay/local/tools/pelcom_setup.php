<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
if (!$USER->IsAdmin()) { http_response_code(403); die('Требуется вход администратором Битрикса.'); }
$APPLICATION->SetTitle('Установка сайта Pelcom');
require_once $_SERVER['DOCUMENT_ROOT'].'/local/migrations/catalog_service.php';
$manifest=json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/migrations/catalog.json'),true,512,JSON_THROW_ON_ERROR);
$categories=$manifest['categories']; $total=count($categories); $message='';$error='';$auto=false;
$state=&$_SESSION['PELCOM_SETUP'];
if (!is_array($state)) $state=['step'=>0,'results'=>[],'done'=>false];
try {
    $s=CSite::GetByID('s1')->Fetch();
    if (!$s || $s['DIR']!=='/') throw new RuntimeException('Пакет рассчитан на свежую установку с сайтом s1 в корне /. Не продолжайте на другом сайте.');
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!check_bitrix_sessid()) throw new RuntimeException('Сессия истекла. Обновите страницу и повторите.');
        $action=(string)($_POST['action'] ?? '');
        if ($action==='start' || $action==='next') {
            $step=(int)$state['step'];
            if ($step<$total) {
                $c=$categories[$step];$r=pelcomImportCategory($c,$manifest);
                if ($r['count']<count($c['models'])) throw new RuntimeException('Количество моделей не совпало: '.$c['name']);
                $state['results'][$c['code']]=$r;$state['step']=$step+1;
                $message=$c['name'].': моделей '.$r['count'];$auto=true;
            } else {
                pelcomConfigureSite();$state['done']=true;
                $message='Импорт завершён. Проверьте главную, категории, модели и фотографии. Индексация пока закрыта.';
            }
        } elseif ($action==='publish') {
            if (!$state['done']) throw new RuntimeException('Сначала завершите импорт и проверьте сайт.');
            $path=$_SERVER['DOCUMENT_ROOT'].'/local/config/pelcom.php';$config=require $path;
            $config['indexing']=true;
            $robots="User-agent: *\nAllow: /\nDisallow: /bitrix/\nDisallow: /local/\nDisallow: /upload/tmp/\nSitemap: https://pelcom-tech.ru/sitemap.xml\n";
            if (!is_writable($path) || !is_writable($_SERVER['DOCUMENT_ROOT'].'/robots.txt')) throw new RuntimeException('Нет права записи в конфиг или robots.txt. Проверьте владельца файлов.');
            if (file_put_contents($path,"<?php\nreturn ".var_export($config,true).";\n",LOCK_EX)===false) throw new RuntimeException('Не удалось записать конфигурацию.');
            if (file_put_contents($_SERVER['DOCUMENT_ROOT'].'/robots.txt',$robots,LOCK_EX)===false) throw new RuntimeException('Не удалось записать robots.txt.');
            $message='Индексация разрешена. Доставка заявок этим действием не подключается.';
        }
    }
} catch (Throwable $e) {$error=$e->getMessage();$auto=false;}
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>
<div style="max-width:900px;background:white;padding:24px;border-radius:8px">
<h2>Pelcom · pelcom-tech.ru</h2>
<p>Только для свежей установки Битрикса. Предварительно сохраните резервную копию файлов и базы. Существующие модели импорт не перезаписывает.</p>
<p>Категорий: <b><?=count($categories)?></b>. Моделей: <b><?=count($manifest['models'])?></b>. Выполнено шагов: <b><?=(int)$state['step']?> / <?=$total?></b>.</p>
<?php if ($error): ?><p role="alert" style="color:#b42318"><?=htmlspecialcharsbx($error)?></p><?php endif; ?>
<?php if ($message): ?><p role="status"><?=htmlspecialcharsbx($message)?></p><?php endif; ?>
<?php if (!$error && !$state['done']): ?>
<form method="post" id="pelcom-next"><?=bitrix_sessid_post()?><input type="hidden" name="action" value="<?=$state['step']?'next':'start'?>"><button type="submit" class="adm-btn-save"><?=$state['step']?'Продолжить импорт':'Импортировать 14 категорий и 99 моделей'?></button></form>
<?php if ($auto): ?><script>setTimeout(function(){document.getElementById('pelcom-next').submit()},400);</script><?php endif; ?>
<?php elseif ($state['done']): ?>
<p><a href="/" target="_blank">Открыть сайт</a> · <a href="/catalog/" target="_blank">Открыть каталог</a></p>
<p>Перед индексацией проверьте HTTPS, фотографии, ссылки и настройте/проверьте получение заявок. Получатель заявок ещё не задан.</p>
<form method="post"><?=bitrix_sessid_post()?><input type="hidden" name="action" value="publish"><button type="submit">Проверка завершена — разрешить индексацию</button></form>
<p>После завершения удалить /bitrix/admin/pelcom_setup.php и /local/tools/pelcom_setup.php через файловый менеджер.</p>
<?php endif; ?>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php'; ?>
