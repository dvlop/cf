<?php
/************************************\
| Telegram-канал: https://t.me/z_tds |
| Cloudflare API v.0.5               |
| Добавление субдоменов              |
| Список: data/subdomains.txt        |
\************************************/
@error_reporting(-1);
@ini_set('display_errors', 1);
@set_time_limit(0);
$email = 'user@gmail.com';//email
$api_key = '94cbe17f45a64fd99b727e5aa570ef07481e3';//ключ API
$domain = 'domain.com';//домен для которого нужно добавить субдомены (домен уже должен быть добавлен в аккаунт!)
$ip = '127.0.0.1';//ip
/*Ниже ничего не изменяйте*/
echo "<!DOCTYPE html><html><head><title>CF API</title><script>window.stopScroll=0;scrollingElement=(document.scrollingElement||document.body);setTimeout(function(){scrollBottom();},1000*1);function scrollBottom(){if(window.stopScroll!=1){setTimeout(function(){scrollBottom();},1000*0.5);}scrollingElement.scrollTop=scrollingElement.scrollHeight;}</script></head><body>";
//таймаут для curl
$timeout_curl = 100;
$success = '<span style="color:green">success</span>';
$error = '<span style="color:red">error</span>';
//список субдоменов
$subdomains = file(__DIR__.'/data/subdomains.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(empty($subdomains)){
	output("Empty");
	stop();
}
//headers
$headers = array();
$headers[] = "X-Auth-Email: $email";
$headers[] = "X-Auth-Key: $api_key";
$headers[] = "Content-Type: application/json";
//проверяем авторизацию
$url = 'https://api.cloudflare.com/client/v4/user';
$type = 'get';
curl();
if($auth_code == 200 && $res->success){
	output("authorization: $success<br><br>");
}
else{
	output("authorization: $error");
	stop();
}
//получаем данные домена
$url = "https://api.cloudflare.com/client/v4/zones?name=$domain";
$type = 'get';
curl();
if($res->success && !empty($res->result[0]->id)){
	$id = $res->result[0]->id;
}
else{
	output('<span style="color:red">domain not found!</span><br>');
	stop();
}
//добавление субдоменов
foreach($subdomains as $sub){
	$sub = trim($sub);
	$url = "https://api.cloudflare.com/client/v4/zones/$id/dns_records";
	$data = '{"type":"A", "name":"'.$sub.'", "content":"'.$ip.'", "proxied":true}';
	$type = 'post';
	curl();
	if($res->success){
		output('create DNS record "'.$sub.'" => <span style="color:green">'.$success.'</span><br>');
	}
	else{
		output('create DNS record "'.$sub.'" => <span style="color:red">'.$error.'</span><br>');
	}
}
stop();
//
function curl(){
	global $url, $headers, $data, $res, $timeout_curl, $type, $auth_code;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_curl);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	if($type == 'post'){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	$res = json_decode(curl_exec($ch));
	$auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$type = '';
}
//
function output($str){
	echo $str;
	ob_flush();
	flush();
}
//
function stop(){
	echo "<script>window.stopScroll=1;</script></body></html>";
	exit();
}
?>