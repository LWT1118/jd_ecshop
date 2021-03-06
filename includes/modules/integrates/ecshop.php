<?php

/**
 * ECSHOP 会员数据处理类
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: ecshop.php 17217 2011-01-19 06:29:08Z liubo $
 */
if(! defined('IN_ECS'))
{
	die('Hacking attempt');
}

/* 模块的基本信息 */
if(isset($set_modules) && $set_modules == TRUE)
{
	$i = (isset($modules)) ? count($modules) : 0;
	
	/* 会员数据整合插件的代码必须和文件名保持一致 */
	$modules[$i]['code'] = 'ecshop';
	
	/* 被整合的第三方程序的名称 */
	$modules[$i]['name'] = 'ECSHOP';
	
	/* 被整合的第三方程序的版本 */
	$modules[$i]['version'] = '2.0';
	
	/* 插件的作者 */
	$modules[$i]['author'] = 'ECSHOP R&D TEAM';
	
	/* 插件作者的官方网站 */
	$modules[$i]['website'] = 'http://www.ecshop.com';
	
	return;
}

require_once (ROOT_PATH . 'includes/modules/integrates/integrate.php');

class ecshop extends integrate
{

	var $is_ecshop = 1;

	var $ec_salt = '';

	function __construct ($cfg)
	{
		$this->ecshop($cfg);
	}

	/**
	 *
	 * @access public
	 * @param        	
	 *
	 * @return void
	 */
	function ecshop ($cfg)
	{
		parent::integrate(array());
		$this->user_table = 'users';
		$this->field_id = 'user_id';
		$this->ec_salt = 'ec_salt';
		$this->field_name = 'user_name';
		$this->field_pass = 'password';
		$this->field_email = 'email';
		$this->field_gender = 'sex';
		$this->field_bday = 'birthday';
		$this->field_reg_date = 'reg_time';
		$this->field_mobile_phone = 'mobile_phone';
		$this->field_email_validated = 'is_validated';
		$this->field_mobile_validated = 'validated';
		$this->need_sync = false;
		$this->is_ecshop = 1;
		/* add by liuweitao start */
		$this->field_realname = 'real_name';
		$this->field_card = 'card';
		$this->field_bank_card_no = 'bank_card_no';
		$this->field_country = 'country';
		$this->field_province = 'province';
		$this->field_city = 'city';
		$this->field_district = 'district';
		$this->field_address = 'address';
		$this->field_img_bank_card = 'img_bank_card';
		$this->field_face_card = 'face_card';
		$this->field_back_card = 'back_card';
		$this->field_user_money = 'user_money';
		$this->field_pay_points = 'pay_points';
		$this->field_credit_line = 'credit_line';
		$this->field_user_rank = 'user_rank';
		$this->field_status = 'status';
		$this->field_is_surplus_open = 'is_surplus_open';
		$this->field_surplus_password = 'surplus_password';
        $this->field_parent_id = 'parent_id';
        $this->field_family1_name = 'family1_name';
        $this->field_family1_mobile = 'family1_mobile';
        $this->field_family2_name = 'family2_name';
        $this->field_family2_mobile = 'family2_mobile';
		/* add by liuweitao end */
	}

	/**
	 * 检查指定用户是否存在及密码是否正确(重载基类check_user函数，支持zc加密方法)
	 *
	 * @access public
	 * @param string $username
	 *        	用户名
	 *        	
	 * @return int
	 */
	function check_user ($username, $password = null)
	{
		if($this->charset != 'UTF8')
		{
			$post_username = ecs_iconv('UTF8', $this->charset, $username);
		}
		else
		{
			$post_username = $username;
		}
		
		if($password === null)
		{
			$sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_name . "='" . $post_username . "'";
			
			return $this->db->getOne($sql);
		}
		else
		{
			$sql = "SELECT user_id, password, salt,ec_salt " . " FROM " . $this->table($this->user_table) . " WHERE user_name='$post_username'";
			$row = $this->db->getRow($sql);
			$ec_salt = $row['ec_salt'];
			if(empty($row))
			{
				return 0;
			}
			if($row['password'] != md5($password)){
				return 0;
			}
			return $row['user_id']; /* add by liuweitao */
			
			if(empty($row['salt']))
			{
				if($row['password'] != $this->compile_password(array(
					'password' => $password, 'ec_salt' => $ec_salt
				)))
				{
					return 0;
				}
				else
				{
					if(empty($ec_salt))
					{
						$ec_salt = rand(1, 9999);
						$new_password = md5(md5($password) . $ec_salt);
						$sql = "UPDATE " . $this->table($this->user_table) . "SET password= '" . $new_password . "',ec_salt='" . $ec_salt . "'" . " WHERE user_name='$post_username'";
						$this->db->query($sql);
					}
					return $row['user_id'];
				}
			}
			else
			{
				/* 如果salt存在，使用salt方式加密验证，验证通过洗白用户密码 */
				$encrypt_type = substr($row['salt'], 0, 1);
				$encrypt_salt = substr($row['salt'], 1);
				
				/* 计算加密后密码 */
				$encrypt_password = '';
				switch($encrypt_type)
				{
					case ENCRYPT_ZC:
						$encrypt_password = md5($encrypt_salt . $password);
						break;
					/* 如果还有其他加密方式添加到这里 */
					// case other :
					// ----------------------------------
					// break;
					case ENCRYPT_UC:
						$encrypt_password = md5(md5($password) . $encrypt_salt);
						break;
					
					default:
						$encrypt_password = '';
				}
				
				if($row['password'] != $encrypt_password)
				{
					return 0;
				}
				
				$sql = "UPDATE " . $this->table($this->user_table) . " SET password = '" . $this->compile_password(array(
					'password' => $password
				)) . "', salt=''" . " WHERE user_id = '$row[user_id]'";
				$this->db->query($sql);
				
				return $row['user_id'];
			}
		}
	}

