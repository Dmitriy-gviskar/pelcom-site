<?php
if (PHP_SAPI!=='cli') exit;
$_SERVER['DOCUMENT_ROOT']=realpath($argv[1]);$_SERVER['HTTP_HOST']='127.0.0.1:18080';
define('NO_KEEP_STATISTIC',true);define('NOT_CHECK_PERMISSIONS',true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
$manifest=json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/migrations/catalog.json'),true);
$count=0;
foreach ($manifest['categories'] as $c) {
 $block=CIBlock::GetList([],['TYPE'=>'pelcom','CODE'=>$c['block_code']])->Fetch();if(!$block)throw new RuntimeException('Missing '.$c['code']);
 $rows=CIBlockElement::GetList([],['IBLOCK_ID'=>$block['ID']],false,false,['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT']);$actual=[];
 while($o=$rows->GetNextElement()){$f=$o->GetFields();$f['P']=$o->GetProperties();$actual[$f['CODE']]=$f;}
 if(count($actual)!==count($c['models']))throw new RuntimeException('Count '.$c['code']);
 foreach($manifest['models'] as $m) {
  if($m['category']!==$c['code'])continue;
  $v=$actual[$m['code']];$p=$v['P'];
  if($v['~NAME']!==$m['name'] || (int)$p['PRICE_FROM']['VALUE']!==$m['price'])throw new RuntimeException('Name/price '.$m['source']);
  if($v['~PREVIEW_TEXT']!==$m['description'])throw new RuntimeException('Description '.$m['source']);
  if($p['CARD_LINES']['~VALUE']!==$m['card_specs'])throw new RuntimeException('Card specs '.$m['source']);
  foreach($m['specs'] as $row)if($p[$manifest['spec_codes'][$row[0]]]['~VALUE']!==$row[1])throw new RuntimeException('Spec '.$m['source'].' '.$row[0]);
  if($m['detail_image'] && !CFile::GetPath($p['DETAIL_PHOTO']['VALUE']))throw new RuntimeException('Photo '.$m['source']);
  if ($m['detail_image']) {
      $photo=CFile::GetPath($p['DETAIL_PHOTO']['VALUE']);
      if (hash_file('sha256',$_SERVER['DOCUMENT_ROOT'].$photo)!==hash_file('sha256',$_SERVER['DOCUMENT_ROOT'].'/'.$m['detail_image'])) throw new RuntimeException('Photo content mismatch '.$m['source']);
  }
  $count++;
 }
}
echo 'PASS: '.count($manifest['categories'])." categories; $count models; descriptions, prices, specifications and photos match source.\n";
