<?php
/**
 * Plugin Name: Weixin API
 * Plugin URI: 
 * Description: 在WordPress中调用微信公众账号API，实现用户鉴权，微信支付，菜单更新等功能
 * Version: 0.5
 * Author: Uice Lu
 * Author URI: https://cecilia.uice.lu/
 * License: 
 */
class WeixinAPI {
	
	private $token, // 微信公众账号后台 / 高级功能 / 开发模式 / 服务器配置
			$app_id, // 开发模式 / 开发者凭据
			$app_secret, // 同上
			$mch_id, // 微信商户ID
			$mch_key; // 微信商户Key
	
	
	function __construct() {
		// 从WordPress配置中获取这些公众账号身份信息
		foreach(array(
			'app_id',
			'app_secret',
			'mch_id',
			'mch_key',
			'token'
		) as $item){
			$this->$item = get_option('wx_' . $item);
		}

	}
	
	/*
	 * 验证来源为微信
	 * 放在用于响应微信消息请求的脚本最上端
	 */
	function verify(){
		$sign = array(
			$this->token,
			$_GET['timestamp'],
			$_GET['nonce']
		);

		sort($sign, SORT_STRING);

		if(sha1(implode($sign)) !== $_GET['signature']){
			exit('Signature verification failed.');
		}
		
		if(isset($_GET['echostr'])){
			echo $_GET['echostr'];
		}

	}
	
	function call($url){
//		error_log('Weixin API called: ' . $url);
		return file_get_contents($url);
	}
	
	/**
	 * 获得站点到微信的access_token
	 * 并缓存于站点数据库
	 * 可以判断过期并重新获取
	 */
	function get_access_token(){
		
		$stored = json_decode(get_option('wx_access_token'));
		
		if($stored && $stored->expires_at > time()){
			return $stored->token;
		}
		
		$query_args = array(
			'grant_type'=>'client_credential',
			'appid'=>$this->app_id,
			'secret'=>$this->app_secret
		);
		
		$return = json_decode($this->call('https://api.weixin.qq.com/cgi-bin/token?' . http_build_query($query_args)));
		
		if($return->access_token){
			update_option('wx_access_token', json_encode(array('token'=>$return->access_token, 'expires_at'=>time() + $return->expires_in - 60)));
			return $return->access_token;
		}
		
		error_log('Get access token failed. ' . json_encode($return));
		
	}
	
	/**
	 * 直接获得用户信息
	 * 仅在用户与公众账号发生消息交互的时候才可以使用
	 * 换言之仅可用于响应微信消息请求的脚本中
	 */
	function get_user_info($openid, $lang = 'zh_CN'){
		
		$url = 'https://api.weixin.qq.com/cgi-bin/user/info?';
		
		$query_vars = array(
			'access_token'=>$this->get_access_token(),
			'openid'=>$openid,
			'lang'=>$lang
		);
		
		$url .= http_build_query($query_vars);
		
		$user_info = json_decode($this->call($url));
		
		return $user_info;
		
	}
	
	/**
	 * 根据open_id自动在系统中查找或注册用户，并获得微信用户信息
	 * 仅在用户与公众账号发生消息交互的时候才可以使用
	 */
	function loggin($open_id){
		
		$users = get_users(array('meta_key'=>'wx_openid','meta_value'=>$open_id));

		if(!$users){
			$user_info = $this->get_user_info($open_id);
			$user_id = wp_create_user($user_info->nickname, $open_id);
			add_user_meta($user_id, 'wx_openid', $open_id, true);
			add_user_meta($user_id, 'sex', $user_info->sex, true);
			add_user_meta($user_id, 'country', $user_info->country, true);
			add_user_meta($user_id, 'province', $user_info->province, true);
			add_user_meta($user_id, 'language', $user_info->language, true);
			add_user_meta($user_id, 'headimgurl', $user_info->headimgurl, true);
			add_user_meta($user_id, 'subscribe_time', $user_info->subscribe_time, true);
		}
		else{
			$user_id = $users[0]->ID;
			if($users[0]->user_login === substr($open_id, -8, 8)){
				$user_info = $this->get_user_info($open_id);
				update_user_meta($user_id, 'nickname', $user_info->nickname);
				add_user_meta($user_id, 'sex', $user_info->sex, true);
				add_user_meta($user_id, 'country', $user_info->country, true);
				add_user_meta($user_id, 'province', $user_info->province, true);
				add_user_meta($user_id, 'language', $user_info->language, true);
				add_user_meta($user_id, 'headimgurl', $user_info->headimgurl, true);
				add_user_meta($user_id, 'subscribe_time', $user_info->subscribe_time, true);
			}
		}
		
		wp_set_current_user($user_id);
		
		return $user_id;
		
	}
	
