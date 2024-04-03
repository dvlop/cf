<?php
/************************************\
| Telegram-канал: https://t.me/z_tds |
| Cloudflare API v.0.5               |
| Удаление доменов                   |
| Список аккаунтов: data/cf.txt      |
| Список доменов: data/domains.txt   |
\************************************/
@error_reporting(-1);
@ini_set('display_errors', 1);
@set_time_limit(0);
$num_domains = 200;//максимальное количество обрабатываемых доменов
/*Ниже ничего не изменяйте*/
echo "<!DOCTYPE html><html><head><title>CF API</title><script>window.stopScroll=0;scrollingElement=(document.scrollingElement||document.body);setTimeout(function(){scrollBottom();},1000*1);function scrollBottom(){if(window.stopScroll!=1){setTimeout(function(){scrollBottom();},1000*0.5);}scrollingElement.scrollTop=scrollingElement.scrollHeight;}</script></head><body>";
$timeout = 100;//таймаут для curl
$success = '<span style="color:green">success</span>';
$error = '<span style="color:red">error</span>';
//список доменов
$domains = file(__DIR__.'/data/domains.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//список аккаунтов cf
$cf = file(__DIR__.'/data/cf.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(empty($domains) || empty($cf)){
	output("Empty");
	stop();
}
foreach($cf as $a){
	$a = explode(';', $a);
	$email = $a[0];
	$api_key = $a[1];
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
//получаем данные
		$url = "https://api.cloudflare.com/client/v4/zones?page=1&per_page=$num_domains";
		$type = 'get';
		curl();
//удаляем домены
		$res_all = $res;
		$t = '';
		foreach($res_all->result as $e){
			$name = $e->name;
			$id = $e->id;
			if(in_array($name, $domains)){
				$t = 1;
				$url = "https://api.cloudflare.com/client/v4/zones/$id";
				$type = 'del';
				curl();
			}
		}
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
function curl(){
	global $url, $headers, $res, $type, $name, $timeout, $auth_code, $success, $error;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	if($type == 'del'){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	}
	$res = json_decode(curl_exec($ch));
	if($type == 'del'){
		if($res->success){
			output("$name => $success<br>");
		}
		else{
			output("$name => $error<br>");
		}
	}
	$auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
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