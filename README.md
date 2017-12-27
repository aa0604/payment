# payment
支付插件 支持：支付宝、微信、payPal、俄罗斯qiwi（正在开发）
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
```php
<?php
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
        
    'payPal' => [
        'clientId' => '商家id',
        'clientSecret' => 'Secret',
        // 钩子id 到paypal后台增加webhook
        'WebhookID' => 'xxxxxxxxx'
        ]
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
?>
```
2、调用设置，使用工厂切换支付驱动，如：PayFactory::getInstance('aliPay')或直接使用类 \xing\payment\drive\AliPay::init($set)->validate();
### 支付宝、微信使用示例
```php
<?php

// 生成支付宝app需要的参数
$payName = 'aliPay';
$set = PaymentSetMap::getSet($payName);
$sign = \xing\payment\drive\PayFactory::getInstance($payName)
  ->init($set)
  ->set('订单号', '金额', '支付标题（商品名）')
  ->getSign();
  
// 生成微信app需要的参数
$payName = 'weChatPay';
$set = PaymentSetMap::getSet($payName);
$sign = \xing\payment\drive\PayFactory::getInstance($payName)
  ->init($set)
  ->set('订单号', '金额', '支付标题（商品名）')
  ->customParams('自定义参数值')
  ->getSign();
 
// 生成app所有支付方式需要的参数（数组）
$paySet = PaymentSetMap::getPayments();
$payChannel= \xing\payment\drive\PayFactory::getAppsParam($paySet, '订单号', '金额', '支付标题（商品名）');

```

### 异步通知回调示例
```php
# 支付宝异步通知
try {
    $payName = 'aliPay';
    $set = PaymentSetMap::getSet($payName);
    $r = PayFactory::getInstance($payName)->init($set)->validate($_POST);
    exit($r ? 'success' : $r);
} catch (\Exception $e) {
    exit($e->getMessage());
}

# 微信回调通知可参考支付宝异步通知
```

### paypal 使用示例
#### Notify
```php
<?php
$requestBody = file_get_contents('php://input');
try {
    $isSandbox = false; // 是否沙箱环境
    $bool = \xing\payment\drive\PayPal::init($config)->sandbox($isSandbox)->validate($requestBody);
    if (!$bool) throw new \Exception('验证失败');
    // 验证通过，订单业务代码.....
} catch (\Exception $e) {
    exit($e->getMessage());
}
```

## payssion
### notify
```php
<?php
$set = [
   'apiKey' => 'apiKey',
   'secretKey' => 'secretKey'
];
$orderSn = $_POST['order_id'] ?? null;
try {

    if(\xing\payment\drive\PaySsion::init($set)->validate($_POST)) {

    } else {
        throw new \Exception('');
    }
}
catch(\Exception $e)
{
    Yii::error($e->getMessage(), __METHOD__);
    return $this->error($e->getMessage(), $e->getCode());
}
```