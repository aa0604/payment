<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2019/4/24
 * Time: 13:02
 */

namespace xing\payment\drive;


use xing\helper\resource\HttpHelper;

/**
 * Class ApplePay
 * @property array $config 配置
 * @property string $result 返回结果
 * @property boolean $sandbox 沙箱模式
 * @package xing\payment\drive
 */
class ApplePay implements \xing\payment\core\PayInterface
{
    /**
     * @var string 测试时使用
     */
    private $sandboxUrl = 'https://sandbox.itunes.apple.com/verifyReceipt';
    /**
     * @var string 正式时使用
     */
    private $url = 'https://buy.itunes.apple.com/verifyReceipt';

    // 配置
    private $config;

    public $sandbox = false; // 沙箱模式
    private $result;
    private $body;

    public static function init($config)
    {
        $class = new self();
        $class->config = $config;
        isset($config['sandbox']) && $class->sandbox = $config['sandbox'];
        if (!isset($class->config['secret']) || empty($class->config['secret'])) throw new \Exception('app专用共享密钥不能为空，请设置到secret这个字段');
        return $class;
    }

    /**
     * 设置主要参数
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @param string $intOrderSn 第三方支付平台生成的订单号，如支付宝支付时返回的支付宝交易号，不是每个平台都需要
     * @return $this
     */
    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {
        $this->body = $body;
        return $this;
    }

    /**
     * 设置其他参数
     * @param $params
     * @return $this
     */
    public function params(array $params)
    {

    }

    /**
     *
     * @param $value
     * @return $this
     */
    public function customParams($value)
    {
        return $this;
    }

    /**
     * 返回签名
     * @return mixed
     */
    public function getAppParam()
    {
        return ['productid' => $this->body];
    }

    public function autoActionFrom(){}

    public function refund($reason = ''){}
    /**
     * 验证凭证
     * @param bool $sendBox 是否使用沙箱环境
     * @return bool
     */
    public function validate($post = null)
    {
        $receipt = $post['receipt'] ?? null;
        if (empty($receipt)) throw new \Exception('receipt为空');

        $post = json_encode(["receipt-data" => $receipt, 'password' => $this->config['secret']]);
        $this->result = HttpHelper::post($this->sandbox ? $this->sandboxUrl : $this->url, $post);
        if (empty($this->result)) throw new \Exception('通讯失败');

        $return = json_decode($this->result, true);
        if ($return['status'] != 0) $this->setStatusError($return['status']);
        return true;
    }
    /**
     * 设置状态错误消息
     * @param $status
     */
    private function setStatusError($status)
    {
        switch (intval($status)) {
            case 21000:
                $error = 'AppleStore不能读取你提供的JSON对象';
                break;
            case 21002:
                $error = 'receipt-data域的数据有问题';
                break;
            case 21003:
                $error = 'receipt无法通过验证';
                break;
            case 21004:
                $error = '提供的shared secret不匹配你账号中的shared secret';
                break;
            case 21005:
                $error = 'receipt服务器当前不可用';
                break;
            case 21006:
                $error = 'receipt合法，但是订阅已过期';
                break;
            case 21007:
                $error = 'receipt是沙盒凭证，但却发送至生产环境的验证服务';
                break;
            case 21008:
                $error = 'receipt是生产凭证，但却发送至沙盒环境的验证服务';
                break;
            default:
                $error = '未知错误';
        }
        throw new \Exception($error, $status);
    }
    /**
     * 返回交易id
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->returnData['receipt']['in_app'][0]['transaction_id'];
    }
    /**
     * curl提交数据
     * @param $receipt_data
     * @param string $password
     * @param $url
     * @return mixed
     */
    private function postData($receipt_data, $password, $url)
    {
        $postData = ["receipt-data" => $receipt_data, 'password' => $password];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}