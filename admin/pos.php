<?php
/**
 * POS机管理
 * $Author: liuweitao $
 * $Id: pos.php 2017-01-07 $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
    $function_name = "action_list";
}

call_user_func($function_name);

function action_list ()
{
    // 全局变量
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];

    /* 检查权限 */
    admin_priv('pos_manage');

    $smarty->assign('ur_here', $_LANG['02_pos_list']);
    $smarty->assign('action_link', array(
        'text' => $_LANG['04_pos_add'],'href' => 'pos.php?act=add'
    ));

    $pos_list = pos_list();

    $smarty->assign('pos_list', $pos_list['pos_list']);
    $smarty->assign('filter', $pos_list['filter']);
    $smarty->assign('record_count', $pos_list['record_count']);
    $smarty->assign('page_count', $pos_list['page_count']);
    $smarty->assign('full_page', 1);

    assign_query_info();
    $smarty->display('pos_list.htm');
}
/* add by liuweitao 未加权限验证 */
function action_record()
{
	$pos_no = $_REQUEST['pos_no'];
    // 全局变量
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];

    /*  暂时注掉检查权限 */
    //admin_priv('pos_manage');

    $smarty->assign('ur_here', "POS机{$pos_no}刷卡记录");
    $smarty->assign('action_link', array(
        'text' => $_LANG['03_pos_list'],'href' => 'pos.php?act=list'
    ));
	$order_table = $GLOBALS['ecs']->table('order_info');
	$cash_table = $GLOBALS['ecs']->table('cash_record');
	$ex_where = '';
	if($_POST['start_time']){
		$ex_where .= ' AND add_time >= ' . strtotime($_POST['start_time']);
	}
	if($_POST['end_time']){
		$ex_where .= ' AND add_time <= ' . strtotime($_POST['end_time']);
	}
    $terminal_result = $GLOBALS['db']->getRow("select min(add_time) as add_time, sum(money_paid) as total from {$order_table} WHERE pay_name='{$pos_no}' and pay_note='terminal' and pay_status = " . PS_PAYED . $ex_where);
	$trade_result = $GLOBALS['db']->getRow("select min(add_time) as add_time, sum(goods_amount-surplus) as total from {$order_table} where pay_note!='terminal' and pay_status=" . PS_PAYED . $ex_where);
	$cash_result = $GLOBALS['db']->getRow("select min(add_time) as add_time,sum(credit_line) as total from {$cash_table} where status=1 and pos_no='{$pos_no}'{$ex_were}");
  	
	$terminal_list = empty($ex_where) ? array() : $GLOBALS['db']->getAll("select order_sn, FROM_UNIXTIME(add_time) as add_time, money_paid as total, '终端消费' as type from {$order_table} WHERE pay_name='{$pos_no}' and pay_note='terminal' and pay_status = " . PS_PAYED . $ex_where);
	$trade_list = empty($ex_where) ? array() : $GLOBALS['db']->getAll("select order_sn, FROM_UNIXTIME(add_time) as add_time, (goods_amount-surplus) as total, '商城消费' as type from {$order_table} where pay_note!='terminal' and pay_status=" . PS_PAYED);
	$cash_list = empty($ex_where) ? array() : $GLOBALS['db']->getAll("select card_no as order_sn, FROM_UNIXTIME(add_time) as add_time, credit_line as total, '终端提现' as type from {$cash_table} where status=1 and pos_no='{$pos_no}'");


    $record_list = empty($ex_where) ? array() : array_merge($terminal_list, $trade_list, $cash_list);

	$smarty->assign('start_time', isset($_POST['start_time']) ? $_POST['start_time'] : '');
	$smarty->assign('end_time', isset($_POST['end_time']) ? $_POST['end_time'] : '');
	$smarty->assign('pos_no', $pos_no);
	$smarty->assign('start_time', date('Y-m-d H:i:s', min($terminal_result['add_time'], $trade_result['add_time'], $cash_result['add_time'])));
	$smarty->assign('terminal_money', $terminal_result['total']);
	$smarty->assign('trade_money', $trade_result['total']);
	$smarty->assign('cash_money', $cash_result['total']);
	$smarty->assign('total', $terminal_result['total'] + $trade_result['total'] + $cash_result['total']);
    $smarty->assign('record_list', $record_list);
    /*$smarty->assign('filter', $record_list['filter']);
    $smarty->assign('record_count', $record_list['record_count']);
    $smarty->assign('page_count', $record_list['page_count']);*/
    $smarty->assign('full_page', 1);

    assign_query_info();
    $smarty->display('pos_record.htm');
}

