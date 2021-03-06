<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');
/* 代码增加2014-12-23 by www.68ecshop.com _star */
include_once (ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
/* 代码增加2014-12-23 by www.68ecshop.com _end */

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_list";
}

call_user_func($function_name);

/* 路由 */

/* ------------------------------------------------------ */
// -- 用户帐号列表
/* ------------------------------------------------------ */
function action_list ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	$sql = "SELECT rank_id, rank_name, min_points FROM " . $ecs->table('user_rank') . " ORDER BY min_points ASC ";
	$rs = $db->query($sql);
	
	$ranks = array();
	while($row = $db->FetchRow($rs))
	{
		$ranks[$row['rank_id']] = $row['rank_name'];
	}
	
	$smarty->assign('user_ranks', $ranks);
	$smarty->assign('ur_here', $_LANG['03_users_list']);
	$smarty->assign('action_link', array(
		'text' => $_LANG['04_users_add'],'href' => 'users.php?act=add'
	));
	
	$user_list = user_list();
	
	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');
	
	assign_query_info();
	$smarty->display('users_list.htm');
}

function action_pay()
{
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_table = $ecs->table('users');
    $order_table = $ecs->table('order_info');
    $repay_date = $_GET['date'];
    if(!empty($repay_date) && ($user_id = intval($_GET['uid'])) > 0){ //还款
    	$year = substr($repay_date, 0, 4);
    	$month = trim(substr($repay_date, 4), "年月");
    	$repay_begin_time = strtotime("{$year}-{$month}-01");
    	$repay_end_time = strtotime("{$year}-{$month}-" . date('t', $repay_begin_time)) + 86400;
    	$timestamp = time();
    	$repay_condition = "user_id={$user_id} and pay_status=2 and add_time>={$repay_begin_time} and add_time<{$repay_end_time} and repayment=0";
    	$repay_data = $db->getAll("select sum(integral_money) as money, pay_note from {$order_table} where {$repay_condition} group by pay_note ");
    	$credit_money = 0;
    	$pay_money = 0;
    	foreach($repay_data as $row){
    		if($row['pay_note'] == 'cash') $credit_money += $row['money'];
    		else $pay_money += $row['money'];
		}
		$fields = array();
		if($credit_money > 0){   //提现额度
            $fields[] = "credit_line=credit_line+{$credit_money}";
		}
		if($pay_money > 0){  //消费额度
			$fields[] = "pay_points=pay_points+{$pay_money}";
		}
		if(count($fields) > 0){
            $db->query("update {$user_table} set " . implode(',', $fields) . " where user_id={$user_id}");
		}
    	$db->query("update {$order_table} set repayment={$timestamp} where {$repay_condition}");
	}
    $smarty = $GLOBALS['smarty'];
    if(!empty($_POST['begin_date']) && !empty($_POST['end_date'])){
    	$begin_time = strtotime($_POST['begin_date']);
    	$end_time = strtotime($_POST['end_date']);
	}elseif(!empty($_POST['begin_date']) && empty($_POST['end_date'])){
    	$begin_time = strtotime($_POST['begin_date']);
        $end_time = time();
	}elseif(empty($_POST['begin_date']) && !empty($_POST['end_date'])){
		$begin_time = 0;
		$end_time = strtotime($_POST['end_date']);
	}else{
        $day = date('j');
        $begin_date = date('Y-m', strtotime("-{$day} day")) . '-01';
        $end_date = date('Y-m-d', strtotime('-' . ($day - 1) . ' day'));
        $begin_time = strtotime($begin_date);
        $end_time = strtotime($end_date);
	}
    $user_list = array();
	$condition = "pay_status=2 and add_time>={$begin_time} and add_time<{$end_time} and repayment=0";  //PS_PAYED = 2
	$order_list = $db->getAll("select user_id,add_time,integral_money from {$order_table} where {$condition}");
	foreach($order_list as $order){
		$year_month = date('Y年m月', $order['add_time']);
		if(!isset($user_list[$year_month])){
			$user_list[$year_month] = array();
		}
		if(isset($user_list[$year_month][$order['user_id']])){
			$repay_money = $user_list[$year_month][$order['user_id']]['repay_money'];
            $repay_money += $order['integral_money'];
            $user_list[$year_month][$order['user_id']]['repay_money'] = $repay_money = sprintf("%.2f", $repay_money);
		}else{
            $user = $db->getRow("select user_id, user_name,real_name,mobile_phone from {$user_table} where user_id ={$order['user_id']}");
            $user['repay_money'] = $order['integral_money'];
            $user_list[$year_month][$order['user_id']] = $user;
		}
	}
    $smarty->assign('record_list', $user_list);
    $smarty->assign('full_page', 1);
    $smarty->display('pay_list.htm');
}
function action_detail()
{
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $smarty = $GLOBALS['smarty'];

    $query_date = $_GET['date'];
    $user_id = intval($_REQUEST['uid']);
    $order_table = $ecs->table('order_info');
    if(!empty($query_date)){
        $year = substr($query_date, 0, 4);
        $month = trim(substr($query_date, 4), "年月");
        $begin_time = strtotime("{$year}-{$month}-01");
        $end_time = strtotime("{$year}-{$month}-" . date('t', $repay_begin_time)) + 86400;
	}elseif(!empty($_POST['begin_date']) && !empty($_POST['end_date'])){
        $begin_time = strtotime($_POST['begin_date']);
        $end_time = strtotime($_POST['end_date']);
    }elseif(!empty($_POST['begin_date']) && empty($_POST['end_date'])){
        $begin_time = strtotime($_POST['begin_date']);
        $end_time = time();
    }elseif(empty($_POST['begin_date']) && !empty($_POST['end_date'])){
        $begin_time = 0;
        $end_time = strtotime($_POST['end_date']);
    }else{
        $day = date('j');
        $begin_date = date('Y-m', strtotime("-{$day} day")) . '-01';
        $end_date = date('Y-m-d', strtotime('-' . ($day - 1) . ' day'));
        $begin_time = strtotime($begin_date);
        $end_time = strtotime($end_date);
    }
    $record_list = array();
    $condition = "user_id={$user_id} and pay_status=2 and add_time>={$begin_time} and add_time<{$end_time} and repayment=0";  //PS_PAYED = 2
    $order_list = $db->getAll("select order_id, order_sn,add_time, pay_name, surplus, goods_amount, integral_money,add_time,pay_note from {$order_table} where {$condition}");
    $total_money = array();
    foreach($order_list as $order){
        $year_month = date('Y年m月', $order['add_time']);
        if(!isset($total_money[$year_month])){
        	$total_money[$year_month] = 0;
		}
        if(!isset($record_list[$year_month])){
            $record_list[$year_month] = array();
        }
        if($order['pay_note'] == 'cash'){
            $order['pay_note_name'] = '提现';
		}elseif($order['pay_note'] == ''){
        	$order['pay_note_name'] = '终端购物';
		}else{
			$order['pay_note_name'] = '商城购物';
		}
        $order['add_time'] = date('Y-m-d H:i:s', $order['add_time']);
        $order['surplus'] = sprintf("%.2f", $order['surplus']);
        $order['goods_amount'] = sprintf("%.2f", $order['goods_amount']);
        $total_money[$year_month] += $order['integral_money'];
        $total_money[$year_month] = sprintf("%.2f", $total_money[$year_month]);
        $order['integral_money'] = sprintf("%.2f", $order['integral_money']);
        $record_list[$year_month][] = $order;
    }
    $smarty->assign('total_money', $total_money);
    $smarty->assign('user_id', $user_id);
    $smarty->assign('record_list', $record_list);
    $smarty->assign('full_page', 1);
    $smarty->display('pay_detail.htm');
}
/* ------------------------------------------------------ */
// -- ajax返回用户列表
/* ------------------------------------------------------ */
function action_query ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	$user_list = user_list();
	
	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);
	
	$sort_flag = sort_flag($user_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
	make_json_result($smarty->fetch('users_list.htm'), '', array(
		'filter' => $user_list['filter'],'page_count' => $user_list['page_count']
	));
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_add ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	
	$user = array(
		'rank_points' => $_CFG['register_points'],'pay_points' => $_CFG['register_points'],'sex' => 0,'credit_line' => 0
	);
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	$smarty->assign('extend_info_list', $extend_info_list);
	
	$smarty->assign('ur_here', $_LANG['04_users_add']);
	$smarty->assign('action_link', array(
		'text' => $_LANG['03_users_list'],'href' => 'users.php?act=list'
	));
	$smarty->assign('form_action', 'insert');
	$smarty->assign('user', $user);
	$smarty->assign('special_ranks', get_rank_list(true));
	
	$smarty->assign('lang', $_LANG);
	$smarty->assign('country_list', get_regions());
	$province_list = get_regions(1, $row['country']);
	$city_list = get_regions(2, $row['province']);
	$district_list = get_regions(3, $row['city']);
	
	$smarty->assign('province_list', $province_list);
	$smarty->assign('city_list', $city_list);
	$smarty->assign('district_list', $district_list);
	
	assign_query_info();
	$smarty->display('user_info.htm');
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_insert ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	$username = empty($_POST['username']) ? '' : trim($_POST['username']);
	$password = empty($_POST['password']) ? '' : trim($_POST['password']);
	$email = empty($_POST['email']) ? '' : trim($_POST['email']);
	$sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
	$sex = in_array($sex, array(
		0, 1, 2
	)) ? $sex : 0;
	$birthday = $_POST['birthdayYear'] . '-' . $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
	$rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
	$credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
	$real_name = empty($_POST['real_name']) ? '' : trim($_POST['real_name']);
	$card = empty($_POST['card']) ? '' : trim($_POST['card']);
	$country = $_POST['country'];
	$province = $_POST['province'];
	$city = $_POST['city'];
	$district = $_POST['district'];
	$address = empty($_POST['address']) ? '' : trim($_POST['address']);
	$status = $_POST['status'];
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	$users = & init_users();
	
	if(! $users->add_user($username, $password, $email))
	{
		/* 插入会员数据失败 */
		if($users->error == ERR_INVALID_USERNAME)
		{
			$msg = $_LANG['username_invalid'];
		}
		elseif($users->error == ERR_USERNAME_NOT_ALLOW)
		{
			$msg = $_LANG['username_not_allow'];
		}
		elseif($users->error == ERR_USERNAME_EXISTS)
		{
			$msg = $_LANG['username_exists'];
		}
		elseif($users->error == ERR_INVALID_EMAIL)
		{
			$msg = $_LANG['email_invalid'];
		}
		elseif($users->error == ERR_EMAIL_NOT_ALLOW)
		{
			$msg = $_LANG['email_not_allow'];
		}
		elseif($users->error == ERR_EMAIL_EXISTS)
		{
			$msg = $_LANG['email_exists'];
		}
		else
		{
			// die('Error:'.$users->error_msg());
		}
		sys_msg($msg, 1);
	}
	
	/* 注册送积分 */
	if(! empty($GLOBALS['_CFG']['register_points']))
	{
		log_account_change($_SESSION['user_id'], 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $_LANG['register_points']);
	}
	
	/* 把新注册用户的扩展信息插入数据库 */
	$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有扩展字段的id
	$fields_arr = $db->getAll($sql);
	
	$extend_field_str = ''; // 生成扩展字段的内容字符串
	$user_id_arr = $users->get_profile_by_name($username);
	foreach($fields_arr as $val)
	{
		$extend_field_index = 'extend_field' . $val['id'];
		if(! empty($_POST[$extend_field_index]))
		{
			$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
			$extend_field_str .= " ('" . $user_id_arr['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
		}
	}
	$extend_field_str = substr($extend_field_str, 0, - 1);
	
	if($extend_field_str) // 插入注册扩展数据
	{
		$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
		$db->query($sql);
	}
	
	/* 更新会员的其它信息 */
	$other = array();
	$other['credit_line'] = $credit_line;
	$other['user_rank'] = $rank;
	$other['sex'] = $sex;
	$other['birthday'] = $birthday;
	$other['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));
	
	$other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
	$other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
	$other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
	$other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
	$other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';
	
	$db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
	if(isset($_FILES['face_card']) && $_FILES['face_card']['tmp_name'] != '')
	{
		$face_card = $image->upload_image($_FILES['face_card']);
		if($face_card === false)
		{
			sys_msg($image->error_msg(), 1, array(), false);
		}
	}
	if(isset($_FILES['back_card']) && $_FILES['back_card']['tmp_name'] != '')
	{
		$back_card = $image->upload_image($_FILES['back_card']);
		if($back_card === false)
		{
			sys_msg($image->error_msg(), 1, array(), false);
		}
	}
	
	$sql = "update " . $ecs->table('users') . " set `real_name`='$real_name',`card`='$card',`country`='$country',`province`='$province',`city`='$city',`district`='$district',`address`='$address',`status`='$status' where user_name = '" . $username . "'";
	$db->query($sql);
	
	if($face_card != '')
	{
		$sql = "update " . $ecs->table('users') . " set `face_card` = '$face_card' where user_name = '" . $username . "'";
		$db->query($sql);
	}
	if($back_card != '')
	{
		$sql = "update " . $ecs->table('users') . " set `back_card` = '$back_card' where user_name = '" . $username . "'";
		$db->query($sql);
	}
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	/* 记录管理员操作 */
	admin_log($_POST['username'], 'add', 'users');
	
	/* 提示信息 */
	$link[] = array(
		'text' => $_LANG['go_back'],'href' => 'users.php?act=list'
	);
	sys_msg(sprintf($_LANG['add_success'], htmlspecialchars(stripslashes($_POST['username']))), 0, $link);
}

/* ------------------------------------------------------ */
// -- 编辑用户帐号
/* ------------------------------------------------------ */
function action_edit ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	
	$sql = "SELECT u.user_name, u.sex, u.birthday, u.pay_points, u.rank_points, u.user_rank , u.user_money, u.frozen_money, u.credit_line, u.parent_id, u2.user_name as parent_username, u.qq, u.msn, u.office_phone, u.home_phone,u.family1_name,u.family1_mobile,u.family2_name,u.family2_mobile,u.mobile_phone" . " FROM " . $ecs->table('users') . " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id WHERE u.user_id='$_GET[id]'";
	
	$row = $db->GetRow($sql);
	$row['user_name'] = addslashes($row['user_name']);
	$users = & init_users();
	$user = $users->get_user_info($row['user_name']);
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
	$sql = "SELECT u.user_id, u.sex, u.birthday, u.pay_points, u.rank_points, u.user_rank , u.user_money, u.frozen_money, u.credit_line, u.parent_id, u2.user_name as parent_username, u.qq, u.msn,
    u.office_phone, u.home_phone, u.mobile_phone,u.real_name,u.card,u.bank_card_no,u.img_bank_card,u.face_card,u.back_card,u.family1_name,u.family1_mobile,u.family2_name,u.family2_mobile,u.country,u.province,u.city,u.district,u.address,u.status " . " FROM " . $ecs->table('users') . " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id WHERE u.user_id='$_GET[id]'";
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	
	$row = $db->GetRow($sql);
	
	if($row)
	{
		$user['user_id'] = $row['user_id'];
		$user['sex'] = $row['sex'];
		$user['birthday'] = date($row['birthday']);
		$user['pay_points'] = $row['pay_points'];
		$user['rank_points'] = $row['rank_points'];
		$user['user_rank'] = $row['user_rank'];
		$user['user_money'] = $row['user_money'];
		$user['frozen_money'] = $row['frozen_money'];
		$user['credit_line'] = $row['credit_line'];
		$user['formated_user_money'] = price_format($row['user_money']);
		$user['formated_frozen_money'] = price_format($row['frozen_money']);
		$user['formated_credit_line'] = price_format($row['credit_line']);
		$user['parent_id'] = $row['parent_id'];
		$user['parent_username'] = $row['parent_username'];
		$user['qq'] = $row['qq'];
		$user['msn'] = $row['msn'];
		$user['office_phone'] = $row['office_phone'];
		$user['home_phone'] = $row['home_phone'];
		$user['mobile_phone'] = $row['mobile_phone'];
		/* 代码增加2014-12-23 by www.68ecshop.com _star */
		$user['real_name'] = $row['real_name'];
		$user['card'] = $row['card'];
		$user['face_card'] = $row['face_card'];
		$user['back_card'] = $row['back_card'];
		$user['country'] = $row['country'];
		$user['province'] = $row['province'];
		$user['city'] = $row['city'];
		$user['district'] = $row['district'];
		$user['address'] = $row['address'];
		$user['status'] = $row['status'];
		/* 代码增加2014-12-23 by www.68ecshop.com _end */
		/* add by liuweitao start */
		$user['bank_card_no'] = $row['bank_card_no'];
		$user['img_bank_card'] = $row['img_bank_card'];
		$user['family1_name'] = $row['family1_name'];
		$user['family1_mobile'] = $row['family1_mobile'];
		$user['family2_name'] = $row['family2_name'];
		$user['family2_mobile'] = $row['family2_mobile'];
		$user['formated_pay_points'] = price_format($row['pay_points']);
		/* add by liuweitao end */
	}
	else
	{
		$link[] = array(
			'text' => $_LANG['go_back'],'href' => 'users.php?act=list'
		);
		sys_msg($_LANG['username_invalid'], 0, $links);
		// $user['sex'] = 0;
		// $user['pay_points'] = 0;
		// $user['rank_points'] = 0;
		// $user['user_money'] = 0;
		// $user['frozen_money'] = 0;
		// $user['credit_line'] = 0;
		// $user['formated_user_money'] = price_format(0);
		// $user['formated_frozen_money'] = price_format(0);
	}
	
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	
	$sql = 'SELECT reg_field_id, content ' . 'FROM ' . $ecs->table('reg_extend_info') . " WHERE user_id = $user[user_id]";
	$extend_info_arr = $db->getAll($sql);
	
	$temp_arr = array();
	foreach($extend_info_arr as $val)
	{
		$temp_arr[$val['reg_field_id']] = $val['content'];
	}
	
	foreach($extend_info_list as $key => $val)
	{
		switch($val['id'])
		{
			case 1:
				$extend_info_list[$key]['content'] = $user['msn'];
				break;
			case 2:
				$extend_info_list[$key]['content'] = $user['qq'];
				break;
			case 3:
				$extend_info_list[$key]['content'] = $user['office_phone'];
				break;
			case 4:
				$extend_info_list[$key]['content'] = $user['home_phone'];
				break;
			case 5:
				$extend_info_list[$key]['content'] = $user['mobile_phone'];
				break;
			default:
				$extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
		}
	}
	
	$smarty->assign('extend_info_list', $extend_info_list);
	
	/* 当前会员推荐信息 */
	$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
	$smarty->assign('affiliate', $affiliate);
	
	empty($affiliate) && $affiliate = array();
	
	if(empty($affiliate['config']['separate_by']))
	{
		// 推荐注册分成
		$affdb = array();
		$num = count($affiliate['item']);
		$up_uid = "'$_GET[id]'";
		for($i = 1; $i <= $num; $i ++)
		{
			$count = 0;
			if($up_uid)
			{
				$sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
				$query = $db->query($sql);
				$up_uid = '';
				while($rt = $db->fetch_array($query))
				{
					$up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
					$count ++;
				}
			}
			$affdb[$i]['num'] = $count;
		}
		if($affdb[1]['num'] > 0)
		{
			$smarty->assign('affdb', $affdb);
		}
	}
	
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
	$smarty->assign('lang', $_LANG);
	$smarty->assign('country_list', get_regions());
	$province_list = get_regions(1, $row['country']);
	$city_list = get_regions(2, $row['province']);
	$district_list = get_regions(3, $row['city']);
	
	$smarty->assign('province_list', $province_list);
	$smarty->assign('city_list', $city_list);
	$smarty->assign('district_list', $district_list);
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	
	assign_query_info();
	$smarty->assign('ur_here', $_LANG['users_edit']);
	$smarty->assign('action_link', array(
		'text' => $_LANG['03_users_list'],'href' => 'users.php?act=list&' . list_link_postfix()
	));
	$smarty->assign('user', $user);
	$smarty->assign('form_action', 'update');
	$smarty->assign('special_ranks', get_rank_list(false));
	$smarty->display('user_info.htm');
}

/* ------------------------------------------------------ */
// -- 更新用户帐号
/* ------------------------------------------------------ */
function action_update ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	$user_id = intval($_POST['id']);
	if($user_id < 1){
	    sys_msg('用户id非法');
	}
	$username = empty($_POST['username']) ? '' : trim($_POST['username']);
	$user_money = sprintf('%.2f', floatval($_POST['user_money']));
	$pay_points = sprintf('%.2f', floatval($_POST['pay_points']));
    $credit_line = sprintf('%.2f', floatval($_POST['credit_line']));
	if(empty($username)){
	    sys_msg('卡号不能为空');
	}
	if($db->getOne('select count(*) from ' . $ecs->table('users') . " where user_name='{$username}' and user_id != {$user_id}")){
		sys_msg('卡号重复');
	}
    $user_info = $db->getRow("select password,parent_id,mobile_phone,real_name from " . $ecs->table('users') . " where user_id='{$user_id}'");
	/*$password = empty($_POST['password']) ? '' : trim($_POST['password']);
	$email = empty($_POST['email']) ? '' : trim($_POST['email']);
	$mobile_phone = empty($_POST['mobile_phone']) ? '' : trim($_POST['mobile_phone']);
	$sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
	$sex = in_array($sex, array(
		0, 1, 2
	)) ? $sex : 0;
	$birthday = $_POST['birthdayYear'] . '-' . $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];*/
	$rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
	//$credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
	/*$real_name = empty($_POST['real_name']) ? '' : trim($_POST['real_name']);
	$card = empty($_POST['card']) ? '' : trim($_POST['card']);
	$country = $_POST['country'];
	$province = $_POST['province'];
	$city = $_POST['city'];
	$district = $_POST['district'];
	$address = empty($_POST['address']) ? '' : trim($_POST['address']);*/
	$status = $_POST['status'];
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
    $users = & init_users();

	if(empty($user_info['password'])){  //未设置过密码，待审核状态
        $audit_msg_id = null;
        $result = $users->audit_user($user_id, $username, $user_money, $pay_points, $credit_line, $rank, $status);
        require_once (ROOT_PATH . 'sms/sms.php');
        if($result && $status == '1'){  //审核通过
            $msg_template = $db->getOne('select value from ' . $ecs->table('shop_config') . " where code='sms_audit_success'");
            $msg_content = sprintf($msg_template, $user_info['real_name'],($pay_points + $credit_line));
            sendSMS($user_info['mobile_phone'], $msg_content);

            /*$msg_template = $db->getOne('select value from ' . $ecs->table('shop_config') . " where code='sms_audit_success2'");
            $msg_content = sprintf($msg_template, $user_info['real_name'], $user_info['mobile_phone']);
			sendSMS($user_info['mobile_phone'], $msg_content);*/

            if(!empty($user_info['parent_id'])){
                $msg_template = $db->getOne('select value from ' . $ecs->table('shop_config') . " where code='sms_invite_success'");
                $msg_content = sprintf($msg_template, $user_info['mobile_phone']);
                sendSMS($user_info['mobile_phone'], $msg_content);
			}
		}elseif($result && $status == '3'){ //审核不通过
            $msg_content = $db->getOne('select value from ' . $ecs->table('shop_config') . " where code='sms_audit_failed'");
            sendSMS($user_info['mobile_phone'], $msg_content);
		}
	}else{   //已经设置过密码，更新用户信息
		$users->edit_user_by_id($user_id, $username, $user_money, $pay_points, $credit_line, $rank, $status);
	}
	
	// 获取用户邮箱和手机号已经验证信息,如果手机号、邮箱变更则需验证，如果未变化则沿用原来的验证结果
	/*$user = $users->get_profile_by_name($username);
	
	$profile = array(
		'username' => $username,'password' => $password,'email' => $email, 'mobile_phone' => $mobile_phone, 'gender' => $sex,'bday' => $birthday
	);
	
	if($user['email'] != $email){
		$profile['email_validated'] = 0;
	}else{
		$profile['email_validated'] = $user['email_validated'];
	}
	
	if($user['mobile_phone'] != $mobile_phone){
		$profile['mobile_validated'] = 0;
	}else{
		$profile['mobile_validated'] = $user['mobile_validated'];
	}
	
	$result = $users->edit_user($profile, 1);
	
	if(! $result)
	{
		if($users->error == ERR_EMAIL_EXISTS)
		{
			$msg = $_LANG['email_exists'];
		}
		else if($users->error == ERR_MOBILE_PHONE_EXISTS)
		{
			$msg = $_LANG['mobile_phone_exists'];
		}
		else
		{
			$msg = $_LANG['edit_user_failed'];
		}
		sys_msg($msg, 1);
	}
	if(! empty($password))
	{
		$sql = "UPDATE " . $ecs->table('users') . "SET `ec_salt`='0' WHERE user_name= '" . $username . "'";
		$db->query($sql);
	}*/
	/* 代码增加2014-12-23 by www.68ecshop.com _star */
	/*if(isset($_FILES['face_card']) && $_FILES['face_card']['tmp_name'] != '')
	{
		$face_card = $image->upload_image($_FILES['face_card']);
		if($face_card === false)
		{
			sys_msg($image->error_msg(), 1, array(), false);
		}
	}
	if(isset($_FILES['back_card']) && $_FILES['back_card']['tmp_name'] != '')
	{
		$back_card = $image->upload_image($_FILES['back_card']);
		if($back_card === false)
		{
			sys_msg($image->error_msg(), 1, array(), false);
		}
	}*/
	
	//$sql = "update " . $ecs->table('users') . " set `real_name`='$real_name',`card`='$card',`country`='$country',`province`='$province',`city`='$city',`district`='$district',`address`='$address',`status`='$status' where user_name = '" . $username . "'";
	
	//$db->query($sql);
	
	/*if($face_card != '')
	{
		$sql = "update " . $ecs->table('users') . " set `face_card` = '$face_card' where user_name = '" . $username . "'";
		$db->query($sql);
	}
	if($back_card != '')
	{
		$sql = "update " . $ecs->table('users') . " set `back_card` = '$back_card' where user_name = '" . $username . "'";
		$db->query($sql);
	}*/
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	/* 更新用户扩展字段的数据 */
	/*$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有扩展字段的id
	$fields_arr = $db->getAll($sql);
	$user_id_arr = $users->get_profile_by_name($username);
	$user_id = $user_id_arr['user_id'];
	
	foreach($fields_arr as $val) // 循环更新扩展用户信息
	{
		$extend_field_index = 'extend_field' . $val['id'];
		if(isset($_POST[$extend_field_index]))
		{
			$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
			
			$sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
			if($db->getOne($sql)) // 如果之前没有记录，则插入
			{
				$sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
			}
			else
			{
				$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
			}
			$db->query($sql);
		}
	}*/
	
	/* 更新会员的其它信息 */
	/*$other = array();
	$other['credit_line'] = $credit_line;
	$other['user_rank'] = $rank;
	
	$other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
	$other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
	$other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
	$other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';*/
	// $other['mobile_phone'] = isset($_POST['extend_field5']) ?
	// htmlspecialchars(trim($_POST['extend_field5'])) : '';
	
	// dqy add start 2015-1-6
	/** 去掉此处，此处会将手机号码设置为未验证
	$sql = "select mobile_phone from " . $GLOBALS['ecs']->table('users') . " where user_name = '$username'";
	if($GLOBALS['db']->getOne($sql) != $other['mobile_phone'])
	{
		$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET validated = 0 where user_name = '$username'";
		$GLOBALS['db']->query($sql);
	}
	**/
	// dqy add end 2015-1-6
	
	//$db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");
	
	/* 记录管理员操作 */
	admin_log($username, 'edit', 'users');
	
	/* 提示信息 */
	$links[0]['text'] = $_LANG['goto_list'];
	$links[0]['href'] = 'users.php?act=list&' . list_link_postfix();
	$links[1]['text'] = $_LANG['go_back'];
	$links[1]['href'] = 'javascript:history.back()';
	
	sys_msg($_LANG['update_success'], 0, $links);
}

