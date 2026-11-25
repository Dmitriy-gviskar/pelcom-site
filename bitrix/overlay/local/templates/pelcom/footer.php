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
?>
<aside class="pelcom-analytics-choice" id="pelcom-analytics-choice" data-counter="114278572" aria-label="Настройки аналитики" hidden>
    <p>Разрешить Яндекс Метрику для анализа посещаемости сайта? <a href="/privacy/#analytics">Подробнее в политике обработки данных</a>.</p>
    <div class="pelcom-analytics-actions">
        <button type="button" data-analytics-choice="declined">Отказаться</button>
        <button type="button" data-analytics-choice="accepted">Разрешить аналитику</button>
    </div>
</aside>
<script src="/pelcom-analytics.js?v=2" defer></script>
<script src="/ui-polish.js?v=20261109-3" defer></script></body></html>