/* add by liuweitao */
function action_query_record ()
{
    // 全局变量
    $smarty = $GLOBALS['smarty'];

    $record_list = pos_record($_GET['pos_no']);

    $smarty->assign('record_list', $record_list['record_list']);
    $smarty->assign('filter', $record_list['filter']);
    $smarty->assign('record_count', $record_list['record_count']);
    $smarty->assign('page_count', $record_list['page_count']);

    $sort_flag = sort_flag($record_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('pos_record.htm'), '', array(
        'filter' => $record_list['filter'],'page_count' => $record_list['page_count']
    ));
}

function action_query ()
{
    // 全局变量
    $smarty = $GLOBALS['smarty'];


    $pos_list = pos_list();

    $smarty->assign('pos_list', $pos_list['pos_list']);
    $smarty->assign('filter', $pos_list['filter']);
    $smarty->assign('record_count', $pos_list['record_count']);
    $smarty->assign('page_count', $pos_list['page_count']);

    $sort_flag = sort_flag($pos_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('pos_list.htm'), '', array(
        'filter' => $pos_list['filter'],'page_count' => $pos_list['page_count']
    ));
}

function action_add ()
{
    // 全局变量
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 检查权限 */
    admin_priv('pos_manage');

    $pos_id = isset($_GET['id']) ? 0 : intval($_GET['id']);
    $pos = $pos_id > 0 ? $db->getRow('SELECT * FROM ' . $ecs->table('pos') . " where pos_id={$pos_id}") : array('pos_id'=>'0');

    $smarty->assign('ur_here', $_LANG['04_pos_add']);
    $smarty->assign('action_link', array(
        'text' => $_LANG['03_pos_list'],'href' => 'pos.php?act=list'
    ));
    $smarty->assign('form_action', $pos_id > 0 ? 'update' : 'insert');
    $smarty->assign('pos', $pos);
    $smarty->assign('special_ranks', get_rank_list(true));

    $smarty->assign('lang', $_LANG);


    assign_query_info();
    $smarty->display('pos_info.htm');
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_insert ()
{
    $_LANG = $GLOBALS['_LANG'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 检查权限 */
    admin_priv('pos_manage');
    $fields = $values = array();
    $pos_no = $_POST['pos_no'];
    unset($_POST['act'], $_POST['id']);
    foreach ($_POST as $key=>$value){
        if(empty($value)){
            sys_msg('请将所有信息填写完整后再提交', 1);
        }else {
            $fields[] = $key;
            $values[] = "'{$value}'";
        }
    }
    if($db->getRow("SELECT pos_id FROM " . $ecs->table('pos') . " WHERE pos_no='{$pos_no}'")){
        sys_msg('POS机编号重重', 1);
    }

    $fields[] = 'create_time';
    $values[] = time();

    $sql = 'INSERT INTO ' . $ecs->table('pos') . ' (' . implode(',', $fields) . ') values (' . implode(',', $values) . ')';
    $db->query($sql);

    /* 记录管理员操作 */
    admin_log($_SESSION['user_id'], 'add', 'pos');

    /* 提示信息 */
    $link[] = array(
        'text' => $_LANG['go_back'],'href' => 'pos.php?act=list'
    );
    sys_msg(sprintf($_LANG['pos_add_success'], htmlspecialchars(stripslashes($_POST['post_no']))), 0, $link);
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
    admin_priv('pos_manage');
    $pos_id = intval($_GET['id']);
    $pos = $db->GetRow('SELECT *  FROM ' . $ecs->table('pos') . " WHERE pos_id={$pos_id}");
    if(!$pos){
        sys_msg($_LANG['id_invalid'], 0, array('text' => $_LANG['go_back'],'href' => 'pos.php?act=list'));
    }
    $smarty->assign('lang', $_LANG);
    assign_query_info();
    $smarty->assign('ur_here', $_LANG['pos_edit']);
    $smarty->assign('action_link', array(
        'text' => $_LANG['03_pos_list'],'href' => 'pos.php?act=list&' . list_link_postfix()
    ));
    $smarty->assign('pos', $pos);
    $smarty->assign('form_action', 'update');
    $smarty->display('pos_info.htm');
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
    $pos_id = intval($_POST['id']);
    $values = array();
    unset($_POST['act'], $_POST['id']);
    foreach ($_POST as $key=>$value){
        if(empty($value)){
            sys_msg('请将所有信息填写完整后再提交', 1);
        }else {
            $values[] = "{$key}='{$value}'";
        }
    }
    $db->query('UPDATE ' . $ecs->table('pos') . ' SET ' . implode(',', $values) . " WHERE pos_id={$pos_id}");
    admin_log($_SESSION['user_id'], 'edit', 'pos');
    /* 提示信息 */
    $links[0]['text'] = $_LANG['goto_list'];
    $links[0]['href'] = 'pos.php?act=list&' . list_link_postfix();
    $links[1]['text'] = $_LANG['go_back'];
    $links[1]['href'] = 'javascript:history.back()';
    sys_msg($_LANG['update_pos_success'], 0, $links);
}
/* add by liuweitao */
function pos_record ($pos_no)
{
    $result = get_filter();
    if($result === false)
    {
        /* 过滤条件 */
		$order_table = $GLOBALS['ecs']->table('order_info');
        $cash_table = $GLOBALS['ecs']->table('cash_record');
		$filter['start_time'] = empty($_REQUEST['start_time']) ? '' : trim($_REQUEST['start_time']);
        if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['start_time'] = json_str_iconv($filter['start_time']);
        }
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : trim($_REQUEST['end_time']);
        if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['end_time'] = json_str_iconv($filter['end_time']);
        }
        $ex_where = '';
        if($filter['start_time'])
        {
            $ex_where .= ' AND add_time >= ' . strtotime($filter['start_time']);
        }
		if($filter['end_time'])
		{
			$ex_where .= ' AND add_time <= ' . strtotime($filter['end_time']);
		}
		$terminal_result = $GLOBALS['db']->getAll("select order_sn, add_time, money_paid as total, '终端消费' as type from {$order_table} WHERE pay_name='{$pos_no}' and pay_note='terminal' and pay_status = " . PS_PAYED . $ex_where);
		$trade_result = $GLOBALS['db']->getAll("select order_sn, add_time, (goods_amount-surplus) as total, '商城消费' as type from {$order_table} where pay_note!='terminal' and pay_status=" . PS_PAYED);
		$cash_result = $GLOBALS['db']->getAll("select pos_no as order_sn, add_time, credit_line as total, '终端提现' as type from {$cash_table} where status=1 and pos_no='{$pos_no}'");

        //$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM {$table}{$ex_where}");

        /* 分页大小 */
        $filter = page_and_size($filter);
        //$sql = "SELECT * FROM {$table}{$ex_where} LIMIT " . $filter['start'] . ',' . $filter['page_size'];

		$filter['pos_no'] = $pos_no;
        $filter['start_time'] = stripslashes($filter['start_time']);
        $filter['end_time'] = stripslashes($filter['end_time']);

        set_filter($filter, $sql);
    }
    else
    {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    //$record_list = $GLOBALS['db']->getAll($sql);
	$record_list = array_merge($terminal_result, $trade_result, $cash_result);


    $count = count($record_list);
    for($i = 0; $i < $count; $i ++)
    {
        //$record_list[$i]['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $record_list[$i]['add_time']);
        $record_list[$i]['add_time_format'] = date('Y-m-d H:i:s', $record_list[$i]['add_time']);
	}

    $arr = array(
        'record_list' => $record_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'total'=>$total
    );

    return $arr;
}

function pos_list ()
{
    $result = get_filter();
    if($result === false)
    {
        /* 过滤条件 */
        $filter['pos_no'] = empty($_REQUEST['pos_no']) ? '' : trim($_REQUEST['pos_no']);
        if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['pos_no'] = json_str_iconv($filter['pos_no']);
        }

        $ex_where = ' WHERE 1 ';
        if($filter['pos_no'])
        {
            $ex_where .= " AND pos_no LIKE '%" . mysql_like_quote($filter['pos_no']) . "%'";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('pos') . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pos') . $ex_where . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['pos_no'] = stripslashes($filter['pos_no']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $pos_list = $GLOBALS['db']->getAll($sql);

    $count = count($pos_list);
    for($i = 0; $i < $count; $i ++)
    {
        $pos_list[$i]['create_time'] = local_date($GLOBALS['_CFG']['date_format'], $pos_list[$i]['create_time']);
        //$pos_list[$i]['total'] = $GLOBALS['db']->getOne('select count(*) from ' . $GLOBALS['ecs']->table('order_info') . " where pay_note='terminal' and pay_name='{$pos_list[$i]['pos_no']}'");
    }

    $arr = array(
        'pos_list' => $pos_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
    );

    return $arr;
}

/* ------------------------------------------------------ */
// -- 删除POS机
/* ------------------------------------------------------ */
function action_remove ()
{
    $_LANG = $GLOBALS['_LANG'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 检查权限 */
    admin_priv('pos_drop');
    $pos_id = intval($_GET['id']);
    $db->query('DELETE FROM ' . $ecs->table('pos') . " WHERE pos_id={$pos_id}");
    /* 记录管理员操作 */
    admin_log($_SESSION['user_id'], 'remove', 'pos');

    /* 提示信息 */
    $link[] = array(
        'text' => $_LANG['go_back'], 'href' => 'pos.php?act=list'
    );
    sys_msg(sprintf($_LANG['remove_pos_success'], $pos_id), 0, $link);
}
/* ------------------------------------------------------ */
// -- 批量删除POS机
/* ------------------------------------------------------ */
function action_batch_remove ()
{
    $_LANG = $GLOBALS['_LANG'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 检查权限 */
    admin_priv('pos_drop');
    if(isset($_POST['checkboxes']))
    {
        $sql = "SELECT pos_id FROM " . $ecs->table('pos') . " WHERE pos_id " . db_create_in($_POST['checkboxes']);
        $col = $db->getCol($sql);
        $pos_ids = implode(',', addslashes_deep($col));
        $count = count($col);
        if($count > 0) {
            $db->query('DELETE FROM ' . $ecs->table('pos') . " WHERE pos_id in ({$pos_ids})");
        }
        admin_log($_SESSION['user_id'], 'batch_remove', 'pos');
        $lnk[] = array(
            'text' => $_LANG['go_back'], 'href' => 'pos.php?act=list'
        );
        sys_msg(sprintf($_LANG['batch_remove_pos_success'], $count), 0, $lnk);
    }
    else
    {
        $lnk[] = array(
            'text' => $_LANG['go_back'], 'href' => 'pos.php?act=list'
        );
        sys_msg($_LANG['no_select_pos'], 0, $lnk);
    }
}
?>
