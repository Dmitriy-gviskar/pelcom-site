<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/local/lib/pelcom_routes.php';
AddEventHandler('iblock','OnBeforeIBlockElementAdd','pelcomValidateProduct');
AddEventHandler('iblock','OnBeforeIBlockElementUpdate','pelcomValidateProduct');
AddEventHandler('iblock','OnAfterIBlockElementUpdate','pelcomRememberProductUrl');
AddEventHandler('main','OnPageStart','pelcomRedirectPage');
AddEventHandler('main','OnEndBufferContent','pelcomRewriteLinks');

AddEventHandler('main','OnBuildGlobalMenu',static function (&$global,&$menu) {
    $menu[]=['parent_menu'=>'global_menu_content','section'=>'pelcom','sort'=>50,'text'=>'Pelcom: управление сайтом','title'=>'Каталог и адреса страниц','items_id'=>'pelcom_admin','items'=>[
        ['text'=>'Товары и категории','url'=>'iblock_admin.php?type=pelcom&lang=ru'],
        ['text'=>'Адреса страниц','url'=>'pelcom_pages.php?lang=ru']
    ]];
});
