<?php
/*
 * 微信API响应页面，用来处理来自微信的请求
 */
$wx = new WeixinAPI();
$wx->verify();

$wx->onmessage('event', function($message) use($wx){
	if($message['EVENTKEY'] === 'COMING_SOON'){
		$wx->reply_message('艇好玩微信公众号8月底盛大发布，同时揭晓免费玩好艇的幸运玩家。', $message);
	}
});
