<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/8/21
 * Time: 11:03
 */

namespace xing\payment\drive;



class PayFactory
{
    private static $payDrive = [
        'aliPay' => '\xing\payment\drive\AliPay',
        'weChatPay' => '\xing\payment\drive\WeChatPay',
        'BeijinPay' => '\xing\payment\drive\BeijinPay',
        'UnionPay' => '\xing\payment\drive\UnionPay',
        'PaySsion' => '\xing\payment\drive\PaySsion',
        'PayPal' => '\xing\payment\drive\PayPal',
        'ApplePay' => '\xing\payment\drive\ApplePay',
    ];

    /**
     * 返回单例
     * @param $payInstanceName
     * @return AliPay|WeChatPay|ApplePay|BeijinPay|UnionPay|PaySsion|PayPal
     */
    public static function getInstance($payInstanceName)
    {
        static $class;
        if (isset($class[$payInstanceName])) return $class[$payInstanceName];
        return $class[$payInstanceName] = new self::$payDrive[$payInstanceName]();
    }

    /**
     * 获取各个app支付需要的参数
     * @param $sets
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @param string $intOrderSn
     * @return array
     */
    public static function getAppsParam($sets, $outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {
        $return = [];
        foreach (self::$payDrive as $name => $class) {
            $return[$name] = static::getInstance($name)
                ->init($sets[$name])
                ->set($outOrderSn, $amount, $title, $body, $intOrderSn)
                ->getAppParam();
        }
        return $return;
    }
}