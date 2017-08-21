<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/8/21
 * Time: 11:03
 */

namespace xing\payment\drive;


class Factory
{
    private static $payDrive = [
        'aliPay' => "AliPay",
        'weChatPay' => "WeChatPay",
    ];

    /**
     * @param $payInstanceName
     * @return AliPay|WeChatPay
     */
    public static function getInstance($payInstanceName)
    {
        return new self::$payDrive[$payInstanceName];
    }
}