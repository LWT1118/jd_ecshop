<?php
error_reporting(0); // 代码增加 By www.68ecshop.com
//session_start();

header("Content-type:text/html; charset=UTF-8");

function random ($length = 6, $numeric = 0)
{
	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
	if($numeric)
	{
		$hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
	}
	else
	{
		$hash = '';
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i ++)
		{
			$hash .= $chars[mt_rand(0, $max)];
		}
	}
	return $hash;
}

function read_file ($file_name)
{
	$content = '';
	$filename = date('Ymd') . '/' . $file_name . '.log';
	if(function_exists('file_get_contents'))
	{
		@$content = file_get_contents($filename);
	}
	else
	{
		if(@$fp = fopen($filename, 'r'))
		{
			@$content = fread($fp, filesize($filename));
			@fclose($fp);
		}
	}
	$content = explode("\r\n",$content);
	return end($content);
}

if($_GET['act'] == 'check')
{
	/* 代码修改_start BY www.ecshop68.com */
	$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
	$mobile_code = isset($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';
	/* 代码修改_end BY www.ecshop68.com */
	
	if(time() - $_SESSION['time'] > 30 * 60)
	{
		unset($_SESSION['mobile_code']);
		exit(json_encode(array(
			'msg' => '验证码超过30分钟。'
		)));
	}
	else
	{
		if($mobile != $_SESSION['mobile'] or $mobile_code != $_SESSION['mobile_code'])
		{
			exit(json_encode(array(
				'msg' => '手机验证码输入错误。'
			)));
		}
		else
		{
			exit(json_encode(array(
				'code' => '2'
			)));
		}
	}
 
}

if($_GET['act'] == 'send')
{
	
	/* 代码修改_start BY www.ecshop68.com */
	$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
	$mobile_code = isset($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';
	/* 代码修改_end BY www.ecshop68.com */
	
	//session_start();
	if(empty($mobile))
	{
		exit(json_encode(array(
			'msg' => '手机号码不能为空'
		)));
	}
	
	$preg = '/^1[0-9]{10}$/'; // 简单的方法
	if(! preg_match($preg, $mobile))
	{
		exit(json_encode(array(
			'msg' => '手机号码格式不正确'
		)));
	}
	
	$mobile_code = random(6, 1);
	
	$content = sprintf($GLOBALS['_CFG']['sms_register_tpl'],$mobile_code,$GLOBALS['_CFG']['sms_sign']);

	
	if($_SESSION['mobile'])
	{
		if(strtotime(read_file($mobile)) > (time() - 60))
		{
			exit(json_encode(array(
				'msg' => '获取验证码太过频繁，一分钟之内只能获取一次。'
			)));
		}
	}
	
	$num = sendSMS($mobile, $content);
	if($num == true)
	{
		$_SESSION['mobile'] = $mobile;
		$_SESSION['mobile_code'] = $mobile_code;
		$_SESSION['time'] = time();
		exit(json_encode(array(
			'code' => 2
		)));
	}
	else
	{
		exit(json_encode(array(
			'msg' => '手机验证码发送失败。'
		)));
	}
}

function sendSMS ($phone, $msg, $sendtime = '', $port = '', $needstatus = '')
{
    $username = 'shanglian8';
    $passwd = 'Ykt111';
    $ch = curl_init();
    $post_data = "username=".$username."&passwd=".$passwd."&phone=".$phone."&msg=".urlencode($msg)."&needstatus=true&port=".$port."&sendtime=".$sendtime;
    /**
    php5.4或php6 curl版本的curl数据格式为数组   你们接入时要注意
    $post_data = array(
    "username"="账号",
    "passwd"="密码",
    "phone"="手机号码1,号码2,号码3".
    "msg"="您好,你的验证码:8888【企业宝】",
    "needstatus"="true",
    "port"='',
    "sendtime"=''
    );
     **/
    curl_setopt ($ch, CURLOPT_URL,"http://www.qybor.com:8500/shortMessage");
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $file_contents = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($file_contents, true);
    return $result['respcode'] == '0';
    //$content = iconv('utf-8', 'gbk', $content);
	/*$http = 'http://120.24.167.205/msg/HttpSendSM'; // 短信接口
	
	$data = array(
		'account' => 'shanglian8', // 用户账号
		'pswd' => 'Ykt111', // MD5位32密码,密码和用户名拼接字符
		'mobile' => $mobile, // 号码
		'msg' => $content, // 内容
		'needstatus' => true, // 定时发送
		'product' => ''
	);
	$re = postSMS($http, $data); // POST方式提交	                          
	// change_sms change_start
	$re_t = explode(',', trim($re));
	if(isset($re_t[1]) && intval($re_t[1]) == 0)	// change_sms change_end		
	{
		return true;
	}
	else
	{
		return false;
	}*/
}

function postSMS ($url, $data = '')
{
	$row = parse_url($url);
	$host = $row['host'];
	$port = $row['port'] ? $row['port'] : 80;
	$file = isset($row['path']) ? $row['path'] : '/';
	while(list($k, $v) = each($data))
	{
		$post .= rawurlencode($k) . "=" . rawurlencode($v) . "&"; // 转URL标准码
	}
	$post = substr($post, 0, - 1);
	$len = strlen($post);
	$fp = @fsockopen($host, $port, $errno, $errstr, 10);
	if(! $fp)
	{
		return "$errstr ($errno)\n";
	}
	else
	{
		$receive = '';
		$out = "POST $file HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;
		fwrite($fp, $out);
		while(! feof($fp))
		{
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n", $receive);
		unset($receive[0]);
		return implode("", $receive);
	}
}

function checkSMS ($mobile, $mobile_code)
{
	$arr = array(
		'error' => 0,'msg' => ''
	);
	if(time() - $_SESSION['time'] > 30 * 60)
	{
		unset($_SESSION['mobile_code']);
		$arr['error'] = 1;
		$arr['msg'] = '验证码超过30分钟。';
	}
	else
	{
		if($mobile != $_SESSION['mobile'] or $mobile_code != $_SESSION['mobile_code'])
		{
			$arr['error'] = 1;
			$arr['msg'] = '手机验证码输入错误。';
		}
		else
		{
			$arr['error'] = 2;
		}
	}
	return $arr;
}
?>