/* ------------------------------------------------------ */
// -- 批量删除会员帐号
/* ------------------------------------------------------ */
function action_batch_remove ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_drop');
	
	if(isset($_POST['checkboxes']))
	{
		$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
		$col = $db->getCol($sql);
		$usernames = implode(',', addslashes_deep($col));
		$count = count($col);
		/* 通过插件来删除用户 */
		$users = & init_users();
		$users->remove_user($col);
		
		admin_log($usernames, 'batch_remove', 'users');
		
		$lnk[] = array(
			'text' => $_LANG['go_back'], 'href' => 'users.php?act=list'
		);
		sys_msg(sprintf($_LANG['batch_remove_success'], $count), 0, $lnk);
	}
	else
	{
		$lnk[] = array(
			'text' => $_LANG['go_back'], 'href' => 'users.php?act=list'
		);
		sys_msg($_LANG['no_select_user'], 0, $lnk);
	}
}

/* 编辑用户名 */
function action_edit_username ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	check_authz_json('users_manage');
	
	$username = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
	$id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
	
	if($id == 0)
	{
		make_json_error('NO USER ID');
		return;
	}
	
	if($username == '')
	{
		make_json_error($GLOBALS['_LANG']['username_empty']);
		return;
	}
	
	$users = & init_users();
	
	if($users->edit_user($id, $username))
	{
		if($_CFG['integrate_code'] != 'ecshop')
		{
			/* 更新商城会员表 */
			$db->query('UPDATE ' . $ecs->table('users') . " SET user_name = '$username' WHERE user_id = '$id'");
		}
		
		admin_log(addslashes($username), 'edit', 'users');
		make_json_result(stripcslashes($username));
	}
	else
	{
		$msg = ($users->error == ERR_USERNAME_EXISTS) ? $GLOBALS['_LANG']['username_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
		make_json_error($msg);
	}
}

