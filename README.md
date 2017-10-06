# payment
支付插件 支持支付宝和微信
## 插件介绍

本插件使用简单方便，扩展性强，它拥有如下特点：

1、各支付平台的类都继承使用interface接口规范，保证整个支付模块工作统一规范

2、使用工厂服务调度，可以方便的切换调用各个支付平台驱动

3、通过工厂服务可以一次调用出所有支持的支付平台的app参数

4、使用命名空间，可以方便的集成到TP3.2以上，YII2等主流框架中使用

## 注意
1、本插件在正式项目中使用，按需求开发和更新
2、暂时只支持app支付（支付宝闲着没事做弄了 web跳转支付）

### 使用说明
1、将配置保存到PHP文件中，如:
```$xslt
namespace common\map;

class PaymentSetMap
{

    public static $set = [
        'aliPay' => [
            'title' => '支付宝支付',
            'appId' => '支付宝appId',
            'notifyUrl' => '异步通知 url',

            'alipayrsaPublicKey' => '支付宝公钥（字符串），详情请查看支付宝生成公钥的文档',

            'rsaPrivateKey' => '支付宝私钥（字符串），详情请查看支付宝生成私钥的文档',
        ],

        'weChatPay' => [
            'title' => '微信支付',
            'appId' => '微信支付appId',
            'mchId' => '商户id',
            'notifyUrl' => '异步通知 url',
            // 证书地址
            'SSL_CERT_PATH' =>  'vendor/xing.chen/payment/sdk/wechatPay/cert/apiclient_cert.pem',
            'SSL_KEY_PATH' => 'vendor/xing.chen/payment/sdk/wechatPay/cert/apiclient_key.pem',
        ],
    ];


    /**
     * 读取某个支付设置
     * @param $name
     * @return mixed
     */
    public static function getSet($name)
    {
        return static::$set[$name];
    }

    /**
     * 读取所有支付设置
     * @return array
     */
    public static function getPayments()
    {
        return self::$set;
    }
}
```
2、调用设置，使用工厂切换支付驱动，如：PayFactory::getInstance('aliPay')
### 使用示例
```

$paySet = PaymentSetMap::getPayments();
// 生成全部app需要的参数（数组）
$payChannel= \xing\payment\drive\PayFactory::getAppsParam($paySet, '订单号', '金额', '支付标题（商品名）');

 
// 生成支付宝app需要的参数
$payName = 'aliPay';
$set = PaymentSetMap::getSet($payName);
\xing\payment\drive\PayFactory::getInstance($payName)
  ->init($set)
  ->set('订单号', '金额', '支付标题（商品名）')
  ->getSign();
```

### 异步通知回调示例
```$xslt
# 阿里异步通知
try {
    $payName = 'aliPay';
    $set = PaymentSetMap::getSet($payName);
    $r = PayFactory::getInstance($payName)->init($set)->validate($_POST);
    exit($r ? 'success' : $r);
} catch (\Exception $e) {
    exit($e->getMessage());
}
```
