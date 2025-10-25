<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2019/4/24
 * Time: 13:02
 */

namespace xing\payment\drive;


use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
    private $ch;

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
    public function validate($post = null, bool $sendBox = false)
    {
        if (empty($post['receipt'])) throw new \Exception('receipt为空');

        $post = json_encode(["receipt-data" => $post['receipt'], 'password' => $this->config['secret']]);
        $this->result = HttpHelper::post($this->sandbox ? $this->sandboxUrl : $this->url, $post);
        if (empty($this->result)) throw new \Exception('通讯失败');

        $return = json_decode($this->result, true);
        if ($return['status'] != 0) $this->setStatusError($return['status']);
        return true;
    }
    public function getOrderInfo($post = null, bool $sendBox = false)
    {
        $algorithm = 'ES256';
        $key = $this->config['privateKey'];
        $payload = [
            'iss' => $this->config['iss'],
            'aud' => 'appstoreconnect-v1',  //固定值
            'iat' => time(),
            'exp' => time() + 3600,
            'bid' => $this->config['bundleId'], //应用bundle_id
        ];
        $jwt = JWT::encode($payload, $key, $algorithm, $this->config['secretId']);
        $header = ['Authorization' => 'Bearer ' . $jwt];
        $url = 'https://api.storekit.itunes.apple.com/inApps/v1/transactions/' . $post['transactionIdentifier'];
        $sandboxUrl = 'https://api.storekit-sandbox.itunes.apple.com/inApps/v1/transactions/' . $post['transactionIdentifier'];

        // 获取订单信息
        $isSandbox = !$sendBox ? $this->sandbox : $sendBox;
        $this->result = $this->get($isSandbox ? $sandboxUrl : $url, $header);
        $result = json_decode($this->result, 1);
        if (!empty($result['errorCode'])) {
            throw new \Exception($result['errorMessage'], $result['errorCode']);
        }
        // 解密
        return static::verifyToken($result['signedTransactionInfo']);

    }
    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $Token 需要验证的token
     * @return bool|string
     */
    public static function verifyToken($Token)
    {
        $tokens = explode('.', $Token);
        if (count($tokens) != 3)
            return false;

        list($base64header, $base64payload) = $tokens;

        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']) || $base64decodeheader['alg'] != 'ES256')
            return false;

        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);

        return $payload;
    }
    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
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
    private function get($url, $header)
    {
//        hr($url);

        $this->ch = curl_init();
        //SSL证书
        if (preg_match('/https:/i',$url)){
            curl_setopt($this->ch,CURLOPT_SSL_VERIFYPEER,false);
        }
        curl_setopt($this->ch,CURLOPT_URL, $url);
        curl_setopt ($this->ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST,'GET'); //设置请求方式

        if ($header) {
            $httpHeader = array();
            foreach ($header as $key => $value) {
                array_push($httpHeader,  is_numeric($key) ? $value : $key.":".$value);
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $httpHeader);
        }
        curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, TRUE); // 获取的信息以文件流的形式


        $html = curl_exec($this->ch) ;
        curl_close($this->ch) ;

        return $html;
    }

    public function getResult()
    {
        return $this->result;
    }
}