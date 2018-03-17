# 概要
本库使用interface规范，工厂模式编写，代码质量高，统一规范。美中不足的是，部分代码是用php7的新特性写的，不兼容老版本的，我们做开发的特别是新项目自然是要走在技术的前端才对。

# 功能说明
1、支持：支付宝、微信、payPal、payssion

2、主要功能：全部支持支付和验证异步通知

3、支付宝、微信：可生成app签名，可原路退款

4、通过工厂服务可以一次调用出所有支持的支付平台的app参数


# 安装
composer require xing.chen/payment dev-master


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

## 支付宝、微信退款（原路退回）
```php
<?php
$payName = 'aliPay或weChatPay';
\xing\payment\drive\PayFactory::getInstance($payName)->init('上面的微信或支付宝配置')->set('订单号', '退款金额')->refund();
?>
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
if (!$r) exit('验证失败');
# 获取微信异步通知传来的参数
$post = $payment->getNotifyParams();
# 这是自定义参数
$drive = $post['attach'] ?? ''; 
# 订单号
$orderSn = $post['out_trade_no'];
# 获取和订单一致的支付金额（将分转为元）
$payMoney = $payment->centsToYuan($post['total_fee']);
# 其他成功业务代码 
# ……
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