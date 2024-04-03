<?php
/************************************\
| Telegram-канал: https://t.me/z_tds |
| Cloudflare API v.0.5               |
| Добавление доменов и DNS записей   |
| Список доменов: data/domains.txt   |
\************************************/
@error_reporting(-1);
@ini_set('display_errors', 1);
@set_time_limit(0);
$email = 'user@gmail.com';//email
$api_key = '94cbe17f45a64fd99b727e5aa570ef07481e3';//ключ API
$ip = '127.0.0.1';//ip
$del = 1;//искать и удалять старые DNS записи (0/1)
$pause = 10;//задержка перед поиском старых DNS записей (для некоторых зон нужно увеличивать до 60 секунд)
$auh = 1;//always use https (0/1)
$ahr = 1;//automatic https rewrites (0/1)
$ssl = 'flexible';//тип SSL: off, flexible, full, strict (оставьте пустым что бы было "по умолчанию")
$wildcard = 0;//wildcard (0/1)
/*Ниже ничего не изменяйте*/
echo "<!DOCTYPE html><html><head><title>CF API</title><script>window.stopScroll=0;scrollingElement=(document.scrollingElement||document.body);setTimeout(function(){scrollBottom();},1000*1);function scrollBottom(){if(window.stopScroll!=1){setTimeout(function(){scrollBottom();},1000*0.5);}scrollingElement.scrollTop=scrollingElement.scrollHeight;}</script></head><body>";
$timeout_curl = 100;//таймаут для curl
$success = '<span style="color:green">success</span>';
$error = '<span style="color:red">error</span>';
//список доменов
$domains = file(__DIR__.'/data/domains.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if(empty($domains)){
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
//добавляем домены
foreach($domains as $d){
	$d = trim($d);
//create zone
	$url = 'https://api.cloudflare.com/client/v4/zones';
	$data = '{"name":"'.$d.'", "jump_start":true}';
	$type = 'post';
	curl();
	if($res->success){
		output("create zone \"$d\" => $success<br>");
		$id = $res->result->id;
		$ns1 = $res->result->name_servers[0];
		$ns2 = $res->result->name_servers[1];
//поиск и удаление старых DNS записей
		if($del == 1){
			$url = "https://api.cloudflare.com/client/v4/zones/$id/dns_records";
			sleep ($pause);
			curl();
			$res1 = $res;
			foreach($res1->result as $e){
				$id_zone = $e->id;
				$name = $e->name;
				$url = "https://api.cloudflare.com/client/v4/zones/$id/dns_records/$id_zone";
				$type = 'delete';
				curl();
				if($res->success){
					output("delete DNS record \"$name\" => $success<br>");
				}
				else{
					output("delete DNS record \"$name\" => $error<br>");
				}
			}
		}
//добавление DNS записей
		$url = "https://api.cloudflare.com/client/v4/zones/$id/dns_records";
		$data = '{"type":"A","name":"'.$d.'","content":"'.$ip.'", "proxied":true}';
		$type = 'post';
		curl();
		if($res->success){
			output("create DNS record \"$d\" => $success<br>");
		}
		else{
			output("create DNS record \"$d\" => $error<br>");
		}
		$n = 'www';
		if($wildcard == 1){
			$n = '*';
		}
		$data = '{"type":"A","name":"'.$n.'","content":"'.$ip.'", "proxied":true}';
		$type = 'post';
		curl();
		if($res->success){
			output("create DNS record \"$n\" => $success<br>");
		}
		else{
			output("create DNS record \"$n\" => $error<br>");
		}
//always use https
		if($auh == 1){
			$url = "https://api.cloudflare.com/client/v4/zones/$id/settings/always_use_https";
			$data = '{"value":"on"}';
			$type = 'patch';
			curl();
			if($res->success){
				output("always use https => $success<br>");
			}
			else{
				output("always use https => $error<br>");
			}
		}
//automatic https rewrites
		if($ahr == 1){
			$url = "https://api.cloudflare.com/client/v4/zones/$id/settings/automatic_https_rewrites";
			$data = '{"value":"on"}';
			$type = 'patch';
			curl();
			if($res->success){
				output("automatic https rewrites => $success<br>");
			}
			else{
				output("automatic https rewrites => $error<br>");
			}
		}
//ssl
		if(!empty($ssl)){
			$url = "https://api.cloudflare.com/client/v4/zones/$id/settings/ssl";
			$data = '{"value":"'.$ssl.'"}';
			$type = 'patch';
			curl();
			if($res->success){
				output("change SSL setting => $success<br>");
			}
			else{
				output("change SSL setting => $error<br>");
			}
		}
		output('NS: '.$ns1.', '.$ns2.'<br><br>');
	}
	else{
		output("create zone \"$d\" => $error<br><br>");
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
	if($type == 'delete'){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	}
	if($type == 'patch'){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
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