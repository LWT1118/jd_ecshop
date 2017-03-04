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
    // 全局变量
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];

    /*  暂时注掉检查权限 */
    //admin_priv('pos_manage');

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
        $pos_list[$i]['total'] = $GLOBALS['db']->getOne('select count(*) from ' . $GLOBALS['ecs']->table('order_info') . " where pay_note='terminal' and pay_name='{$pos_list[$i]['pos_no']}'");
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