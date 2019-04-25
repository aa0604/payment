<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/11/10
 * Time: 15:24
 */

namespace xing\payment\drive;



/**
 * @property ['clientId' => '','clientSecret' => '', 'WebhookID' => ''] $config
 * Class PayPal
 * @package xing\payment\drive
 */
class PayPal implements \xing\payment\core\PayInterface
{

    public $response;

    public $apiContext;

    private $config;

    // 网关
    private $gateway;

    // 沙箱模式开关
    private $sandbox = false;

    /**
     * @param $config
     * @return PayPal
     * @throws \Exception
     */
    public static function init($config)
    {

        $class = new self();
        $class->config = $config;
        isset($config['sandbox']) && $class->sandbox = $config['sandbox'];

        if (!isset($config['clientId']) || empty($config['clientId']))
            throw new \Exception('PayPal商户id未设置');

        if (!isset($config['clientSecret']) || empty($config['clientSecret']))
            throw new \Exception('PayPal Secret 未设置');

        $class->apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential($config['clientId'], $config['clientSecret'])
        );
        return $class;
    }

    public function createWebHook($url)
    {

        $webhook = new \PayPal\Api\Webhook();
        $webhook->setUrl($url);

        $webhookEventTypes = array();
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
            '{
        "name":"PAYMENT.AUTHORIZATION.CREATED"
    }'
        );
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
            '{
        "name":"PAYMENT.AUTHORIZATION.VOIDED"
    }'
        );
        $webhook->setEventTypes($webhookEventTypes);
        $request = clone $webhook;

        try {
            $output = $webhook->create($this->apiContext);
        } catch (\Exception $ex) {
            // Ignore workflow code segment
            if ($ex instanceof \PayPal\Exception\PayPalConnectionException) {
                $data = $ex->getData();
                $msg = "Created Webhook Failed. Checking if it is Webhook Number Limit Exceeded. Trying to delete all existing webhooks". $ex->getMessage();
                if (strpos($data, 'WEBHOOK_NUMBER_LIMIT_EXCEEDED') !== false) {
                    exit('这步是什么意思？');
                    require 'DeleteAllWebhooks.php';
                    try {
                        $output = $webhook->create($this->apiContext);
                    } catch (\Exception $ex) {
                        exit($msg . "Created Webhook". $ex->getMessage());
                        exit(1);
                    }
                } else {
                    exit($msg . "Created Webhook". $ex->getMessage());
                    exit(1);
                }
            } else {
                exit("Created Webhook". $ex->getMessage());
                exit(1);
            }
        }
        er("Created Webhook");

        return $output;
    }


    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {
        return $this;
    }
    public function params(array $params)
    {

    }
    public function customParams($value)
    {
        return $this;
    }
    public function getAppParam()
    {

    }

    public function autoActionFrom()
    {

    }

    /**
     * 设置沙箱模式
     * @param bool $switch
     * @return $this
     */
    public function sandbox($switch = true)
    {
        $this->sandbox = $switch;
        return $this;
    }
    /**
     * 验证返回信息
     * @param null $requestBody
     * @return bool
     */
    public function validate($requestBody = null)
    {

        $ipn = new \xing\payment\sdk\payPal\PaypalIPN();
        if($this->sandbox) $ipn->useSandbox();
        return $ipn->verifyIPN();
    }

    /**
     * 生成客户端令牌
     * @return string
     */
    public function createClientToken()
    {
        return $this->getGateway()->clientToken()->generate();
    }
    /**
     * 获取网关
     * @return \Braintree_Gateway
     */
    public function getGateway()
    {
        if (empty($this->gateway))
            $this->gateway = new \Braintree_Gateway(['accessToken' => $this->config['accessToken']]);
        return $this->gateway;
    }

    public function refund($reason = '')
    {

    }
}

/**
 * nginx 没有这个函数
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}