<?php
$weixin = new WeixinAPI();
$user_info = $weixin->get_oauth_token();

$nonce_str = sha1(NONCE_KEY . $user_info->openid);
$timestamp = time();

if(isset($_GET['from_username'])){
	$user = get_user_by('login', $_GET['from_username']);
	if(!$user){
		exit('invalid invite username');
	}
	$invited_user_ids = get_user_meta($user->ID, 'invited_user_id');
	if($current_user->ID !== $user->ID && !in_array($current_user->ID, $invited_user_ids)){
		add_user_meta($user->ID, 'invited_user_id', $current_user->ID);
	}
}

if(isset($_POST['attend'])){
	wp_update_user(array('ID' => $current_user->ID, 'user_email' => $_POST['email'], 'display_name' => $_POST['name']));
	update_user_meta($current_user->ID, 'phone', $_POST['phone']);
	update_user_meta($current_user->ID, 'attended', date('Y-m-d H:i:s'));
}

?>
﻿<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title><?php bloginfo('site_name'); ?></title>
		<meta charset="utf-8">
		<meta name="apple-touch-fullscreen" content="YES">
		<meta name="format-detection" content="telephone=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta http-equiv="Expires" content="-1">
		<meta http-equiv="pragram" content="no-cache">
		<link rel="stylesheet" type="text/css" href="<?= get_stylesheet_directory_uri() ?>/css/main.css">
		<link rel="stylesheet" type="text/css" href="<?= get_stylesheet_directory_uri() ?>/css/endpic.css">
		<script type="text/javascript" src="<?= get_stylesheet_directory_uri() ?>/js/offline.js"></script>
		<meta name="viewport" content="width=640, user-scalable=no, target-densitydpi=device-dpi">
	</head>

	<body class="s-bg-ddd pc no-3d" style="-webkit-user-select: none;">
		<section class="u-alert">
			<img style="display:none;" src="<?= get_stylesheet_directory_uri() ?>/images/loading_large.gif">
			<div class="alert-loading z-move">
				<div class="cycleWrap">	<span class="cycle cycle-1"></span>
					<span class="cycle cycle-2"></span><span class="cycle cycle-3"></span><span class="cycle cycle-4"></span>
				</div>
				<div class="lineWrap">	<span class="line line-1"></span><span class="line line-2"></span><span class="line line-3"></span>
				</div>
			</div>
		</section>
		<section class="u-arrow">
			<p class="css_sprite01"></p>
		</section>
		<section class="p-ct transformNode-2d" style="height: 907px;">
			<div class="translate-back" style="height: 907px;">
				<?php if(!isset($_POST['attend'])){ ?>
				<div class="m-page m-fengye" data-page-type="info_pic3" data-statics="info_pic3" style="height:70%;">
					<div class="page-con lazy-finish" data-position="50% 50%" data-size="cover" style="background-image: url(<?= get_stylesheet_directory_uri() ?>/images/page-1.jpg); background-size: cover; height: 909px; background-position: 50% 50%;"></div>
				</div>
				<div class="m-page m-bigTxt f-hide" data-page-type="bigTxt" data-statics="info_list" style="height:70%;">
					<div class="page-con j-txtWrap lazy-finish" data-position="50% 50%" data-size="cover" style="background-image: url(<?= get_stylesheet_directory_uri() ?>/images/page-2.jpg); background-size: cover; background-position: 50% 50%;"></div>
				</div>
				<div class="m-page m-bigTxt f-hide" data-page-type="bigTxt" data-statics="info_list" style="height:70%;">
					<div class="page-con j-txtWrap lazy-finish" data-position="50% 50%" data-size="cover" style="background-image: url(<?= get_stylesheet_directory_uri() ?>/images/page-3.jpg); background-size: cover; background-position: 50% 50%;"></div>
				</div>
				<div id="page-4" class="m-page m-bigTxt f-hide" data-page-type="bigTxt" data-statics="info_list" style="height: 907px;">
					<div class="page-con j-txtWrap lazy-finish" data-position="50% 50%" data-size="cover" style="background-image: url(<?= get_stylesheet_directory_uri() ?>/images/page-4.jpg); background-size: cover; background-position: 50% 50%;">
						<div class="text">
							<p>1、  关注“艇好玩”微信公众号并注册，将有机会赢取万元游艇盛筵大奖；</p>
							<p>2、  “朋友帮”——分享至朋友圈邀好友一起参与，每引入1名好友将增加抽奖机会一次；每引入10名好友，将享有“艇好玩”初次订单10%折扣优惠，累积最高5折封顶。</p>
						</div>
					</div>
				</div>
				<div id="page-5" class="m-page m-bigTxt f-hide" data-page-type="bigTxt" data-statics="info_list" style="height: 907px;">
					<div class="page-con j-txtWrap lazy-finish" data-position="50% 50%" data-size="cover" style="background-image: url(<?= get_stylesheet_directory_uri() ?>/images/page-4.jpg); background-size: cover; background-position: 50% 50%;">
						<div class="form">
							<form method="post">
								<input type="text" name="name" placeholder="姓名">
								<input type="text" name="phone" placeholder="手机">
								<input type="text" name="email" placeholder="邮箱">
								<button type="submit" name="attend">提&nbsp;&nbsp;&nbsp;&nbsp;交</button>
							</form>
							<div class="help">
								<p>提交资料获得抽奖机会</p>
								<p>分享至朋友圈，凡好友参与即可增加中奖几率</p>
							</div>
						</div>

					</div>
				</div>
				<?php }else{ ?>
				<div id="page-6" class="m-page m-fengye" data-page-type="bigTxt" data-statics="info_list" style="height:70%;">
					<div class="page-con j-txtWrap lazy-finish" data-position="50% 50%" data-size="cover" style="background-image: url(<?= get_stylesheet_directory_uri() ?>/images/page-5.jpg); background-size: cover; background-position: 50% 50%;"></div>
				</div>
				<?php } ?>
			</div>
		</section>

		<script src="<?= get_stylesheet_directory_uri() ?>/js/init.mix.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?= get_stylesheet_directory_uri() ?>/js/coffee.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?= get_stylesheet_directory_uri() ?>/js/99_main.js" type="text/javascript" charset="utf-8"></script>

		<div style="text-align:center;margin:50px 0; font:normal 14px/24px 'MicroSoft YaHei';"></div>

		<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>		
		<script type="text/javascript">
			
			wx.config({
				debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
				appId: '<?=$weixin->app_id?>', // 必填，公众号的唯一标识
				timestamp: '<?=$timestamp?>', // 必填，生成签名的时间戳
				nonceStr: '<?=$nonce_str?>', // 必填，生成签名的随机串
				signature: '<?=$weixin->generate_jsapi_sign($nonce_str, $timestamp)?>',// 必填，签名，见附录1
				jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
			});
			
			var openid = '<?=$user_info->openid?>';
			
			wx.ready(function(){
				wx.onMenuShareTimeline({
					title: '<?=get_bloginfo('site_name')?>', // 分享标题
					link: '<?=site_url()?>/?from_username=<?=$user_info->openid?>', // 分享链接
					imgUrl: '', // 分享图标
					success: function () {
						
					},
					cancel: function () {
						
					}
				});
				
				wx.onMenuShareAppMessage({
					title: '<?=get_bloginfo('site_name')?>', // 分享标题
					desc: '<?=site_url()?>/?from_username=<?=$user_info->openid?>',
					link: '<?=site_url()?>/?from_username=<?=$user_info->openid?>', // 分享链接
					imgUrl: '', // 分享图标
					success: function () {
						
					},
					cancel: function () {
						
					}
				});
			});
			
		</script>
	</body>
</html>