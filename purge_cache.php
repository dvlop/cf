<?php
/************************************\
| Telegram-канал: https://t.me/z_tds |
| Cloudflare API v.0.5               |
| Очистка кэша                       |
| Список аккаунтов: data/cf.txt      |
| Список доменов: data/domains.txt   |
\************************************/
@error_reporting(-1);
@ini_set('display_errors', 1);
@set_time_limit(0);
$num_domains = 200;//доменов на странице
/*Ниже ничего не изменяйте*/
echo "<!DOCTYPE html><html><head><title>CF API</title><script>window.stopScroll=0;scrollingElement=(document.scrollingElement||document.body);setTimeout(function(){scrollBottom();},1000*1);function scrollBottom(){if(window.stopScroll!=1){setTimeout(function(){scrollBottom();},1000*0.5);}scrollingElement.scrollTop=scrollingElement.scrollHeight;}</script></head><body>";
$success = '<span style="color:green">success</span>';
$error = '<span style="color:red">error</span>';
//список аккаунтов cf
$cf = file(__DIR__.'/data/cf.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(empty($cf)){
	output("Empty");
	stop();
}
//список доменов
$domains = file(__DIR__.'/data/domains.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach($cf as $v){
	$v = explode(';', $v);
	$email = $v[0];
	$api_key = $v[1];
//headers
	$headers = array();
	$headers[] = "X-Auth-Email: $email";
	$headers[] = "X-Auth-Key: $api_key";
	$headers[] = "Content-Type: application/json";
//проверяем авторизацию
	$url = 'https://api.cloudflare.com/client/v4/user';
	$type = 'get';
	curl();
	output($email.'<br>');
	if($auth_code == 200 && $res->success){
		output("authorization: $success<br><br>");
		$t = '';
		clear();
		if(empty($t)){
			output("not found<br>");
		}
	}
	else{
		output("authorization: $error<br>");
	}
	output('--------------------<br>');
}
stop();
//
function clear(){
	global $url, $res, $name, $num_domains, $domains, $type, $data, $t;
//получаем список доменов и их идентификаторы
	$url = "https://api.cloudflare.com/client/v4/zones?page=1&per_page=$num_domains";
	$type = 'get';
	curl();
	output('domains: '.count($res->result).'<br>');
	$res_1 = $res;
	$data = '{"purge_everything":true}';
	foreach($res_1->result as $e1){
		$name = $e1->name;
		$id = $e1->id;
		$url = "https://api.cloudflare.com/client/v4/zones/$id/purge_cache";
		if(empty($domains) || in_array($name, $domains)){
			$t = 1;
			$type = 'post';
			curl();
		}
	}
}
//
function curl(){
	global $url, $headers, $data, $name, $res, $type, $success, $error, $auth_code;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	if($type == 'post'){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$res = json_decode(curl_exec($ch));
	$auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($type == 'post'){
		if($res->success){
			output("$name => $success<br>");
		}
		else{
			output("$name => $error<br>");
		}
	}
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