	/**
	 * 生成OAuth授权地址
	 */
	function generate_oauth_url($redirect_uri = null, $state = '', $scope = 'snsapi_base'){
		
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
		
		$query_args = array(
			'appid'=>$this->app_id,
			'redirect_uri'=>is_null($redirect_uri) ? site_url() : $redirect_uri,
			'response_type'=>'code',
			'scope'=>$scope,
			'state'=>$state
		);
		
		$url .= http_build_query($query_args) . '#wechat_redirect';
		
		return $url;
		
	}
	
	/**
	 * 生成授权地址并跳转
	 */
	function oauth_redirect($redirect_uri = null, $state = '', $scope = 'snsapi_base'){
		
		if(headers_sent()){
			exit('Could not perform an OAuth redirect, headers already sent');
		}
		
		$url = $this->generate_oauth_url($redirect_uri, $state, $scope);
		
		header('Location: ' . $url);
		exit;
		
	}
	
	/**
	 * 根据一个OAuth授权请求中的code，获得并存储用户授权信息
	 */
	function get_oauth_token($code = null, $scope = 'snsapi_base'){
		
		if(is_user_logged_in()){
			
			$auth_result = json_decode(get_user_meta(get_current_user_id(), 'oauth_info', true));
			
			if($auth_result){
				if($auth_result->expires_at >= time()){
					return $auth_result;
				}
				else{
					$auth_result = $this->refresh_oauth_token($auth_result->refresh_token);
					if(isset($auth_result->access_token)){
						update_user_meta(get_current_user_id(), 'oauth_info', json_encode($auth_result));
						return $auth_result;
					}
				}
			}
		}
		
		if(isset($_GET['code'])){
			$code = $_GET['code'];
		}
		
		if(is_null($code)){
			$this->oauth_redirect(site_url() . $_SERVER['REQUEST_URI'], '', $scope);
		}
		
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?';

		$query_args = array(
			'appid'=>$this->app_id,
			'secret'=>$this->app_secret,
			'code'=>$code,
			'grant_type'=>'authorization_code'
		);

		$auth_result = json_decode($this->call($url . http_build_query($query_args)));

		if(!isset($auth_result->openid)){
			error_log('Get OAuth token failed. ' . json_encode($auth_result));
			exit;
		}
		
		$auth_result->expires_at = $auth_result->expires_in + time();
		
		$user = get_user_by('login', $auth_result->openid);
		
		if(!$user){
			$user_id = wp_insert_user(array('user_login'=>$auth_result->openid));
		}else{
			$user_id = $user->ID;
		}
		
		update_user_meta($user_id, 'oauth_info', json_encode($auth_result));
		
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id, true);
		
