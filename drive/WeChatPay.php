<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/8/21
 * Time: 11:26
 */

namespace xing\payment\drive;

use xing\payment\sdk\wechatPay\lib\WxPayApi;
use xing\payment\sdk\wechatPay\WxPayUnifiedOrder;

class WeChatPay implements \xing\payment\core\PayInterface
{

    public $notifyUrl = '通过设置 init 方法的参数$config[notifyUrl] 来设置';
    public $returnUrl = '同上，可空';

    private $config;
    private $payObject;

    public static function init($config)
    {

        $class = new self();
        $class->config = $config;
        $class->notifyUrl = $config['notifyUrl'];
        $class->returnUrl = $config['returnUrl'] ?? '';

        return $class;
    }

    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {

        $this->payObject = new WxPayUnifiedOrder();
        $this->payObject->SetBody($body);
        $this->payObject->SetOut_trade_no($outOrderSn);
        $this->payObject->SetTotal_fee($amount);
        $this->payObject->SetTime_start(date("YmdHis"));
        $this->payObject->SetTime_expire(date("YmdHis", time() + 600));
        $this->payObject->SetNotify_url($this->notifyUrl);
        $this->payObject->SetTrade_type("APP");
        $this->payObject->SetOpenid($this->config['openId'] ?? '');
        $this->payObject->SetAppid($this->config['appId']);//公众账号ID
        $this->payObject->SetMch_id($this->config['mchId'] ?? '');//商户号
        $this->payObject->SetNotify_url($this->notifyUrl);//异步通知url
        $this->payObject->key = $this->config['key'] ?? '';
        $rootPath = dirname(dirname(dirname(dirname(__DIR__)))) . '/';
        define('SSLCERT_PATH', $rootPath . $this->config['SSL_CERT_PATH']);
        define('SSLKEY_PATH', $rootPath . $this->config['SSL_KEY_PATH']);
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
        $return = WxPayApi::unifiedOrder($this->payObject, 15);
        if ($return['return_code'] == 'FAIL') throw new \Exception($return['return_msg']);
        return json_encode($return);
    }


    public function autoActionFrom()
    {

    }

    public function validate($post = null)
    {

    }

}