<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/8/21
 * Time: 11:29
 */

namespace xing\payment\core;

interface PayInterface
{
    /**
     * 初始化
     * @param $config
     * @return mixed
     */
    public static function init($config);

    /**
     * 设置主要参数
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @param string $intOrderSn 第三方支付平台生成的订单号，如支付宝支付时返回的支付宝交易号，不是每个平台都需要
     * @return $this
     */
    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '');

    /**
     * 设置其他参数
     * @param $params
     * @return $this
     */
    public function params(array $params);

    /**
     *
     * @param $value
     * @return $this
     */
    public function customParams($value);

    /**
     * 返回签名
     * @return mixed
     */
    public function getAppParam();

    public function autoActionFrom();

    public function validate($post = null);

    public function refund($reason = '');
}