/* ------------------------------------------------------ */
// -- 编辑email
/* ------------------------------------------------------ */
function action_edit_email ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	check_authz_json('users_manage');
	
	$id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
	$email = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
	
	$users = & init_users();
	
	$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '$id'";
	$username = $db->getOne($sql);
	
	if(is_email($email))
	{
		if($users->edit_user(array(
			'username' => $username, 'email' => $email
		)))
		{
			admin_log(addslashes($username), 'edit', 'users');
			
			make_json_result(stripcslashes($email));
		}
		else
		{
			$msg = ($users->error == ERR_EMAIL_EXISTS) ? $GLOBALS['_LANG']['email_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
			make_json_error($msg);
		}
	}
	else
	{
		make_json_error($GLOBALS['_LANG']['invalid_email']);
	}
}

function action_edit_mobile_phone ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	check_authz_json('users_manage');

	$id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
	$mobile_phone = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));

	$users = & init_users();

	$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '$id'";
	$username = $db->getOne($sql);

	if(is_mobile_phone($mobile_phone))
	{
		if($users->edit_user(array(
				'username' => $username, 'mobile_phone' => $mobile_phone
		)))
		{
			admin_log(addslashes($username), 'edit', 'users');
				
			make_json_result(stripcslashes($mobile_phone));
		}
		else
		{
			$msg = ($users->error == ERR_MOBILE_PHONE_EXISTS) ? $GLOBALS['_LANG']['mobile_phone_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
			make_json_error($msg);
		}
	}
	else
	{
		make_json_error($GLOBALS['_LANG']['invalid_mobile_phone']);
	}
}

