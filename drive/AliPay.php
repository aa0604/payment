<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/8/21
 * Time: 10:24
 * 使用示例:
 * $pay = AliPay::init([])->set('111', 9.9)->params([]);
 * $sign = $pay->getSign();
 *
 * @property \xing\payment\sdk\aliPay\aop\AopClient $AopClient
 */

namespace xing\payment\drive;

class AliPay implements \xing\payment\core\PayInterface
{

    public $notifyUrl = '通过设置 init 方法的参数$config[notifyUrl] 来设置';

    private $config;

    private $params;

    public $AopClient;

    public $request;


    /**
     * 初始化
     * @param $config
     * @return AliPay
     */
    public static function init($config)
    {
        $class = new self();
        $class->config = $config;
        $class->notifyUrl = $config['notifyUrl'];

//        初始化支付宝和配置参数
        $class->AopClient = new \xing\payment\sdk\aliPay\aop\AopClient;
        $class->AopClient->appId = $config['appId'];
        $class->AopClient->rsaPrivateKey = $config['rsaPrivateKey'];  // 请填写开发者私钥去头去尾去回车，一行字符串
        $class->AopClient->alipayrsaPublicKey = $config['alipayrsaPublicKey']; // 请填写支付宝公钥，一行字符串
        $class->AopClient->signType = $config['signType'] ?? 'RSA2';  // 签名方式
        $class->AopClient->format = $config['format'] ?? 'json';
        $class->AopClient->format = $config['charset'] ?? 'UTF-8';
//        返回本类自身
        return $class;
    }

    /**
     * 设置主要参数
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @return $this
     */
    public function set($outOrderSn, $amount, $title = '', $body = '')
    {
        $this->params['out_trade_no'] = $outOrderSn;
        $this->params['body'] = $body;
        $this->params['subject'] = $title;
        $this->params['total_amount'] = $amount;
        $this->params['timeout_express'] = '30m';
        return $this;
    }

    public function params(array $params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function fineSet()
    {

    }

    /**
     * @return string
     */
    public function getSign()
    {
        $request = new \xing\payment\sdk\aliPay\aop\request\AlipayTradeAppPayRequest();
        $request->setNotifyUrl($this->notifyUrl);
        $request->setBizContent($this->params);
        $response = $this->AopClient->sdkExecute($request);
        return htmlspecialchars($response);
    }
}