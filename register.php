<?php

/**
 * ECSHOP 注册
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: register.php 17217 2015-08-07 06:29:08Z niqingyang $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');

/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$back_act = '';

/* 如果是显示页面，对页面进行相应赋值 */
if(true)
{
	assign_template();
	$position = assign_ur_here(0, $_LANG['user_center']);
	$smarty->assign('page_title', $position['title']); // 页面标题
	$smarty->assign('ur_here', $position['ur_here']);
	$sql = "SELECT value FROM " . $ecs->table('shop_config') . " WHERE id = 419";
	$row = $db->getRow($sql);
	$car_off = $row['value'];
	$smarty->assign('car_off', $car_off);
	/* 是否显示积分兑换 */
	if(! empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
	{
		$smarty->assign('show_transform_points', 1);
	}
	$smarty->assign('helps', get_shop_help()); // 网店帮助
	$smarty->assign('data_dir', DATA_DIR); // 数据目录
	$smarty->assign('action', $action);
	$smarty->assign('lang', $_LANG);
}

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_default";
}

call_user_func($function_name);

/* 路由 */

/* 发送注册邮箱验证码到邮箱 */
function action_send_email_code ()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 载入语言文件 */
	require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');
	
	require_once (ROOT_PATH . 'includes/lib_validate_record.php');
	
	$email = trim($_REQUEST['email']);
	
	if(empty($email))
	{
		exit("邮箱不能为空");
		return;
	}
	else if(! is_email($email))
	{
		exit("邮箱格式不正确");
		return;
	}
	else if(check_validate_record_exist($email))
	{
		
		$record = get_validate_record($email);
		
		/**
		 * 检查是过了限制发送邮件的时间
		 */
		if(time() - $record['last_send_time'] < 60)
		{
			echo ("每60秒内只能发送一次注册邮箱验证码，请稍候重试");
			return;
		}
	}
	
	require_once (ROOT_PATH . 'includes/lib_passport.php');
	
	/* 设置验证邮件模板所需要的内容信息 */
	$template = get_mail_template('reg_email_code');
	// 生成邮箱验证码
	$email_code = rand_number(6);
	
	$GLOBALS['smarty']->assign('email_code', $email_code);
	$GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
	$GLOBALS['smarty']->assign('send_date', date($GLOBALS['_CFG']['date_format']));
	
	$content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);
	
	/* 发送激活验证邮件 */
	$result = send_mail($email, $email, $template['template_subject'], $content, $template['is_html']);
	if($result)
	{
		// 保存验证码到Session中
		$_SESSION[VT_EMAIL_REGISTER] = $email;
		// 保存验证记录
		save_validate_record($email, $email_code, VT_EMAIL_REGISTER, time(), time() + 30 * 60);
		
		echo 'ok';
	}
	else
	{
		echo '注册邮箱验证码发送失败';
	}
}