/* ------------------------------------------------------ */
// -- 删除会员帐号
/* ------------------------------------------------------ */
function action_remove ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_drop');
	
	$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
	$username = $db->getOne($sql);
	/* 通过插件来删除用户 */
	$users = & init_users();
	$users->remove_user($username); // 已经删除用户所有数据
	
	/* 记录管理员操作 */
	admin_log(addslashes($username), 'remove', 'users');
	
	/* 提示信息 */
	$link[] = array(
		'text' => $_LANG['go_back'], 'href' => 'users.php?act=list'
	);
	sys_msg(sprintf($_LANG['remove_success'], $username), 0, $link);
}

/* ------------------------------------------------------ */
// -- 收货地址查看
/* ------------------------------------------------------ */
function action_address_list ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$sql = "SELECT a.*, c.region_name AS country_name, p.region_name AS province, ct.region_name AS city_name, d.region_name AS district_name " . " FROM " . $ecs->table('user_address') . " as a " . " LEFT JOIN " . $ecs->table('region') . " AS c ON c.region_id = a.country " . " LEFT JOIN " . $ecs->table('region') . " AS p ON p.region_id = a.province " . " LEFT JOIN " . $ecs->table('region') . " AS ct ON ct.region_id = a.city " . " LEFT JOIN " . $ecs->table('region') . " AS d ON d.region_id = a.district " . " WHERE user_id='$id'";
	$address = $db->getAll($sql);
	$smarty->assign('address', $address);
	assign_query_info();
	$smarty->assign('ur_here', $_LANG['address_list']);
	$smarty->assign('action_link', array(
		'text' => $_LANG['03_users_list'], 'href' => 'users.php?act=list&' . list_link_postfix()
	));
	$smarty->display('user_address_list.htm');
}

