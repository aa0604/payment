<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2017/12/20
 * Time: 15:00
 */

namespace xing\payment\drive;


class BeijinPay implements \xing\payment\core\PayInterface
{
    
    # 证书路径
    public $pemPath;
    public $apiKey;
    public $checkValidate = 'md5';

    public static function init($config)
    {

        $class = new self();
        $class->apiKey = $config['apiKey'] ?? '';
        isset($config['checkValidate']) && $class->checkValidate = $config['checkValidate'];

        return $class;
    }

    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {

    }
    public function params(array $params)
    {

    }
    public function customParams($value)
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
        return strtolower($this->checkValidate) == 'md5' ? $this->md5Validate($post) : $this->pemValidate($post);
    }

    /**
     * md5方式验证
     * @param null $post
     * @return bool
     */
    private function md5Validate($post = null)
    {

//接收返回的参数
        $v_oid=$post['v_oid'];//订单编号组
        $v_pmode= urldecode($post['v_pmode']);//支付方式组
        $v_pstatus=$post['v_pstatus'];//支付状态组
        $v_pstring= urldecode($post['v_pstring']);//支付结果说明
        $v_count=$post['v_count'];//订单个数
        $v_mac=$post['v_mac'];//数字指纹（v_mac）
        $v_md5money=$post['v_md5money'];//数字指纹（v_md5money）
        $v_sign=$post['v_sign'];//验证商城数据签名（v_sign）
        $v_amount=$post['v_amount'];//订单支付金额
        $v_moneytype=$post['v_moneytype'];//订单支付币种


        $key = $this->apiKey;//商户的密钥
        $data1=$v_oid.$v_pmode.$v_pstatus.$v_pstring.$v_count;
        $mac= $this->hmac($key, $data1);

        $data2=$v_amount.$v_moneytype;
        $md5money= $this->hmac($key, $data2);

        return $mac == $v_mac or $md5money == $v_md5money;
    }

    /**
     * 官方给出的方法
     * @param $key
     * @param $data
     * @return string
     */
    private function hmac ($key, $data)
    {
        // 创建 md5的HMAC

        $b = 64; // md5加密字节长度
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key  = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
    }
    /**
     * 证书验证
     * @return bool
     */
    private function pemValidate($post = null)
    {

//接收返回的参数
        $v_oid=$post['v_oid'];//订单编号组
        $v_pstatus=$post['v_pstatus'];//支付状态组
        $v_count=$post['v_count'];//订单个数
        $v_mac=$post['v_mac'];//数字指纹（v_mac）
        $v_md5money=$post['v_md5money'];//数字指纹（v_md5money）
        $v_sign=$post['v_sign'];//验证商城数据签名（v_sign）


//读取公钥内容到$pubKey变量
        $pubKey = file_get_contents($this->pemPath);  //读取公钥内容到$pubKey变量
        $keyid = openssl_pkey_get_public($pubKey);   //获取公钥KeyID

        $verifydata=$v_oid.$v_pstatus.$v_amount.$v_moneytype.$v_count;
//echo strlen($v_sign);


        if(function_exists("hex2bin")){
            $v_sign=hex2bin($v_sign);   //高于php5.4版本建议用此函数，还原16进制数据
        }else{
            $v_sign=pack("H*",$v_sign); //低于php5.4版本请用此函数，还原16进制数据
        }

        $verify = openssl_verify($verifydata,$v_sign,$keyid);  //验证签名，成功返回1，失败返回非1或0


        return $verify === 1;
    }

    public function refund($reason = '')
    {

    }
}