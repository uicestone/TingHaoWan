<?php

// add columns to User panel list page
add_filter('manage_users_columns', function($column) {
	
	$column = array (
		'cb' => '<input type="checkbox" />',
		'username' => '用户名',
		'display_name' => '姓名',
		'email' => '电子邮件',
		'phone' => '手机',
		'invites' => '邀请数',
		'attended' => '报名时间',
	);
    
    return $column;
	
});
// add the data
add_filter('manage_users_custom_column', function ($val, $column_name, $user_id){
    switch ($column_name) {
        case 'phone' :
            return get_user_meta($user_id, 'phone', true);
        case 'display_name' :
			$user = get_userdata($user_id);
            return $user->display_name != $user->user_login ? $user->display_name : '';
		case 'invites' :
			return count(get_user_meta($user_id, 'invited_user_id'));
		case 'attended' :
			return get_user_meta($user_id, 'attended', true);
        default:
    }
    return;
}, 10, 3 );