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
     * @return mixed
     */
    public function set($outOrderSn, $amount, $title = '', $body = '');

    /**
     * 设置其他参数
     * @param $params
     * @return mixed
     */
    public function params(array $params);

    /**
     * 返回签名
     * @return mixed
     */
    public function getSign();

}