/* 发送验证码到手机 */
function action_send_mobile_code ()
{	
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 载入语言文件 */
	require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');
	
	require_once (ROOT_PATH . 'includes/lib_validate_record.php');
	
	$mobile_phone = trim($_REQUEST['mobile_phone']);
	
	if(empty($mobile_phone))
	{
		exit("手机号不能为空");
		return;
	}
	else if(! is_mobile_phone($mobile_phone))
	{
		exit("手机号格式不正确");
		return;
	}
	else if(check_validate_record_exist($mobile_phone))
	{
		// 获取数据库中的验证记录
		$record = get_validate_record($mobile_phone);
		
		/**
		 * 检查是过了限制发送短信的时间
		 */
		$last_send_time = $record['last_send_time'];
		$expired_time = $record['expired_time'];
		$create_time = $record['create_time'];
		$count = $record['count'];
		
		// 每天每个手机号最多发送的验证码数量
		$max_sms_count = 10;
		// 发送最多验证码数量的限制时间，默认为24小时
		$max_sms_count_time = 60 * 60 * 24;
		
		if((time() - $last_send_time) < 60)
		{
			echo ("每60秒内只能发送一次短信验证码，请稍候重试");
			return;
		}
		else if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count)
		{
			echo ("您发送验证码太过于频繁，请稍后重试！");
			return;
		}
		else
		{
			$count ++;
		}
	}
	
	require_once (ROOT_PATH . 'includes/lib_passport.php');
	
	// 设置为空
	$_SESSION['mobile_register'] = array();
	
	require_once (ROOT_PATH . 'sms/sms.php');
	
	// 生成6位短信验证码
	$mobile_code = rand_number(6);
	// 短信内容
	$content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['shop_name'], $mobile_code, $GLOBALS['_CFG']['shop_name']);
	
	/* 发送激活验证邮件 */
	// $result = true;
	$result = sendSMS($mobile_phone, $content);
	if($result)
	{
		
		if(! isset($count))
		{
			$ext_info = array(
				"count" => 1
			);
		}
		else
		{
			$ext_info = array(
				"count" => $count
			);
		}
		
		// 保存手机号码到SESSION中
		$_SESSION[VT_MOBILE_REGISTER] = $mobile_phone;
		// 保存验证信息
		save_validate_record($mobile_phone, $mobile_code, VT_MOBILE_REGISTER, time(), time() + 30 * 60, $ext_info);
		echo 'ok';
	}
	else
	{
		echo '短信验证码发送失败';
	}
}

/**
 * 验证邮箱是否可以注册，true-已存在，不能注册 false-不存在可以注册
 */
