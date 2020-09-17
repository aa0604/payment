<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/8/21
 * Time: 11:26
 */

namespace xing\payment\drive;

use xing\payment\sdk\wechatPay\lib\WxPayApi;
use xing\payment\sdk\wechatPay\lib\WxPayRefund;
use xing\payment\sdk\wechatPay\WxPayUnifiedOrder;

/**
 * Class WeChatPay
 * @property WxPayUnifiedOrder $payObject
 * @property string $notifyUrl
 * @property string $returnUrl
 * @property string $config
 * @package xing\payment\drive
 */

class WeChatPay implements \xing\payment\core\PayInterface
{

    public $notifyUrl = '通过设置 init 方法的参数$config[notifyUrl] 来设置';
    public $returnUrl = '同上，可空';

    private $config;
    private $payObject;

    private $orderSn;
    private $amount;


    public static function init($config)
    {

        $class = new self();
        $class->config = $config;
        $class->notifyUrl = $config['notifyUrl'];
        $class->returnUrl = $config['returnUrl'] ?? '';
        $class->payObject = new WxPayUnifiedOrder();
        $class->payObject->SetOpenid($class->config['openId'] ?? '');
        $class->payObject->SetAppid($class->config['appId']);//公众账号ID
        $class->payObject->SetMch_id($class->config['mchId'] ?? '');//商户号

        $class->payObject->key = $class->config['key'] ?? '';
        $rootPath = dirname(dirname(dirname(dirname(__DIR__)))) . '/';
        !defined('SSLCERT_PATH') && define('SSLCERT_PATH', $rootPath . $class->config['SSL_CERT_PATH']);
        !defined('SSLKEY_PATH') && define('SSLKEY_PATH', $rootPath . $class->config['SSL_KEY_PATH']);
        !defined('WECHAT_KEY') && define('WECHAT_KEY', $class->config['key']);
        !defined('WECHAT_APPID') && define('WECHAT_APPID', $class->config['appId']);
        !defined('WECHAT_MCHID') && define('WECHAT_MCHID', $class->config['mchId']);
//        define('WECHAT_APPSECRET', $class->config['key']);
        return $class;
    }

    /**
     * 100分换成1元
     * @param $amount
     * @return int
     */
    public function centsToYuan($cents)
    {
        return $cents / 100;
    }


    /**
     * 1元钱换成100分钱
     * @param $amount
     * @return int
     */
    private function yuanToCents($yuan)
    {
        return intval($yuan * 100);
    }

    /**
     * 获取异步通知传来的值
     * @return array
     * @throws \Exception
     */
    public function getNotifyParams()
    {

        $xml = file_get_contents("php://input");
        
        $input = new \xing\payment\sdk\wechatPay\WxPayResults();
        $input->FromXml($xml);
        return $input->GetValues();
    }

    /**
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @param string $intOrderSn
     * @return WeChatPay
     */
    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {
        $this->orderSn = $outOrderSn;

        # 元换分
        $amount = $this->yuanToCents($amount);
        $this->amount = $amount;

        $this->payObject->SetBody($body ?: $title);
        $this->payObject->SetOut_trade_no($outOrderSn);
        $this->payObject->SetTotal_fee($amount);
        $this->payObject->SetTime_start(date("YmdHis"));
        $this->payObject->SetTime_expire(date("YmdHis", time() + 600));
        $this->payObject->SetNotify_url($this->notifyUrl);
        $this->payObject->SetTrade_type('APP');
        $this->payObject->SetNotify_url($this->notifyUrl);//异步通知url
        return $this;
    }

    public function params(array $params)
    {
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function customParams($value)
    {
        $this->payObject->SetAttach($value);
        return $this;
    }

    public function getAppParam()
    {
        $return = WxPayApi::appUnifiedOrder($this->payObject, 60);
        return json_encode($return);
    }
    
    public function getMiniProgramParam()
    {
        $this->payObject->SetTrade_type('JSAPI');
        $return = WxPayApi::appUnifiedOrder($this->payObject, 60);
        return json_encode($return);
    }


    public function autoActionFrom()
    {

    }

    public function validate($post = null)
    {
        $notify = new \xing\payment\extendSdk\weChat\WeChatNotifyExtend();
        $notify->Handle(true);
        return $notify->GetReturn_code() == 'SUCCESS';
    }

    /**
     * 原路退款
     * @param string $reason
     * @return array 成功时返回，其他抛异常
     * @throws \xing\payment\sdk\wechatPay\lib\WxPayException
     */
    public function refund($reason = '')
    {

        $input = new WxPayRefund();
        $input->SetOut_trade_no($this->orderSn);
        $input->SetTotal_fee($this->amount);
        $input->SetRefund_fee($this->amount);
        $input->SetOut_refund_no($this->config['mchId'].date("YmdHis"));
        $input->SetOp_user_id($this->config['mchId']);
        $result = WxPayApi::refund($input);
        return isset($result['return_code']) && $result['return_code'] == 'SUCCESS';
    }

}