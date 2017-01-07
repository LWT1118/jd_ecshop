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
        //检测pos机是否在数据库中存在 ... to do
        /*if(empty($this->random) || empty($this->keycode)){
            $this->setError('随机数和校验码不能为空');
            return false;
        }
        if($this->keycode != md5("^{$this->cardNo}&{$this->random}$")){
            $this->setError('校验码错误');
            return false;
        }*/
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
        $user_table = $ecs->table('users');
        $amount = $amount / 100;
        $sql = "select user_id,user_money from {$user_table} where user_name='{$this->cardNo}'";
        $record = $db->getRow($sql);
        if(empty($record)){
            $this->setError("未找到卡号：{$this->cardNo}");
            return;
        }
        $user_id = $record['user_id'];
        $money_value = $record['user_money'] + $amount;
        $record_table = $ecs->table('deposit_record');
        if($db->query("update {$user_table} set user_money={$money_value} where user_id={$user_id}")){
            $create_time = time();
            $db->query("insert into {$record_table} (user_id,card_no,pos_no,amount,create_time) values ({$user_id}, '{$this->cardNo}', '{$this->posNo}', '{$amount}', '{$create_time}')");
        }
        $this->responseData['user_money'] = $money_value;
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
            $this->errMsg = "未找到卡号：{$this->cardNo}";
            return;
        }
        if(!$record['is_surplus_open']){
            $this->errMsg = "该卡号没有开通支付密码，请先开通支付密码，然后提现";
            return;
        }
        if(empty($_GET['surplus_pwd'])){
            $this->errMsg = '支付密码不能为空';
            return;
        }
        if($record['surplus_password'] != $_GET['surplus_pwd']){
            $this->errMsg = '支付密码错误';
            return;
        }
        if($amount > ($record['user_money'] + $record['credit_line'])){
            $this->errMsg = '余额不足';
            return;
        }
        if($record['user_money'] >= $amount){
            $user_money = $record['user_money'] - $amount;
            $credit_line = $record['credit_line'];
        }else{
            $user_money = '0.00';
            $credit_line = $record['user_money'] + $record['credit_line'] - $amount;
        }
        $user_id = $record['user_id'];
        $record_table = $ecs->table('cash_record');
        if($db->query("update {$user_table} set user_money={$user_money},credit_line={$credit_line} where user_id={$user_id}")){
            $create_time = time();
            $db->query("insert into {$record_table} (user_id,card_no,pos_no,cash,create_time) values ({$user_id}, '{$this->cardNo}', '{$this->posNo}', '{$amount}', '{$create_time}')");
        }
        $this->responseData['record_id'] = $db->insert_id();
        $this->responseData['user_money'] = $user_money;
        $this->responseData['credit_line'] = $credit_line;
    }

    private function cashdoneHandler()
    {
        if(empty($_GET['record_id'])){
            $this->errMsg = '充值ID不能为空';
            return;
        }
    }
    
    private function tradeHandler()
    {
        require(ROOT_PATH . 'includes/lib_order.php');
        include_once('includes/lib_clips.php');
        include_once('includes/lib_payment.php');
    }

    private function tradedoneHandler()
    {
        if(empty($_GET['order_id'])){
            $this->errMsg = '订单ID不能为空';
            return;
        }
    }
    
    private function queryHandler()
    {
        
    }
    
    public function processRequest()
    {
        if(!$this->checkParams()){
            echo json_encode(array('err_code'=>$this->errCode, 'err_msg'=>$this->errMsg, 'data'=>$this->responseData));
            return;
        }
        $handler = "{$this->action}Handler";
        $this->$handler();
        var_dump(array('err_code'=>$this->errCode, 'err_msg'=>$this->errMsg, 'data'=>$this->responseData));
    }
}
$apiMember = new ApiMember();
$apiMember->processRequest();
?>