function action_check_email_exist ()
{
	$_LANG = $GLOBALS['_LANG'];
	$_CFG = $GLOBALS['_CFG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	$email = empty($_POST['email']) ? '' : $_POST['email'];
	
	$user = $GLOBALS['user'];
	
	if($user->check_email($email))
	{
		echo 'true';
	}
	else
	{
		echo 'false';
	}
}

function action_check_mobile_exist ()
{
	$_LANG = $GLOBALS['_LANG'];
	$_CFG = $GLOBALS['_CFG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	$mobile = empty($_POST['mobile']) ? '' : $_POST['mobile'];
	
	$user = $GLOBALS['user'];
	
	if($user->check_mobile_phone($mobile))
	{
		echo 'true';
	}
	else
	{
		echo 'false';
	}
}

/**
 * 显示会员注册界面
 */
function action_default ()
{	
	// 获取全局变量
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	if((! isset($back_act) || empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
	{
		$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
	}
	
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	$smarty->assign('extend_info_list', $extend_info_list);
	
	/* 验证码相关设置 */
	if((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
	{
		$smarty->assign('enabled_captcha', 1);
		$smarty->assign('rand', mt_rand());
	}
	/* 还原选择的省地区联动 by liuweitao */
	if(isset($_SESSION['register_info'])){
	    $sel_country = intval($_SESSION['register_info']['country']);
	    $smarty->assign('sel_country', $sel_country);
	    if($sel_country > 0){
	        $smarty->assign('province_list', get_regions(1, $sel_country));
	    }
	    $sel_province = intval($_SESSION['register_info']['province']);
	    $smarty->assign('sel_province', $sel_province);
	    if($sel_province > 0){
	        $smarty->assign('city_list', get_regions(2, $sel_province));
	    }
	    $sel_city = intval($_SESSION['register_info']['city']);
	    $smarty->assign('sel_city', $sel_city);
	    if($sel_city > 0){
	        $smarty->assign('district_list', get_regions(3, $sel_city));
	    }
	    $smarty->assign('sel_district', intval($_SESSION['register_info']['district']));
	}
	
	/* 密码提示问题 */
	$smarty->assign('passwd_questions', $_LANG['passwd_questions']);
	/* 代码增加_start By www.68ecshop.com */
	$smarty->assign('sms_register', $_CFG['sms_register']);
	/* 代码增加_end By www.68ecshop.com */
	/* 代码增加_star By www.68ecshop.com */
	$smarty->assign('sms_register', $_CFG['sms_register']);
	/* 代码增加_end By www.68ecshop.com */
	/* 增加是否关闭注册 */
	$smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
	// 登陆注册-注册类型
	//$register_type = empty($_REQUEST['register_type']) ? 'mobile' : $_REQUEST['register_type'];
	$register_type = 'mobile'; //默认设为手机号
	if($register_type != 'email' && $register_type != 'mobile')
	{
		$register_type = 'mobile';
	}
	$smarty->assign('country_list', get_regions());
	
	$smarty->assign('register_type', $register_type);
	// $smarty->assign('back_act', $back_act);
	$smarty->display('user_register.dwt');
}

/**
 * liuweitao
 * 提交资料审核
 */
function action_register_preview()
{
    $_SESSION['register_info'] = $_POST; //存储post过来的数据，以便还原地区四级联动
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    if(empty($_POST['agreement'])){
        show_message($_LANG['passport_js']['agreement']);
    }
    foreach($_POST as $value){
        if($value) continue;
        show_message('请将资料填写完整后再提交，所有信息都必须填写');
    }
    if($_FILES['img_bank_card']['size'] == 0 || $_FILES['face_card'] == 0 || $_FILES['back_card'] == 0){
        show_message("请上传银行卡正面照和身份证正反面！");
    }
    /* 手机验证码检查 */  
    require_once (ROOT_PATH . 'includes/lib_validate_record.php');
    $record = get_validate_record($_POST['mobile']);
    $session_mobile_phone = $_SESSION[VT_MOBILE_REGISTER];    
    if($session_mobile_phone != $_POST['mobile']){   // 检查发送短信验证码的手机号码和提交的手机号码是否匹配
        show_message($_LANG['mobile_phone_changed']);
    }else if($record['record_code'] != $_POST['mobile_code']){  // 检查验证码是否正确
        show_message($_LANG['invalid_mobile_phone_code']);
    }else if($record['expired_time'] < time()){ // 检查过期时间
        show_message($_LANG['invalid_mobile_phone_code']);
    }
    remove_validate_record($_POST['mobile']);/* 删除注册的验证记录 */
    /* 图片验证码检查 */
    if((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0){
        include_once ('includes/cls_captcha.php');
        $captcha = new captcha();        	
        if(! $captcha->check_word(trim($_POST['captcha']))){
            show_message($_LANG['invalid_captcha']);
        }
    }
    include_once (ROOT_PATH . '/includes/cls_image.php');
    $image = new cls_image($_CFG['bgcolor']);
    $img_bank_card = $image->upload_image($_FILES['img_bank_card'], 'imgbankcard/' . date('Ym'));
    $face_card = $image->upload_image($_FILES['face_card'], 'imgidcard/' . date('Ym'));
    $back_card = $image->upload_image($_FILES['back_card'], 'imgidcard/' . date('Ym'));
    $_SESSION['register_info']['img_bank_card'] = $img_bank_card;
    $_SESSION['register_info']['face_card'] = $face_card;
    $_SESSION['register_info']['back_card'] = $back_card;   
    $smarty->assign('country', get_region_by_id($_POST['country']));
    $smarty->assign('province', get_region_by_id($_POST['province']));
    $smarty->assign('city', get_region_by_id($_POST['city']));
    $smarty->assign('district', get_region_by_id($_POST['district']));
    $smarty->assign('register_info', $_SESSION['register_info']);
    $smarty->display('user_register_preview.dwt');
}

/**
 * 注册会员的处理
 */
function action_register ()
{
    if(empty($_SESSION['register_info'])){
        show_message('请正确填写审核资料', '提交审请资料', 'register.php');
    }
	// 获取全局变量
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	/* 增加是否关闭注册 */
	if($_CFG['shop_reg_closed'])
	{
		$smarty->assign('action', 'register');
		$smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
		$smarty->display('user_passport.dwt');
	}
	else
	{
	    $registerInfo = $_SESSION['register_info'];
		include_once (ROOT_PATH . 'includes/lib_passport.php');
		
		//$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$realname = isset($registerInfo['real_name']) ? trim($registerInfo['real_name']) : '';
		
		//$password = isset($_POST['password']) ? trim($_POST['password']) : '';
		//$email = isset($_POST['email']) ? trim($_POST['email']) : '';
		$other['msn'] = isset($_POST['extend_field1']) ? $_POST['extend_field1'] : '';
		$other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
		$other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
		$other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
		$other['mobile_phone'] = isset($_POST['extend_field5']) ? $_POST['extend_field5'] : '';
		$sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
		$passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';
		
		// 注册类型：email、mobile
		//$register_type = isset($_POST['register_type']) ? trim($_POST['register_type']) : '';
		$register_type = 'mobile';		
		$back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';		
		$mobile = $registerInfo['mobile'];
		//$result = register_by_mobile($username, $password, $mobile_phone, $other);
		$result = register_by_realname($realname, $mobile, $registerInfo['sex'], $registerInfo['card'], $registerInfo['bank_card_no'], $registerInfo['country'], $registerInfo['province'], $registerInfo['city'], $registerInfo['district'], $registerInfo['address'], $registerInfo['img_bank_card'], $registerInfo['face_card'], $registerInfo['back_card'], $other);
		if($result)
		{		    
		    unset($_SESSION['register_info']);
		    $user_id = get_user_id_by_mobile($registerInfo['mobile']);
			/* 把新注册用户的扩展信息插入数据库 */
			$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有自定义扩展字段的id
			$fields_arr = $db->getAll($sql);
			
			$extend_field_str = ''; // 生成扩展字段的内容字符串
			foreach($fields_arr as $val)
			{
				$extend_field_index = 'extend_field' . $val['id'];
				if(! empty($_POST[$extend_field_index]))
				{
					$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
					//$extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . compile_str($temp_field_content) . "'),";
					$extend_field_str .= " ('" . $user_id . "', '" . $val['id'] . "', '" . compile_str($temp_field_content) . "'),";
				}
			}
			$extend_field_str = substr($extend_field_str, 0, - 1);
			
			if($extend_field_str) // 插入注册扩展数据
			{
				$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
				$db->query($sql);
			}
			/* 代码增加2014-12-23 by www.68ecshop.com _star */
			// if($_SESSION['tag'] > 0)
			// {
			// $sql = "update " . $GLOBALS['ecs']->table('users') . " set
			// is_validated = 1 where user_id = '" . $_SESSION['user_id'] . "'";
			// $GLOBALS['db']->query($sql);
			// }
			
			// if($other['mobile_phone'] != '')
			// {
			// if($_CFG['sms_register'] == 1)
			// {
			// $sql = "update " . $GLOBALS['ecs']->table('users') . " set
			// validated = 1 where user_id = '" . $_SESSION['user_id'] . "'";
			// $GLOBALS['db']->query($sql);
			// }
			// }
			/* 代码增加2014-12-23 by www.68ecshop.com _end */
			/*
			 * 代码增加_start By www.68ecshop.com
			 * include_once(ROOT_PATH . '/includes/cls_image.php');
			 * $image = new cls_image($_CFG['bgcolor']);
			 * $headimg_original =
			 * $GLOBALS['image']->upload_image($_FILES['headimg'], 'headimg/'.
			 * date('Ym'));
			 *
			 * $thumb_path=DATA_DIR. '/headimg/' . date('Ym').'/' ;
			 * $headimg_thumb = $GLOBALS['image']->make_thumb($headimg_original,
			 * '80', '50', $thumb_path);
			 * $headimg_thumb = $headimg_thumb ? $headimg_thumb :
			 * $headimg_original;
			 * if ($headimg_thumb)
			 * {
			 * $sql = 'UPDATE ' . $ecs->table('users') . " SET
			 * `headimg`='$headimg_thumb' WHERE `user_id`='" .
			 * $_SESSION['user_id'] . "'";
			 * $db->query($sql);
			 * }
			 * 代码增加_end By www.68ecshop.com
			 */
			
			/* 写入密码提示问题和答案 */
			if(! empty($passwd_answer) && ! empty($sel_question))
			{
				//$sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
			    $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $user_id . "'";
				$db->query($sql);
			}
			
			/* 代码增加_start By www.68ecshop.com */
			$now = gmtime();
			if($_CFG['bonus_reg_rand'])
			{
				$sql_bonus_ext = " order by rand() limit 0,1";
			}
			$sql_b = "SELECT type_id FROM " . $ecs->table("bonus_type") . " WHERE send_type='" . SEND_BY_REGISTER . "'  AND send_start_date<=" . $now . " AND send_end_date>=" . $now . $sql_bonus_ext;
			$res_bonus = $db->query($sql_b);
			$kkk_bonus = 0;
			while($row_bonus = $db->fetchRow($res_bonus))
			{
				//$sql = "INSERT INTO " . $ecs->table('user_bonus') . "(bonus_type_id, bonus_sn, user_id, used_time, order_id, emailed)" . " VALUES('" . $row_bonus['type_id'] . "', 0, '" . $_SESSION['user_id'] . "', 0, 0, 0)";
			    $sql = "INSERT INTO " . $ecs->table('user_bonus') . "(bonus_type_id, bonus_sn, user_id, used_time, order_id, emailed)" . " VALUES('" . $row_bonus['type_id'] . "', 0, '" . $user_id . "', 0, 0, 0)";
				$db->query($sql);
				$kkk_bonus = $kkk_bonus + 1;
			}
			if($kkk_bonus)
			{
				$_LANG['register_success'] = '用户名 %s 注册成功,并获得官方赠送的红包礼品';
			}
			/* 代码增加_end By www.68ecshop.com */
			
			/* 判断是否需要自动发送注册邮件 */
			/*if($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email'])
			{
				send_regiter_hash($_SESSION['user_id']);
			}*/
			/*$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
			show_message(sprintf($_LANG['register_success'], $username . $ucdata), array(
				$_LANG['back_up_page'],$_LANG['profile_lnk']
			), array(
				$back_act,'user.php'
			), 'info');*/
			show_message('您的资料已经提交成功，请等待管理员审核');
		}
		else
		{
			$GLOBALS['err']->show($_LANG['sign_up'], 'register.php');
		}
	}
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
}

/**
 * 随机生成指定长度的数字
 *
 * @param number $length        	
 * @return number
 */
function rand_number ($length = 6)
{
	if($length < 1)
	{
		$length = 6;
	}
	
	$min = 1;
	for($i = 0; $i < $length - 1; $i ++)
	{
		$min = $min * 10;
	}
	$max = $min * 10 - 1;
	
	return rand($min, $max);
}

/**
 * 根据手机号生成用户名
 *
 * @param number $length
 * @return number
 */
function generate_username_by_mobile ($mobile)
{

	$username = 'u'.substr($mobile, 0, 3);

	$charts = "ABCDEFGHJKLMNPQRSTUVWXYZ";
	$max = strlen($charts);

	for($i = 0; $i < 4; $i ++)
	{
		$username .= $charts[mt_rand(0, $max)];
	}

	$username .= substr($mobile, -4);
	
	$sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where user_name = '$username'";
	$count = $GLOBALS['db']->getOne($sql);
	if($count > 0)
	{
		return generate_username_by_mobile();
	}

	return $username;
}

/**
 * 根据邮箱地址生成用户名
 *
 * @param number $length
 * @return number
 */
function generate_username ()
{

	$username = 'u'.rand_number(3);

	$charts = "ABCDEFGHJKLMNPQRSTUVWXYZ";
	$max = strlen($charts);

	for($i = 0; $i < 4; $i ++)
	{
		$username .= $charts[mt_rand(0, $max)];
	}

	$username .= rand_number(4);
	
	$sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where user_name = '$username'";
	$count = $GLOBALS['db']->getOne($sql);
	if($count > 0)
	{
		return generate_username();
	}

	return $username;
}

?>