	/**
	 * 编辑用户信息($password, $email, $gender, $bday, $mobile_phone,
	 * $email_validated, $mobile_phonle_validated)
	 *
	 * @access public
	 * @param        	
	 *
	 * @return void
	 */
	function edit_user ($cfg)
	{
		if(empty($cfg['username']))
		{
			return false;
		}
		else
		{
			$cfg['post_username'] = $cfg['username'];
		}
		
		$values = array();
		if(! empty($cfg['password']) && empty($cfg['md5password']))
		{
			$cfg['md5password'] = md5($cfg['password']);
		}
		if((! empty($cfg['md5password'])) && $this->field_pass != 'NULL')
		{
			$values[] = $this->field_pass . "='" . $this->compile_password(array(
				'md5password' => $cfg['md5password']
			)) . "'";
			// 重置ec_salt、salt
			$values[] = "salt = 0";
			$values[] = "ec_salt = 0";
		}
		
		if((! empty($cfg['email'])) && $this->field_email != 'NULL')
		{
			/* 检查email是否重复 */
			$sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_email . " = '$cfg[email]' " . " AND " . $this->field_name . " != '$cfg[post_username]'";
			if($this->db->getOne($sql, true) > 0)
			{
				$this->error = ERR_EMAIL_EXISTS;
				
				return false;
			}
			
			$values[] = $this->field_email . "='" . $cfg['email'] . "'";
			
			if(isset($cfg['email_validated']) && ! empty($cfg['email_validated']))
			{
				if($cfg['email_validated'] != 1)
				{
					$cfg['email_validated'] = 0;
				}
				
				$values[] = $this->field_email_validated . "='" . $cfg['email_validated'] . "'";
			}
			else
			{
				
				// 检查是否为新E-mail
				$sql = "SELECT count(*)" . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_email . " = '$cfg[email]' ";
				if($this->db->getOne($sql, true) == 0)
				{
					// 新的E-mail
					$cfg['email_validated'] = 0;
					
					$values[] = $this->field_email_validated . "='" . $cfg['email_validated'] . "'";
				}
			}
		}
		
		// 手机号
		if((! empty($cfg['mobile_phone'])) && $this->field_mobile_phone != 'NULL')
		{
			/* 检查email是否重复 */
			$sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_mobile_phone . " = '$cfg[mobile_phone]' " . " AND " . $this->field_name . " != '$cfg[post_username]'";
			if($this->db->getOne($sql, true) > 0)
			{
				$this->error = ERR_MOBILE_PHONE_EXISTS;
				
				return false;
			}
			
			$values[] = $this->field_mobile_phone . "='" . $cfg[mobile_phone] . "'";
			
			if(isset($cfg['mobile_validated']) && ! empty($cfg['mobile_validated']))
			{
				if($cfg['mobile_validated'] != 1)
				{
					$cfg['mobile_validated'] = 0;
				}
				
				$values[] = $this->field_mobile_validated . "='" . $cfg['mobile_validated'] . "'";
			}
			else
			{
				
				// 检查是否为新E-mail
				$sql = "SELECT count(*)" . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_mobile_phone . " = '$cfg[mobile_phone]' ";
				if($this->db->getOne($sql, true) == 0)
				{
					// 新的E-mail
					$cfg['mobile_validated'] = 0;
					
					$values[] = $this->field_mobile_validated . "='" . $cfg['mobile_validated'] . "'";
				}
			}
		}
		
		if(isset($cfg['gender']) && $this->field_gender != 'NULL')
		{
			$values[] = $this->field_gender . "='" . $cfg['gender'] . "'";
		}
		
		if((! empty($cfg['bday'])) && $this->field_bday != 'NULL')
		{
			$values[] = $this->field_bday . "='" . $cfg['bday'] . "'";
		}
		
		if($values)
		{
			$sql = "UPDATE " . $this->table($this->user_table) . " SET " . implode(', ', $values) . " WHERE " . $this->field_name . "='" . $cfg['post_username'] . "' LIMIT 1";
			
			$this->db->query($sql);
			
			if($this->need_sync)
			{
				if(empty($cfg['md5password']))
				{
					$this->sync($cfg['username']);
				}
				else
				{
					$this->sync($cfg['username'], '', $cfg['md5password']);
				}
			}
		}
		
		return true;
	}
    /**
     * 审核用户信息($username, $user_money, $pay_points, $user_rank, $status)
     * @author liuweitao
     * @return boolean
     */
    function edit_user_by_id($user_id, $username, $user_money, $pay_points, $credit_line, $user_rank, $status){
        if(empty($username)){
            return false;
        }
        $user_table = $this->table($this->user_table);
        $values = array();
        $values[] = "{$this->field_name}='{$username}'";
        $values[] = "{$this->field_user_money}='{$user_money}'";
        $values[] = "{$this->field_pay_points}='{$pay_points}'";
        $values[] = "{$this->field_credit_line}='{$credit_line}'";
        $values[] = "{$this->field_user_rank}='{$user_rank}'";
        $values[] = "{$this->field_status}='{$status}'";
        if($values) {
            $sql = "UPDATE {$user_table} SET " . implode(', ', $values) . " WHERE user_id={$user_id} LIMIT 1";
            $this->db->query($sql);
        }
        return true;
    }
	/**
	 * 审核用户信息($username, $user_money, $pay_points, $user_rank, $status)
	 * @author liuweitao
	 * @return boolean
	 */
	function audit_user ($user_id, $username, $user_money, $pay_points, $credit_line, $user_rank, $status)
	{
	    if(empty($username))
	    {
	        return false;
	    }
	    $password = '666666';
	    $md5password = md5($password);
	
	    $values = array();
	    $values[] = "{$this->field_name}='{$username}'";
	    if((!empty($md5password)) && $this->field_pass != 'NULL')
	    {
	        $values[] = $this->field_pass . "='" . $this->compile_password(array('md5password' => $md5password)) . "'";
	        //重置ec_salt、salt
	        $values[] = "salt = 0";
	        $values[] = "ec_salt = 0";
	    }
	    $values[] = "{$this->field_user_money}='{$user_money}'";
	    $values[] = "{$this->field_pay_points}='{$pay_points}'";
	    $values[] = "{$this->field_credit_line}='{$credit_line}'";
	    $values[] = "{$this->field_user_rank}='{$user_rank}'";
	    $values[] = "{$this->field_status}='{$status}'";
	
	    if($values)
	    {
	        $sql = "UPDATE " . $this->table($this->user_table) . " SET " . implode(', ', $values) . " WHERE user_id={$user_id} LIMIT 1";
	        $this->db->query($sql);
	        	
	        /* comment by liuweitao
	        if($this->need_sync)
	        {
	            if(empty($cfg['md5password']))
	            {
	                $this->sync($cfg['username']);
	            }
	            else
	            {
	                $this->sync($cfg['username'], '', $cfg['md5password']);
	            }
	        }
	        */
	    }
	    return true;
	}

