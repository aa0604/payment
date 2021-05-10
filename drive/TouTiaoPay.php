<?php


namespace xing\payment\drive;

/**
 * Class TouTiaoPay
 * @property string $merchant_id
 * @property string $app_id
 * @property string $secret
 * @property string $title
 * @property string $sign_type
 * @property string $trade_type
 * @property string $version
 * @property string $orderSn
 * @property float $amount
 * @property string $notifyUrl
 * @property string $body
 * @property int $tradeTime
 * @property int $valid_time
 * @property array $otherSet
 * @property string $serviceType
 * @property array|string $customParam
 * @package xing\payment\drive
 */
class TouTiaoPay implements \xing\payment\core\PayInterface
{
    private $merchant_id;
    private $sign_type = 'MD5';
    private $version = '2.0';
    private $trade_type = 'H5';
    private $orderSn;
    private $notifyUrl = 'https://tp-pay.snssdk.com/paycallback';
    private $amount;
    private $title;
    private $body;
    public $tradeTime;
    public $valid_time = 86400;
    private $otherSet;
    private $serviceType;
    private $customParam;

    public static function init($config)
    {

        $class = new self();
        $class->config = $config;
        $class->merchant_id = $config['merchant_id'];
        $class->app_id = $config['app_id'];
        $class->secret = $config['secret'];
//        $class->notify_url = $config['notify_url'];
        return $class;
    }

    /**
     * 设置主要参数
     * @param $outOrderSn
     * @param $money 支付金额 单位：元
     * @param string $title
     * @param string $body
     * @param string $intOrderSn 第三方支付平台生成的订单号，如支付宝支付时返回的支付宝交易号，不是每个平台都需要
     * @return $this
     */
    public function set($outOrderSn, $money, $title = '', $body = '', $intOrderSn = '')
    {
        $this->orderSn = $outOrderSn;
        $this->amount = $this->yuanToCents($money);
        $this->title = $title;
        $this->body = $body;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function customParams($value)
    {
        $this->customParam = $value;
        return $this;
    }

    /**
     * 设置第三方支付
     * @param null $type
     * @param null $set
     * @return $this
     */
    public function setService($type = null, $set = null)
    {

        switch ($type) {
            case 'AliPay';
                $this->serviceType = 'AliPay';
                break;
            case 'WeChatPay':
                $this->serviceType = 'WeChatPay';
                break;
        }

        if (!empty($set)) $this->otherSet = $set;
        return $this;
    }

    /**
     * 设置其他参数
     * @param $params
     * @return $this
     */
    public function params(array $params)
    {
        foreach ($params as $k => $v) $this->$k = $v;
        return $this;
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
     * 返回签名参数
     * @return mixed
     */
    public function getAppParam()
    {
        $post = [
            'merchant_id' => $this->merchant_id,
            'app_id' => $this->app_id,
            'sign_type' => $this->sign_type,
            'timestamp' => (string) time(),
            'version' => $this->version,
            'trade_type' => $this->trade_type,
            'product_code' => 'pay',
            'payment_type' => 'direct',
            'out_order_no' => $this->orderSn,
            'uid' => $this->app_id,
            'total_amount' => $this->amount,
            'currency' => 'CNY',
            'subject' => $this->title,
            'body' => $this->body,
            'trade_time' => (string) ($this->tradeTime ?: time()),
            'valid_time' => (string) $this->valid_time,
            'notify_url' => $this->notifyUrl,
            'risk_info' => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? '']),
        ];

        // 设置第三方支付url参数
        if (!empty($this->serviceType)) {

            $service = \xing\payment\drive\PayFactory::getInstance($this->serviceType)
                ->init($this->otherSet)
                ->customParams($this->customParam)
                ->set($this->orderSn, $this->centsToYuan($this->amount), $this->title, $this->body);
            
            switch ($this->serviceType) {
                case 'AliPay';
                    $params = $service->getAppParam();
                    $post['alipay_url'] = $params;
                    break;
                case 'WeChatPay':
                    $post['wx_type'] = 'MWEB';
                    $service->payObject->SetTrade_type($post['wx_type']);
                    $result = $service->getH5Param();
                    $params = json_decode($result, 1);
                    $post['wx_url'] = $params['mweb_url'] ?? '';
                    break;
            }
        }
        $post['sign'] = $this->sign($post);
        return $post;
    }
    
    private function sign($post)
    {

        unset($post['risk_info']);
        ksort($post);
        $string = '';
        foreach ($post as $k => $v) $string .= '&' . $k . '=' . $v;
        $string = trim($string, '&');
        return md5($string . $this->secret);
    }

    public function autoActionFrom()
    {

    }

    public function validate($post = null)
    {

    }

    public function refund($reason = '')
    {

    }

}