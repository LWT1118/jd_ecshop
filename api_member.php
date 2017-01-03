<?php
/**
 * $Author: liuweitao $
 * 终端接口
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_license.php');
require_once('includes/cls_json.php');



/*
 * 充值接口
 * add by liuweitao
 * */
function api_deposit($card_no, $amount)
{
    die('a');
    data_back(array('balance'=>'0'), '', RETURN_TYPE);  //返回数据
}
/*
 * 消费接口
 * add by liuweitao
 * */
function api_trade()
{

}
/*
 * 提现接口
 * add by liuweitao
 * */
function api_cash($card_no)
{

}

/*
 * 查询接口
 * add by liuweitao
 * */
function api_query($card_no)
{

}
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
        if(isset($_GET['data_type'])){
            $this->dataType = intval($_GET['data_type']);
            if($this->dataType != 0 && $this->dataType != 1){
                $this->dataType = 0;
            }
        }
        if(isset($_GET['card_no']) && !empty($_GET['card_no'])){
            $this->cardNo = $_GET['card_no'];
        }
        if(isset($_GET['action'])){
            $this->action = $_GET['action'];
        }
        if(isset($_GET['pos_no'])){
            $this->posNo = $_GET['pos_no'];
        }
    }
    
    private function depositHandler()
    {
        $this->responseData['balance'] = 0;
    }
    
    private function cashHandler()
    {
        $this->responseData['balance'] = 0;
    }
    
    private function tradeHandler()
    {
        
    }
    
    private function queryHandler()
    {
        
    }
    
    public function processRequest()
    {
        $handler = "{$this->action}Handler";
        $this->$handler();
        echo json_encode(array('err_code'=>$this->errCode, 'err_msg'=>$this->errMsg, 'data'=>$this->responseData));
    }
}
$apiMember = new ApiMember();
$apiMember->processRequest();
?>