	/**
	 * 获取指定用户的信息
	 *
	 * @access public
	 * @param        	
	 *
	 * @return void
	 */
	function get_profile_by_name ($username)
	{
		$post_username = $username;
		
		$sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," . $this->field_email . " AS email," . $this->field_gender . " AS sex," . $this->field_email_validated . " AS email_validated, " . $this->field_mobile_phone . " AS mobile_phone, " . $this->field_mobile_validated . " AS mobile_validated, " . $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " . $this->field_pass . " AS password, " . $this->ec_salt . " AS ec_salt " . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_name . "='$post_username'";
		$row = $this->db->getRow($sql);
		
		return $row;
	}
	
	/**
	 * @根据mobile获取用户id
	 * @add by liuweitao 
	 */
	function get_user_id_by_mobile($mobile)
	{
	    $sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_mobile_phone . "='{$mobile}'";
	    return $this->db->getOne($sql, true);
	}

	/**
	 * 获取指定用户的信息
	 *
	 * @access public
	 * @param        	
	 *
	 * @return void
	 */
	function get_profile_by_id ($id)
	{
		$sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," . $this->field_email . " AS email," . $this->field_gender . " AS sex," . $this->field_email_validated . " AS email_validated, " . $this->field_mobile_phone . " AS mobile_phone, " . $this->field_mobile_validated . " AS mobile_validated, " . $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " . $this->field_pass . " AS password, " . $this->ec_salt . " AS ec_salt " . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_id . "='$id'";
		$row = $this->db->getRow($sql);
		
		return $row;
	}
}

?>
