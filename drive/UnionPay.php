<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2018/7/9
 * Time: 15:16
 */

namespace xing\payment\drive;


use xing\payment\sdk\xingUnionPay\AcpService;
use xing\payment\sdk\xingUnionPay\SDKConfig;

class UnionPay implements \xing\payment\core\PayInterface
{

    // 商户号
    public $merId = '';
    /**
     * 初始化
     * @param $config
     * @return $this
     */
    public static function init($config)
    {
        if (!isset($config['certsPath']) || empty($config['certsPath']))
            throw new \Exception('请设置ini配置文件路径');
        define('UNION_PATH', $config['certsPath']);

        $class = new self;
        $class->merId = $config['merId'] ?? $class->merId;
        if (empty($class->merId)) throw new \Exception('未设置后台通知地址');

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

    }

    /**
     * 返回签名
     * @return mixed
     */
    public function getAppParam()
    {

    }

    public function autoActionFrom()
    {

    }

    public function validate($post = null)
    {
        return AcpService::validate($post);
    }

    public function refund($reason = '')
    {

    }

    /**
     * 创建订单，获取银联流水号
     * @param int $orderSn 订单号
     * @param float $payMoney 交易金额
     * @return string
     */
    public function createOrder($orderSn, $payMoney)
    {
        $params = array(

            //以下信息非特殊情况不需要改动
            'version' => SDKConfig::getSDKConfig()->version,                 //版本号
            'encoding' => 'utf-8',				  //编码方式
            'txnType' => '01',				      //交易类型
            'txnSubType' => '01',				  //交易子类
            'bizType' => '000201',				  //业务类型
            'frontUrl' =>  SDKConfig::getSDKConfig()->frontUrl ?? '',  //前台通知地址
            'backUrl' => SDKConfig::getSDKConfig()->backUrl,	  //后台通知地址
            'signMethod' => SDKConfig::getSDKConfig()->signMethod,	              //签名方法
            'channelType' => '08',	              //渠道类型，07-PC，08-手机
            'accessType' => '0',		          //接入类型
            'currencyCode' => '156',	          //交易币种，境内商户固定156

            //TODO 以下信息需要填写
            'merId' => $this->merId,		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => $orderSn,	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => date('YmdHis'),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => $payMoney,	//交易金额，单位分，此处默认取demo演示页面传递的参数

            // 请求方保留域，
            // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
            // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
            // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
            //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
            // 2. 内容可能出现&={}[]"'符号时：
            // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
            // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
            //    注意控制数据长度，实际传输的数据长度不能超过1024位。
            //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
            //    'reqReserved' => base64_encode('任意格式的信息都可以'),

            //TODO 其他特殊用法请查看 pages/api_05_app/special_use_purchase.php
        );

        AcpService::sign ( $params ); // 签名
        $url = SDKConfig::getSDKConfig()->appTransUrl;

        $result_arr = AcpService::post ($params,$url);

        if(count($result_arr)<=0) { //没收到200应答的情况
            $this->printResult ($url, $params, "" );
            return;
        }

//        $this->printResult ($url, $params, $result_arr ); //页面打印请求应答数据
//        er($result_arr);

        if (!AcpService::validate ($result_arr) ){
            throw new \Exception("应答报文验签失败<br>\n");
            return;
        }


//        echo "应答报文验签成功<br>\n";
        if ($result_arr["respCode"] == "00"){
            return $result_arr["tn"];
            //成功
            //TODO
            echo "成功接收tn：" . $result_arr["tn"] . "<br>\n";
            echo "后续请将此tn传给手机开发，由他们用此tn调起控件后完成支付。<br>\n";
            echo "手机端demo默认从仿真获取tn，仿真只返回一个tn，如不想修改手机和后台间的通讯方式，【此页面请修改代码为只输出tn】。<br>\n";
        } else {
            //其他应答码做以失败处理
            //TODO
            throw new \Exception("失败：" . $result_arr["respMsg"] . "。<br>\n") ;
        }


    }


    /**
     * 打印请求应答
     *
     * @param
     *        	$url
     * @param
     *        	$req
     * @param
     *        	$resp
     */
    private function printResult($url, $req, $resp) {
        echo "=============<br>\n";
        echo "地址：" . $url . "<br>\n";
        echo "请求：" . str_replace ( "\n", "\n<br>", htmlentities ( \xing\payment\sdk\xingUnionPay\createLinkString ( $req, false, true ) ) ) . "<br>\n";
        echo "应答：" . str_replace ( "\n", "\n<br>", htmlentities ( \xing\payment\sdk\xingUnionPay\createLinkString ( $resp , false, false )) ) . "<br>\n";
        echo "=============<br>\n";
    }
}