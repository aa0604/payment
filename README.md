# payment
支付插件 支持：支付宝、微信、payPal、payssion

# 安装
composer require xing.chen/payment dev-master
## 插件介绍

本插件使用简单方便，扩展性强，它拥有如下特点：

1、各支付平台的类都继承使用interface接口规范，保证整个支付模块工作统一规范

2、使用工厂服务调度，可以方便的切换调用各个支付平台驱动

3、通过工厂服务可以一次调用出所有支持的支付平台的app参数

4、使用命名空间，可以方便的集成到TP3.2以上，YII2等主流框架中使用

## 注
1、本插件在正式项目中使用，按需求开发和更新

2、只支持app支付参数生成和异步通知

### 支付宝、微信使用示例
```php
<?php

// 生成支付宝app需要的参数
$aliConfig = [
     'title' => '支付宝支付',
     'appId' => '支付宝appId',
     'notifyUrl' => '异步通知 url',

     'alipayrsaPublicKey' => '支付宝公钥（字符串），详情请查看支付宝生成公钥的文档',

     'rsaPrivateKey' => '支付宝私钥（字符串），详情请查看支付宝生成私钥的文档',
 ];
$sign = \xing\payment\drive\PayFactory::getInstance('aliPay')
  ->init($aliConfig)
  ->set('订单号', '金额', '支付标题（商品名）')
//  ->customParams('自定义参数值，需要请取消注释')
  ->getSign();
  
// 生成微信app需要的参数
$wechatConfig = [
    'title' => '微信支付',
    'appId' => '微信支付appId',
    'mchId' => '商户id',
    'notifyUrl' => '异步通知 url',
    // 请换成你自己的相应的文证书件地址
    'SSL_CERT_PATH' =>  'vendor/xing.chen/payment/sdk/wechatPay/cert/apiclient_cert.pem',
    'SSL_KEY_PATH' => 'vendor/xing.chen/payment/sdk/wechatPay/cert/apiclient_key.pem',
];
$sign = \xing\payment\drive\PayFactory::getInstance('weChatPay')
  ->init($wechatConfig)
  ->set('订单号', '金额', '支付标题（商品名）')
//  ->customParams('自定义参数值，需要请取消注释')
  ->getSign();
 
// 配置数组：注意键名为相应正确的支付驱动英文名
$paySet = [
    'aliPay' => $aliConfig,
    'weChatPay' => $wechatConfig
];
$payChannel= \xing\payment\drive\PayFactory::getAppsParam($paySet, '订单号', '金额', '支付标题（商品名）');

```

### 异步通知回调示例
```php
<?php
# 支付宝异步通知

$payName = 'aliPay';
$r = \xing\payment\drive\PayFactory::getInstance($payName)->init($aliConfig)->validate($_POST);
exit($r ? 'success' : $r);

# 微信回调通知
$payName = 'weChatPay';
$r = \xing\payment\drive\PayFactory::getInstance($payName)->init($wechatConfig)->validate($_POST);
exit($r ? 'success' : $r);
```

# paypal
#### Notify
```php
<?php
$config = [
    'clientId' => '商家id',
    'clientSecret' => 'Secret',
];
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

# payssion
### notify
```php
<?php
$set = [
   'apiKey' => 'apiKey',
   'secretKey' => 'secretKey'
];
try {

    if(\xing\payment\drive\PaySsion::init($set)->validate($_POST)) {
        // 验证成功
    } else {
        throw new \Exception('验证支付失败');
    }
}
catch(\Exception $e)
{
    exit($e->getMessage());
}
```