<?php
/**
 * $Author: liuweitao $
 * 终端接口
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_license.php');
require_once('includes/cls_json.php');

class ApiMember
{ 
    public $action = '';    
    public $dataType = 0;
    public $payCode = '';
    public $cardNo = '';
    public $random = '';
    public $keycode = '';
    public $posNo = '';  //终端编码    
    public $errCode = 0;
    public $errMsg = '';
    public $responseData = array();
    
    function __construct()
    {
        if (isset($_GET['data_type'])) {
            $this->dataType = intval($_GET['data_type']);
            if ($this->dataType != 0 && $this->dataType != 1) {
                $this->dataType = 0;
            }
        }
        if (isset($_GET['card_no']) && !empty($_GET['card_no'])) {
            $this->cardNo = $_GET['card_no'];
        }
        if (isset($_GET['action'])) {
            $this->action = $_GET['action'];
        }
        if (isset($_GET['pos_no'])) {
            $this->posNo = $_GET['pos_no'];
        }
        if(isset($_GET['random'])){
            $this->random = $_GET['random'];
        }
        if(isset($_GET['keycode'])){
            $this->keycode = $_GET['keycode'];
        }
    }

    private function setError($msg, $errCode = -1)
    {
        $this->errCode = $errCode;
        $this->errMsg = $msg;
    }

    private function checkParams()
    {
        $ecs = $GLOBALS['ecs'];
        $db = $GLOBALS['db'];
        if(empty($this->action)){
            $this->setError('接口名称不能为空');
            return false;
        }
        if(!method_exists($this, "{$this->action}Handler")){
            $this->setError('接口不存在');
            return false;
        }
        if(empty($this->cardNo)){
            $this->setError('卡号不能为空');
            return false;
        }
        if(empty($this->posNo)){
            $this->setError('终端编号不能为空');
            return false;
        }
        if(!$db->getRow('select pos_id from ' . $ecs->table('pos') . " where pos_no='{$this->posNo}'")){
            $this->setError('POS机编号不存在');
            return false;
        }
        if(empty($this->random) || empty($this->keycode)){
            $this->setError('随机数和校验码不能为空');
            return false;
        }
        $timestamp = time();
        if(($timestamp - $this->random) > 30){
            $this->setError('随机数错误');
            return false;
        }
        if($this->keycode != md5("^{$this->cardNo}&{$this->random}$")){
            $this->setError('校验码错误');
            return false;
        }
        return true;
    }
    
    private function depositHandler()
    {
        global $db, $ecs;
        $amount = intval($_GET['amount']);
        if($amount <= 0) {
            $this->setError('充值金额必须大于0');
            return;
        }
        if(empty($_GET['client_key'])){
            $this->setError('client_key不能为空');
            return;
        }
        $client_key = $_GET['client_key'];
        $user_table = $ecs->table('users');
        $record_table = $ecs->table('deposit_record');
        $amount = $amount / 100;
        $record = $db->getRow("select user_id,user_money from {$user_table} where user_name='{$this->cardNo}'");
        if(empty($record)){
            $this->setError("未找到卡号：{$this->cardNo}");
            return;
        }
        if($db->getRow("select record_id from {$record_table} where client_key='{$client_key}'")){
            $this->setError('client_key不能重复');
            return;
        }
        $user_id = $record['user_id'];
        $money_value = $record['user_money'] + $amount;
        if($db->query("update {$user_table} set user_money={$money_value} where user_id={$user_id}")){
            $create_time = time();
            $db->query("insert into {$record_table} (user_id,card_no,pos_no,amount,client_key,create_time) values ({$user_id}, '{$this->cardNo}', '{$this->posNo}', '{$amount}', '{$client_key}', '{$create_time}')");
        }
        $this->responseData['user_money'] = $money_value;
    }

    private function query_depositHandler()
    {
        if(empty($_GET['client_key'])){
            $this->setError('client_key不能为空');
            return;
        }
        global $db, $ecs;
        $client_key = $_GET['client_key'];
        $record_table = $ecs->table('deposit_record');
        $user_table = $ecs->table('users');
        $record = $db->getRow("select record_id from {$record_table} where client_key='{$client_key}'");
        if(!$record){
            $this->setError('未找到充值记录');
            return;
        }
        $user_money = $db->getOne("select user_money from {$user_table} where user_name='{$this->cardNo}'");
        $this->responseData['record_id'] = $record['record_id'];
        $this->responseData['user_money'] = floatval($user_money);
    }
    
    private function cashHandler()
    {
        global $db, $ecs;
        $amount = intval($_GET['amount']);
        if($amount <= 0) {
            $this->setError('提现金额必须大于0');
            return;
        }
        $amount = $amount / 100;
        $user_table = $ecs->table('users');
        $record = $db->getRow("select user_id,user_money,credit_line,is_surplus_open,surplus_password from {$user_table} where user_name='{$this->cardNo}'");
        if(empty($record)){
            $this->setError("未找到卡号：{$this->cardNo}");
            return;
        }
        if(!$record['is_surplus_open']){
            $this->setError("该卡号没有开通支付密码，请先开通支付密码，然后提现");
            return;
        }
        if(empty($_GET['surplus_pwd'])){
            $this->setError('支付密码不能为空');
            return;
        }
        if($record['surplus_password'] != $_GET['surplus_pwd']){
            $this->setError('支付密码错误');
            return;
        }
        if($amount > ($record['user_money'] + $record['credit_line'])){
            $this->setError('余额不足');
            return;
        }
        if($record['user_money'] >= $amount){
            $user_money = $record['user_money'] - $amount;
            $credit_line = $record['credit_line'];
            $cash_money = $amount;
            $credit_money = 0;
        }else{
            $user_money = '0.00';
            $credit_line = $record['user_money'] + $record['credit_line'] - $amount;
            $cash_money = $record['user_money'];
            $credit_money = $amount - $record['user_money'];
        }
        $user_id = $record['user_id'];
        $record_table = $ecs->table('cash_record');
        if($db->query("update {$user_table} set user_money={$user_money},credit_line={$credit_line} where user_id={$user_id}")){
            $create_time = time();
            $db->query("insert into {$record_table} (user_id,card_no,pos_no,user_money,credit_line,create_time) values ({$user_id}, '{$this->cardNo}', '{$this->posNo}', '{$cash_money}', '{$credit_money}', '{$create_time}')");
        }
        $this->responseData['record_id'] = $db->insert_id();
        $this->responseData['user_money'] =  floatval($user_money);
        $this->responseData['credit_line'] = floatval($credit_line);
    }

    private function cashdoneHandler()
    {
        global $db, $ecs;
        $record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;
        if($record_id < 1){
            $this->setError('充值ID非法');
            return;
        }
        $cash_table = $ecs->table('cash_record');
        $user_table = $ecs->table('users');
        $user_id = $db->getOne("select user_id from {$cash_table} where record_id={$record_id}");
        if(!$user_id){
            $this->setError('未找到该充值记录');
            return;
        }
        $user_info = $db->getRow("select user_id,user_money,credit_line from {$user_table} where user_id={$user_id}");
        $db->query("update {$cash_table} set status=1 where record_id={$record_id}");
        $this->responseData['record_id'] = $record_id;
        $this->responseData['user_money'] = floatval($user_info['user_money']);
        $this->responseData['credit_line'] = floatval($user_info['credit_line']);
        $this->responseData['status'] = 1;
    }
    
    private function tradeHandler()
    {
        global $db, $ecs;
        require(ROOT_PATH . 'includes/lib_order.php');
        include_once('includes/lib_clips.php');
        include_once('includes/lib_payment.php');
        $amount = intval($_GET['amount']);
        if($amount <= 0) {
            $this->setError('提现金额必须大于0');
            return;
        }
        $amount = $amount / 100;
        $user_table = $ecs->table('users');
        $record = $db->getRow("select user_id,user_money,pay_points,is_surplus_open,surplus_password from {$user_table} where user_name='{$this->cardNo}'");
        if(empty($record)){
            $this->setError("未找到卡号：{$this->cardNo}");
            return;
        }
        if(!$record['is_surplus_open']){
            $this->setError("该卡号没有开通支付密码，请先开通支付密码，然后提现");
            return;
        }
        if(empty($_GET['surplus_pwd'])){
            $this->setError('支付密码不能为空');
            return;
        }
        if($record['surplus_password'] != $_GET['surplus_pwd']){
            $this->setError('支付密码错误');
            return;
        }
        if($amount > ($record['user_money'] + $record['pay_points'])){
            $this->setError('余额不足');
            return;
        }
        $pay_balance_id = $GLOBALS['db']->getOne('SELECT pay_id ' . ' FROM ' . $ecs->table('payment') . ' WHERE enabled = 1 and pay_code="balance"');
        if(!$pay_balance_id){
            $this->setError('服务器未开通余额支付方式，支付失败');
            return;
        }
        if($record['user_money'] >= $amount){
            $user_money = $record['user_money'] - $amount;
            $pay_points = $record['pay_points'];
            $surplus =  $amount;
            $integral_money = '0.00';
        }else{
            $user_money = '0.00';
            $pay_points = $record['user_money'] + $record['pay_points'] - $amount;
            $surplus = $record['user_money'];
            $integral_money = $amount - $record['user_money'];
        }
        $add_time = gmtime();
        $user_id = $record['user_id'];
        $order = array(
            'pay_id'          =>$pay_balance_id,
            'goods_amount'    =>$amount,
            'money_paid'      =>$amount,           //已支付金额
            'surplus'         =>$surplus,          //余额支付金额
            'integral_money'  =>$integral_money,   //积分金额支付，积分金额作为消费额度使用
            'user_id'         =>$user_id,
            'order_status'    =>OS_UNCONFIRMED,  //未确认
            'shipping_status' =>SS_SHIPPED, //已发货
            'pay_status'      =>PS_PAYING,  //付款中
            'add_time'        =>$add_time,
            'pay_time'        =>$add_time,
            'pay_name'        =>$this->posNo,
            'pay_note'        =>'terminal',
        );
        do{
            $order['order_sn'] = get_order_sn(); //获取新订单号
            $db->autoExecute($ecs->table('order_info'), $order, 'INSERT');
            $error_no = $db->errno();
            if ($error_no > 0 && $error_no != 1062){
                $this->setError($db->errorMsg());
                return;
            }
        }
        while ($error_no == 1062); //如果是订单号重复则重新提交数据
        $order_id = $db->insert_id();
        $db->query("update {$user_table} set user_money='{$user_money}', pay_points='{$pay_points}' where user_id={$user_id}");
        $this->responseData['order_id'] = $order_id;
        $this->responseData['order_sn'] = $order['order_sn'];
        $this->responseData['order_status'] = $order['order_status'];
        $this->responseData['create_time'] = date('Y-m-d H:i:s', $order['add_time']);
    }

    private function tradedoneHandler()
    {
        global $db, $ecs;
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if($order_id < 1){
            $this->setError('订单ID非法');
            return;
        }
        $order_table = $ecs->table('order_info');
        $order_sn = $db->getOne("select order_sn from {$order_table} where order_id={$order_id}");
        if(!$order_sn){
            $this->setError('订单不存在');
            return;
        }
        $user_table = $ecs->table('users');
        $record = $db->getRow("select user_id,user_money,credit_line from {$user_table} where user_name='{$this->cardNo}'");
        if(empty($record)){
            $this->setError("未找到卡号：{$this->cardNo}");
            return;
        }
        $confirm_time = gmtime();
        $db->query("update {$order_table} set order_status=" . OS_CONFIRMED . ', pay_status=' . PS_PAYED . ",confirm_time={$confirm_time} where order_id={$order_id}");
        $this->responseData['order_id'] = $order_id;
        $this->responseData['order_sn'] = $order_sn;
        $this->responseData['order_status'] = OS_CONFIRMED;
        $this->responseData['pay_status'] = PS_PAYED;
        $this->responseData['user_money'] = floatval($record['user_money']);
        $this->responseData['credit_line'] = floatval($record['credit_line']);
    }

    
    private function userHandler()
    {
        global $db, $ecs;
        /*用户信息查询功能，即刷卡后显示 姓名、会员卡号、消费余额、提现余额，这个是不是要添加新接口？*/
        $user_table = $ecs->table('users');
        $record = $db->getRow("select user_id,user_name, real_name,user_money,credit_line,pay_points,is_surplus_open from {$user_table} where user_name='{$this->cardNo}'");
        if(!$record){
            $this->setError("未找到卡号：{$this->cardNo}");
            return;
        }
        $this->responseData['card_no'] = $record['user_name'];
        $this->responseData['real_name'] = $record['real_name'];
        $this->responseData['user_money'] = floatval($record['user_money']);
        $this->responseData['credit_line'] = floatval($record['credit_line']);
        $this->responseData['pay_points'] = floatval($record['pay_points']);
        $this->responseData['is_surplus_open'] = intval($record['is_surplus_open']);
    }

    public function outputData()
    {
        $data = array('err_code'=>$this->errCode, 'err_msg'=>$this->errMsg, 'data'=>$this->responseData);
        $json_data = json_encode($data);
        header('Content-Length: ' . strlen($json_data));
        die($json_data);
    }
    
    public function processRequest()
    {
        if(!$this->checkParams()){
            $this->outputData();
        }
        $handler = "{$this->action}Handler";
        $this->$handler();
        $this->outputData();
    }
}
$apiMember = new ApiMember();
$apiMember->processRequest();
?>