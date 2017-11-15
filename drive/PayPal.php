<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/11/10
 * Time: 15:24
 */

namespace xing\payment\drive;


use yii\db\Exception;

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

    /**
     * @param $config
     * @return PayPal
     * @throws \Exception
     */
    public static function init($config)
    {

        $class = new self();
        $class->config = $config;
        if (!isset($config['clientId']) || empty($config['clientId']))
            throw new \Exception('PayPal商户id未设置');

        if (!isset($config['clientSecret']) || empty($config['clientSecret']))
            throw new \Exception('PayPal Secret 未设置');

        $class->apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential($config['clientId'], $config['clientSecret']
            )
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

    }
    public function params(array $params)
    {

    }
    public function getAppParam()
    {

    }

    public function autoActionFrom()
    {

    }

    /**
     * 验证返回信息
     * @param null $requestBody
     * @return bool
     */
    public function validate($requestBody = null)
    {
        $headers = array (
            'Client-Pid' => '14910',
            'Cal-Poolstack' => 'amqunphttpdeliveryd:UNPHTTPDELIVERY*CalThreadId=0*TopLevelTxnStartTime=1579e71daf8*Host=slcsbamqunphttpdeliveryd3001',
            'Correlation-Id' => '958be65120106',
            'Host' => 'shiparound-dev.de',
            'User-Agent' => 'PayPal/AUHD-208.0-25552773',
            'Paypal-Auth-Algo' => 'SHA256withRSA',
            'Paypal-Cert-Url' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-360caa42-fca2a594-a5cafa77',
            'Paypal-Auth-Version' => 'v2',
            'Paypal-Transmission-Sig' => 'eDOnWUj9FXOnr2naQnrdL7bhgejVSTwRbwbJ0kuk5wAtm2ZYkr7w5BSUDO7e5ZOsqLwN3sPn3RV85Jd9pjHuTlpuXDLYk+l5qiViPbaaC0tLV+8C/zbDjg2WCfvtf2NmFT8CHgPPQAByUqiiTY+RJZPPQC5np7j7WuxcegsJLeWStRAofsDLiSKrzYV3CKZYtNoNnRvYmSFMkYp/5vk4xGcQLeYNV1CC2PyqraZj8HGG6Y+KV4trhreV9VZDn+rPtLDZTbzUohie1LpEy31k2dg+1szpWaGYOz+MRb40U04oD7fD69vghCrDTYs5AsuFM2+WZtsMDmYGI0pxLjn2yw==',
            'Paypal-Transmission-Time' => '2016-09-21T22:00:46Z',
            'Paypal-Transmission-Id' => 'd938e770-8046-11e6-8103-6b62a8a99ac4',
            'Accept' => '*/*',
        );
        $headers = getallheaders();
        $headers = array_change_key_case($headers, CASE_UPPER);

        $signatureVerification = new \PayPal\Api\VerifyWebhookSignature();
        $signatureVerification->setWebhookId($this->config['WebhookID']); // Note that the Webhook ID must be a currently valid Webhook that you created with your client ID/secret.
        $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
        $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
        $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
        $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
        $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);

        $signatureVerification->setRequestBody($requestBody);
        $request = clone $signatureVerification;
        try {
            $output = $signatureVerification->post($this->apiContext);
            $this->response = $output->getVerificationStatus();
        } catch (\Exception $e) {
//            throw $e;
            $this->response = $e->getMessage();
            return false;
        }
        return true;
    }
}