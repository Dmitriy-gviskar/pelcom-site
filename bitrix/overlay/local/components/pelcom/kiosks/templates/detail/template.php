<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$item=$arResult['ITEMS'][0];$p=$item['PROPERTIES'];$specs=[];
$manifest=json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/migrations/catalog.json'),true);
// Preserve each model's original row order; values are editable properties.
$original=json_decode($p['SPECS_JSON']['~VALUE'] ?? '[]',true) ?: [];
foreach ($original as $row) {
    $code=$manifest['spec_codes'][$row[0]] ?? null;
    $value=$code ? ($p[$code]['~VALUE'] ?? '') : '';
    if ($value!=='') $specs[]=[$row[0],$value];
}
foreach ($manifest['spec_codes'] as $label=>$code) {
    if (!in_array($label,array_column($specs,0),true) && ($p[$code]['~VALUE'] ?? '')!=='') $specs[]=[$label,$p[$code]['~VALUE']];
}
$source=basename($p['SOURCE_FILE']['VALUE'] ?? '');
if (!in_array($source,array_column($manifest['models'],'source'),true)) { require __DIR__.'/generic.php'; return; }
$view=$_SERVER['DOCUMENT_ROOT'].'/local/model_views/'.substr($source,0,-5).'.php';
if (!is_file($view)) { ShowError('Не найден шаблон модели'); return; }
require $view;