/* ------------------------------------------------------ */
// -- 脱离推荐关系
/* ------------------------------------------------------ */
function action_remove_parent ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	
	$sql = "UPDATE " . $ecs->table('users') . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
	$db->query($sql);
	
	/* 记录管理员操作 */
	$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
	$username = $db->getOne($sql);
	admin_log(addslashes($username), 'edit', 'users');
	
	/* 提示信息 */
	$link[] = array(
		'text' => $_LANG['go_back'], 'href' => 'users.php?act=list'
	);
	sys_msg(sprintf($_LANG['update_success'], $username), 0, $link);
}

/* ------------------------------------------------------ */
// -- 查看用户推荐会员列表
/* ------------------------------------------------------ */
function action_aff_list ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('users_manage');
	$smarty->assign('ur_here', $_LANG['03_users_list']);
	
	$auid = $_GET['auid'];
	$user_list['user_list'] = array();
	
	$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
	$smarty->assign('affiliate', $affiliate);
	
	empty($affiliate) && $affiliate = array();
	
	$num = count($affiliate['item']);
	$up_uid = "'$auid'";
	$all_count = 0;
	for($i = 1; $i <= $num; $i ++)
	{
		$count = 0;
		if($up_uid)
		{
			$sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
			$query = $db->query($sql);
			$up_uid = '';
			while($rt = $db->fetch_array($query))
			{
				$up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
				$count ++;
			}
		}
		$all_count += $count;
		
		if($count)
		{
			$sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated, user_money, frozen_money, rank_points, pay_points, reg_time " . " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id IN($up_uid)" . " ORDER by level, user_id";
			$user_list['user_list'] = array_merge($user_list['user_list'], $db->getAll($sql));
		}
	}
	
	$temp_count = count($user_list['user_list']);
	for($i = 0; $i < $temp_count; $i ++)
	{
		$user_list['user_list'][$i]['reg_time'] = local_date($_CFG['date_format'], $user_list['user_list'][$i]['reg_time']);
	}
	
	$user_list['record_count'] = $all_count;
	
	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('action_link', array(
		'text' => $_LANG['back_note'], 'href' => "users.php?act=edit&id=$auid"
	));
	
	assign_query_info();
	$smarty->display('affiliate_list.htm');
}

