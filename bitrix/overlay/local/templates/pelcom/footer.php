<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $pelcomPage;
$page = preg_match('/^[a-z0-9-]+$/D', $pelcomPage ?? '') ? $pelcomPage : 'pelcom-landing';
require __DIR__.'/parts/'.$page.'-footer.php';
?></body></html>
