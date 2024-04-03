<?php
/************************************\
| Telegram-канал: https://t.me/z_tds |
| Cloudflare API v.0.5               |
| Массовая замена IP в DNS записях   |
| Список аккаунтов: data/cf.txt      |
| Список доменов: data/domains.txt   |
\************************************/
@error_reporting(-1);
@ini_set('display_errors', 1);
@set_time_limit(0);
$ip_old = '127.0.0.1';//ip который нужно заменить
$ip_new = '127.0.0.2';//новый ip
$num_domains = 200;//максимальное количество обрабатываемых доменов
$num_subdomains = 200;//... субдоменов (по докам максимум 100, по факту можно ставить больше)
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
//получаем список доменов и их идентификаторы
		$url = 'https://api.cloudflare.com/client/v4/zones?page=1&per_page=$num_domains';
		$type = 'get';
		curl();
		output('domains: '.count($res->result).'<br>');
//меняем ip
		$res1 = $res;
		$t = '';
		foreach($res1->result as $e1){
			$name = $e1->name;
			$id = $e1->id;
			$x = 1;//номер страницы
			while(true){
				$url = "https://api.cloudflare.com/client/v4/zones/$id/dns_records?page=$x&per_page=$num_subdomains";
				$type = 'get';
				curl();
				$res2 = $res;
				$total_pages = $res2->result_info->total_pages;
				foreach($res2->result as $e2){
					$content_dns = $e2->content;
					$id_dns = $e2->id;
					$type_dns = $e2->type;
					$name_dns = $e2->name;
					$url = "https://api.cloudflare.com/client/v4/zones/$id/dns_records/$id_dns";
					if($content_dns == $ip_old){
						$t = 1;
						$data = '{"type":"'.$type_dns.'", "name":"'.$name_dns.'", "content":"'.$ip_new.'", "proxied":true}';
						$type = 'put';
						curl();
					}
				}
				if($x == $total_pages){
					break;
				}
				$x++;
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
	global $url, $headers, $data, $name, $type_dns, $name_dns, $res, $success, $error, $type, $auth_code;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	if($type == 'put'){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	$res = json_decode(curl_exec($ch));
	$auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($type == 'put'){
		if($res->success){
			output("$name | type: $type_dns | name: $name_dns | $success<br>");
		}
		else{
			output("$name | type: $type_dns | name: $name_dns | $error<br>");
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