/**
 * 返回用户列表数据
 *
 * @access public
 * @param        	
 *
 * @return void
 */
function user_list ()
{
	$result = get_filter();
	if($result === false)
	{
		/* 过滤条件 */
		$filter['card'] = empty($_REQUEST['card']) ? '' : trim($_REQUEST['card']);
		$filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? '' : trim($_REQUEST['mobile']);
        $filter['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        $filter['parent_id'] = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
		{
			//$filter['card'] = json_str_iconv($filter['card']);
		}
		$filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);

		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'user_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		
		$ex_where = ' WHERE 1 ';
		if($filter['user_name'])
		{
			$ex_where .= " AND user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%' ";
		}
		if($filter['rank'])
		{
			$ex_where .= " AND user_rank = '$filter[rank]' ";
		}
		if($filter['card'])
		{
			$ex_where .= " AND card LIKE '%" . mysql_like_quote($filter['card']) . "%' ";
		}
		if($filter['mobile'])
		{
			$ex_where .= " AND mobile_phone LIKE '%" . $filter['mobile'] . "%' ";
		}
		if($filter['status'] != ''){
			$ex_where .= " AND `status`={$filter['status']}";
		}
		if($filter['parent_id'] > 0){
			$ex_where .= " AND parent_id={$filter['parent_id']}";
		}
		
		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . $ex_where);
		
		/* 分页大小 */
		$filter = page_and_size($filter);
		/* 代码增加2014-12-23 by www.68ecshop.com _star */
		// $sql = "SELECT user_id, user_name, email, is_validated,
		// validated,status,user_money, frozen_money, rank_points, pay_points,
		// reg_time ".
		$sql = "SELECT user_id, user_name, real_name, sex, card, credit_line, email, mobile_phone, is_validated, validated, user_money, frozen_money, user_rank, rank_points, pay_points, status, reg_time, parent_id ".
		        /* 代码增加2014-12-23 by www.68ecshop.com  _end  */
                " FROM " . $GLOBALS['ecs']->table('users') . $ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
		
		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}
	
	$user_list = $GLOBALS['db']->getAll($sql);
	
	$sql = "select * from " . $GLOBALS['ecs']->table('user_rank'); // . " where special_rank = 0"; //comment by liuweitao
	
	$rank_list = $GLOBALS['db']->getAll($sql);
	
	$count = count($user_list);
	for($i = 0; $i < $count; $i ++)
	{
		$user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list[$i]['reg_time']);
		
		for ($j = 0; $j < count($rank_list); $j ++)
		{
			$rank_id = $rank_list[$j]['rank_id'];
			//$rank_points = $user_list[$i]['rank_points'];
			//$min_point = $rank_list[$j]['min_points'];
			//$max_point = $rank_list[$j]['max_points'];
			if($rank_id == $user_list[$i]['user_rank'])  //($rank_points <= $max_point && $rank_points >= $min_point) // by liuweitao
			{
				$user_list[$i]['rank_name'] = $rank_list[$j]['rank_name'];
			}
		}
		$credit_money = $GLOBALS['db']->getOne('select sum(credit_line) from ' . $GLOBALS['ecs']->table('cash_record') . " where user_id={$user_list[$i]['user_id']} and status=1");
		$trade_money = $GLOBALS['db']->getOne('select sum(integral_money) from ' . $GLOBALS['ecs']->table('order_info') . " where user_id={$user_list[$i]['user_id']} and order_status=1");
		$user_list[$i]['pay_money'] = $credit_money + $trade_money;
		$user_list[$i]['inviter'] = empty($user_list[$i]['parent_id']) ? '' : $GLOBALS['db']->getOne('select real_name from ' . $GLOBALS['ecs']->table('users') . " where user_id={$user_list[$i]['parent_id']}");
	}
	
	$arr = array(
		'user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);
	
	return $arr;
}

?>
