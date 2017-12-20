<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/12/20
 * Time: 15:00
 */

namespace xing\payment\drive;


class PaySsion implements \xing\payment\core\PayInterface
{
    public $apiKey;
    public $secretKey;
    // 付款结果状态
    public $resultState;

    public static function init($config)
    {

        if (!isset($config['apiKey']) || empty($config['apiKey'])) throw new \Exception('apiKey is empty');
        if (!isset($config['secretKey']) || empty($config['secretKey'])) throw new \Exception('secretKey is empty');
        $class = new self();
        $class->apiKey = $config['apiKey'];
        $class->secretKey = $config['secretKey'];

        return $class;
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

    public function validate($post = null)
    {
        $pm_id = $post['pm_id'];
        $amount = $post['amount'];
        $currency = $post['currency'];
        $order_id = $post['order_id'];
        $state = $this->resultState = $post['state'];

        if (empty($order_id)) throw new \Exception('order_id is empty');

        $check_array = array(
            $this->apiKey,
            $pm_id,
            $amount,
            $currency,
            $order_id,
            $state,
            $this->secretKey
        );

        $check_msg = implode('|', $check_array);
        $check_sig = md5($check_msg);
        $notify_sig = $post['notify_sig'];

        if ($notify_sig != $check_sig) return false;

        if ($state === 'completed') return true;

        return false;
    }
}