		return $auth_result;
	}
	
	/**
	 * 刷新用户OAuth access token
	 */
	function refresh_oauth_token($refresh_token){
		
		$url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?';
		
		$query_args = array(
			'appid'=>$this->app_id,
			'grant_type'=>'refresh_token',
			'refresh_token'=>$refresh_token,
		);
		
		$url .= http_build_query($query_args);
		
		$auth_result = json_decode($this->call($url));
		
		if(empty($auth_result->access_token)){
			return false;
		}
		
		$auth_result->expires_at = time() + $auth_result->expires_in;
		
		return $auth_result;
	}
	
	/**
	 * OAuth方式获得用户信息
	 * 注意，access token的scope必须包含snsapi_userinfo，才能调用本函数获取
	 */
	function oauth_get_user_info($lang = 'zh_CN'){
		
		$url = 'https://api.weixin.qq.com/sns/userinfo?';
		
		$auth_info = $this->get_oauth_token(null, 'snsapi_userinfo');
		
		$query_vars = array(
			'access_token'=>$auth_info->access_token,
			'openid'=>$auth_info->openid,
			'lang'=>$lang
		);
		
		$url .= http_build_query($query_vars);
		
		$user_info = json_decode($this->call($url));
		
		return $user_info;
	}
	
	function generate_pay_sign(array $data){
		$data = array_filter($data);
		ksort($data, SORT_STRING);
		$string1 = urldecode(http_build_query($data));
		return strtoupper(md5($string1 . '&key=' . $this->mch_key));
	}
	
	/**
	 * 统一支付接口,可接受 JSAPI/NATIVE/APP下预支付订单,返回预支付订单号。 NATIVE支付返回二维码 code_url。
	 * @param string $order_id
	 * @param float $total_price
	 * @param string $order_name
	 * @param string $attach
	 */
	function unified_order($order_id, $total_price, $openid, $notify_url, $order_name, $trade_type = 'JSAPI', $attach = ' '){
		
		$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder?';
		
		$args = array(
			'appid'=>$this->app_id,
			'mch_id'=>$this->mch_id,
			'nonce_str'=>rand(1E15, 1E16-1),
			'body'=>$order_name,
			'attach'=>$attach,
			'out_trade_no'=>$order_id,
			'total_fee'=>$total_price * 100,
			'spbill_create_ip'=>$_SERVER['REMOTE_ADDR'],
			'time_start'=>date('YmdHis'),
			'notify_url'=>$notify_url,
			'trade_type'=>$trade_type,
			'openid'=>$openid
		);
		
		$args['sign'] = $this->generate_pay_sign($args);
		
		$query_data = array_map(function($value){return (string) $value;}, $args);
		
		$response = json_decode($this->call($url . http_build_query($query_data)));
		
		if($response->return_code === 'SUCCESS' && $response->result_code === 'SUCCESS'){
			return $trade_type === 'JSAPI' ? $response->prepay_id : $response->code_url;
		}
		else{
			return $response;
		}
	}
	
	/**
	 * 生成支付接口参数，供前端调用
	 * @param string $notify_url 支付结果通知url
	 * @param string $order_id 订单号，必须唯一
	 * @param int $total_price 总价，单位为分
	 * @param string $order_name 订单名称
	 * @param string $attach 附加信息，将在支付结果通知时原样返回
	 * @return array
	 */
	function generate_js_pay_args($prepay_id){
		
		$args = array(
			'appId'=>$this->app_id,
			'timeStamp'=>time(),
			'nonceStr'=>rand(1E15, 1E16-1),
			'package'=>'prepay_id=' . $prepay_id,
			'signType'=>'MD5'
		);
		
		$args['paySign'] = $this->generate_pay_sign($args);
		
		return array_map(function($value){return (string) $value;}, $args);
	}
	
	/**
	 * 生成微信收货地址共享接口参数，供前端调用
	 * @return array
	 */
	function generate_js_edit_address_args(){
		
		$args = array(
			'appId'=>(string) $this->app_id,
			'scope'=>'jsapi_address',
			'signType'=>'sha1',
			'addrSign'=>'',
			'timeStamp'=>(string) time(),
			'nonceStr'=>(string) rand(1E15, 1E16-1)
		);
		
		$sign_args = array(
			'appid'=>$this->app_id,
			'url'=>"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
			'timestamp'=>$args['timeStamp'],
			'noncestr'=>$args['nonceStr'],
			'accesstoken'=>$this->get_oauth_token($_GET['code'])->access_token
		);

		ksort($sign_args, SORT_STRING);
		$string1 = urldecode(http_build_query($sign_args));
		
		$args['addrSign'] = sha1($string1);

		return $args;
		
	}
	
	/**
	 * 生成一个带参数二维码的信息
	 * @param int $scene_id $action_name 为 'QR_LIMIT_SCENE' 时为最大为100000（目前参数只支持1-100000）
	 * @param array $action_info
	 * @param string $action_name 'QR_LIMIT_SCENE' | 'QR_SCENE'
	 * @param int $expires_in
	 * @return array 二维码信息，包括获取的URL和有效期等
	 */
	function generate_qr_code($action_info = array(), $action_name = 'QR_SCENE', $expires_in = '1800'){
		// TODO 过期scene应该要回收
		// TODO scene id 到达100000后无法重置
		// TODO QR_LIMIT_SCENE只能有100000个
		$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->get_access_token();
		
		$scene_id = get_option('wx_last_qccode_scene_id', 0) + 1;
		
		if($scene_id > 100000){
			$scene_id = 1; // 强制重置
		}
		
		$action_info['scene']['scene_id'] = $scene_id;
		
		$post_data = array(
			'expire_seconds'=>$expires_in,
			'action_name'=>$action_name,
			'action_info'=>$action_info,
		);
		
		$ch = curl_init($url);
		
		curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
			CURLOPT_POSTFIELDS => json_encode($post_data)
		));
		
		$response = json_decode(curl_exec($ch));
		
		if(!property_exists($response, 'ticket')){
			return $response;
		}
		
		$qrcode = array(
			'url'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($response->ticket),
			'expires_at'=>time() + $response->expire_seconds,
			'action_info'=>$action_info,
			'ticket'=>$response->ticket
		);
		
		update_option('wx_qrscene_' . $scene_id, json_encode($qrcode));
		update_option('wx_last_qccode_scene_id', $scene_id);
		
		return $qrcode;
		
	}
	
	/**
	 * 删除微信公众号会话界面菜单
	 */
	function remove_menu(){
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->get_access_token();
		return json_decode($this->call($url));
	}
	
	/**
	 * 创建微信公众号会话界面菜单
	 */
	function create_menu($data){
		
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->get_access_token();
		
		$ch = curl_init($url);
		
		curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
			CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE)
		));
		
		$response = json_decode(curl_exec($ch));
		
		return $response;
		
	}
	
	/**
	 * 获得微信公众号会话界面菜单
	 */
	function get_menu(){
		$menu = json_decode($this->call('https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $this->get_access_token()));
		return $menu;
	}
	
	function onmessage($type, $callback){
		
		if(!isset($GLOBALS["HTTP_RAW_POST_DATA"])){
			return false;
		}
		
		xml_parse_into_struct(xml_parser_create(), $GLOBALS["HTTP_RAW_POST_DATA"], $message);

		$message = array_column($message, 'value', 'tag');

		if(!is_array($message)){
			error_log('XML parse error.');
		}

		// 事件消息			
		if($message['MSGTYPE'] === $type){
			$callback($message);
		}
		
		return $this;
		
	}
	
	function reply_message($reply_message_content, $received_message){
		require plugin_dir_path(__FILE__) . 'template/message_reply.php';
	}
	
	function reply_post_message($reply_posts, $received_message){
		!is_array($reply_posts) && $reply_posts = array($reply_posts);
		$reply_posts_count = count($reply_posts);
		require plugin_dir_path(__FILE__) . 'template/post_message_reply.php';
	}
	
	function transfer_customer_service($received_message){
		require plugin_dir_path(__FILE__) . 'template/transfer_customer_service.php';
	}
	
}

// create place holder page, refresh rewrite on plugin active
register_activation_hook(__FILE__, function(){
	flush_rewrite_rules();
	wp_insert_post(array('post_type'=>'page', 'post_title'=>'WeChat Placeholder', 'post_name'=>'wx', 'post_status'=>'publish'));
});

register_deactivation_hook(__FILE__, function(){
	flush_rewrite_rules();
	$page = get_posts(array('name'=>'wx', 'post_type'=>'page'))[0];
	wp_delete_post($page->ID, true);
});


