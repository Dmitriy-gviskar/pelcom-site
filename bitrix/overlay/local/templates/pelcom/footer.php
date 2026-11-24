<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $pelcomPage, $pelcomInterior, $pelcomPngShell;
$page = preg_match('/^[a-z0-9-]+$/D', $pelcomPage ?? '') ? $pelcomPage : 'pelcom-landing';
require_once __DIR__.'/site-refresh.php';
if ($pelcomInterior ?? false) {
    echo '</div>';
}
if ($pelcomPngShell ?? false) {
    echo pelcomPngFooter($page);
    echo '<script src="/png-shell.js?v=20261124" defer></script>';
} elseif ($pelcomInterior ?? false) {
    ob_start();
    require __DIR__.'/parts/'.$page.'-footer.php';
    echo pelcomInteriorFooter(ob_get_clean());
} else {
    require __DIR__.'/parts/'.$page.'-footer.php';
}
?><script src="/ui-polish.js?v=20261109-3" defer></script></body></html>
