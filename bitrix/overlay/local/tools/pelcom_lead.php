<?php
// Same-origin JSON endpoint. No visitor payloads are written to logs or files.
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function leadReply(int $status,string $message): never {http_response_code($status);echo json_encode(['ok'=>$status===200,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') leadReply(405,'Используйте форму сайта');
if(($_SERVER['HTTP_ORIGIN'] ?? '')!=='https://pelcom-tech.ru') leadReply(403,'Неверный источник запроса');
if((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>16384 || !str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) leadReply(400,'Некорректный запрос');
$data=json_decode(file_get_contents('php://input',false,null,0,16385),true);
if(!is_array($data)) leadReply(400,'Некорректные данные');
foreach($data as $value) if(!is_scalar($value) && $value!==null) leadReply(400,'Некорректные поля');
if(!empty($data['_gotcha'])) leadReply(400,'Не удалось отправить заявку');
$name=trim((string)($data['name'] ?? ''));$phone=trim((string)($data['phone'] ?? ''));
if($name==='' || mb_strlen($name)>150 || strlen(preg_replace('/\D/','',$phone))<7 || mb_strlen($phone)>80) leadReply(422,'Укажите имя и корректный телефон');
if(!in_array((string)($data['consent'] ?? ''),['on','yes','1','true'],true)) leadReply(422,'Нужно согласие на обработку данных');
if(!empty($data['email']) && !filter_var($data['email'],FILTER_VALIDATE_EMAIL)) leadReply(422,'Проверьте email');
$rateDir=dirname($_SERVER['DOCUMENT_ROOT'],2).'/bin-tmp/pelcom-lead-rate';
if(!is_dir($rateDir) && !mkdir($rateDir,0700,true)) leadReply(503,'Попробуйте позже');
$rateFile=$rateDir.'/'.hash('sha256',$_SERVER['REMOTE_ADDR'] ?? 'unknown');$lock=fopen($rateFile,'c+');
if(!$lock || !flock($lock,LOCK_EX)) leadReply(503,'Попробуйте позже');
$last=(int)stream_get_contents($lock);if($last>time()-30) leadReply(429,'Подождите 30 секунд перед повторной отправкой');
$labels=['name'=>'Имя','phone'=>'Телефон','email'=>'Email','company'=>'Компания','category'=>'Категория','region'=>'Регион','industry'=>'Целевая отрасль','message'=>'Комментарий','page'=>'Страница'];
$body="Заявка с сайта pelcom-tech.ru\n\n";
foreach($labels as $key=>$label) if(isset($data[$key]) && trim((string)$data[$key])!=='') {
 $value=trim((string)$data[$key]);if(mb_strlen($value)>4000) leadReply(422,'Слишком длинное сообщение');$body.=$label.': '.$value."\n";
}
$subject='=?UTF-8?B?'.base64_encode('Заявка с сайта Pelcom').'?=';
$headers="From: Pelcom <webmaster@pelcom-tech.ru>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
if(!empty($data['email'])) $headers.='Reply-To: '.$data['email']."\r\n";
if(!mail('info@pelcom-tech.ru',$subject,$body,$headers)) leadReply(503,'Почтовый сервер не принял заявку');
ftruncate($lock,0);rewind($lock);fwrite($lock,(string)time());flock($lock,LOCK_UN);fclose($lock);chmod($rateFile,0600);
leadReply(200,'Заявка передана почтовому серверу');
