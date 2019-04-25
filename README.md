# 概要
本库使用interface规范，工厂模式编写，代码质量高，统一规范。

简单说好处就是，在控制器里，你只需要写几行代码，你就可以对接多个支付。想要做到这一点，需要前端向传递服务端使用哪个支付驱动代码，服务端再根据支付驱动代码去调用相应的支付程序。

美中不足的是，部分代码是用php7的新特性写的，不兼容老版本的，我们做开发的特别是新项目自然是要走在技术的前端才对。

# 目录
* [安装](#安装)
* [支付驱动代码列表](#支付驱动代码列表)
    * [支付宝支付](#支付宝支付)
    * [微信支付](#微信支付)
    * [首信易支付](#首信易支付)
    * [银联](#银联)
    * [PaySsion](#PaySsion)
    * [PayPal](#PayPal)
    * [苹果支付](#苹果支付)
* [统一方法](#统一方法)
    * 初始化
    * 生成app签名
    * 异步通知
    * 退款（原路退回）
    * 设置自定义参数
* [苹果内购](#苹果内购)
    * [苹果流程说明](#苹果流程说明)
    * [苹果配置](#苹果配置)
    * [苹果获取订单参数](#苹果获取订单参数)
    * [苹果漏单处理](#苹果漏单处理)
* [支付宝](#支付宝)
    * 支付宝配置
    * 支付宝获取异步通知参数
* [微信](#微信)
    * 微信配置
    * 微信获取异步通知参数
    * 微信分转换为元
* [paypal](#paypal)
    * payPal配置
    * payPal获取异步通知参数
* [payssion](#payssion)
    * payssion配置
* [银联](#银联)
    * 银联说明
    * 银联配置
    * 获取银联流水号
* [首信易](#首信易)
    * 首信易异步通知



# 功能说明
1、支持：支付宝、微信、银联、payPal、payssion，首信易支付

2、主要功能：全部支持支付和验证异步通知

3、支付宝、微信：可生成app签名，可原路退款

4、通过工厂服务可以一次调用出所有支持的支付平台的app参数


## 安装
composer require xing.chen/payment dev-master

## 支付驱动代码列表
说明：在此方法传递参数时传入此代码即调用相应的支付驱动
\xing\payment\drive\PayFactory::getInstance('支付驱动代码')
#### 支付宝支付
aliPay
#### 微信支付
weChatPay
#### 首信易支付
BeijinPay
#### 银联
UnionPay
#### PaySsion
PaySsion
#### PayPal
PayPal
#### 苹果支付
ApplePay

## 统一方法
### 初始化
```php
<?php

$payName = '支付驱动代码'; // 支付驱动代码
$payInstance = \xing\payment\drive\PayFactory::getInstance($payName)->init('支付驱动 的配置，详情看配置篇');
```

### 生成app签名
```php
<?php
// 单个
$sign = $payInstance->set('订单号', '金额', '支付标题（商品名）')->getAppParam();

// 同时获取微信和支付的app支付签名
$payChannel= \xing\payment\drive\PayFactory::getAppsParam([
    'aliPay' => $aliConfig,
    'weChatPay' => $wechatConfig
], '订单号', '金额', '支付标题（商品名）');
```

### 异步通知
```php
<?php
// 如无特别说明，传递的参数都是是$_POST，但有些支付厂商不能使用$_POST，如paypal，微信等，请参考获取异步通知参数说明
if (!$payInstance->validate($_POST)) 
    throw new \Exception('非法请求');
```

### 退款

```php
<?php
$payInstance->set('订单号', '退款金额')->refund();
```
### 设置自定义参数
```php
<?php 
$payInstance->customParams('自定义参数（字符串）');
```

## 苹果内购
### 苹果流程说明
。

整个支付流程建议为：
1、如果需要跟踪客户订单或是保持和支付宝微信的支付流程一致，可以先在服务端生成订单信息，并返回orderinfo参数，客户端保存好订单号。否则这一步可以跳过。
2、客户端发起购买
3、用户确认并支付
4、支付成功后，客户端保存成功的数据，向服务端发送receipt数据以及订单号
5、服务端根据服务端数据库的订单状态或保存的receipt原始订单id来判断是否使用过，避免刷单，如未使用，则发起验证，验证通过保存receipt为已使用。

### 苹果配置
```php
<?php

$appleSet = [
    'sandbox' => false, // 是否沙箱模式
    'secret' => 'app专用共享密钥',
];
```

### 苹果获取订单参数
说明：和支付宝，微信的生成app签名的方法一样，不同的是，第4个参数为产品id，这样可以使后端一套代码应付多个支付平台，并且使苹果内购支付前的流程和支付宝微信相似。
```php
<?php

$orderInfo = $payInstance->set('订单号', '金额', '', '产品id')->getAppParam();
```

### 苹果漏单处理
有三个步骤有可能造成漏单：

1、服务端并不能保证100%在线。

2、用户网络也无法保证100%连通。

3、苹果服务器不稳定。

解决方案建议：

客户端保存支付成功数据，在未得到服务成功的信号前，一直存在，并有不断重启请求任务的机制，并且app重启也能重启此任务。

## 支付宝
### 支付宝配置
```php
<?php

// 支付宝配置
$aliConfig = [
     'title' => '支付宝支付',
     'appId' => '支付宝appId',
     'notifyUrl' => '异步通知 url',

     'alipayrsaPublicKey' => '支付宝公钥（字符串），详情请查看支付宝生成公钥的文档',

     'rsaPrivateKey' => '支付宝私钥（字符串），详情请查看支付宝生成私钥的文档',
 ];
```
### 支付宝获取异步通知参数
```php
<?php
$params = $_POST['passback_params']; // 自定义参数
$orderSn = $_POST['out_trade_no']; // 订单号
$payMoney = $_POST['total_amount']; // 支付金额
```

## 微信
### 微信配置
```php
<?php

// 微信
$wechatConfig = [
    'title' => '微信支付',
    'appId' => '微信支付appId',
    'mchId' => '商户id',
    'notifyUrl' => '异步通知 url',
    // 请换成你自己的相应的文证书件地址
    'SSL_CERT_PATH' =>  '(绝对路径)apiclient_cert.pem',
    'SSL_KEY_PATH' => '(绝对路径)apiclient_key.pem',
];

```

### 微信获取异步通知参数
```php
<?php

# 获取微信异步通知传来的参数
$post = $payment->getNotifyParams();
# 这是自定义参数
$drive = $post['attach'] ?? ''; 
# 订单号
$orderSn = $post['out_trade_no'];
```

### 微信分转换为元
```php
<?php
# 获取微信异步通知传来的参数
$post = $payment->getNotifyParams();
# 获取和订单一致的支付金额（将分转为元）
$payMoney = $payment->centsToYuan($post['total_fee']);
```
## 微信

## paypal
### payPal配置
```php
<?php
$config = [
    'clientId' => '商家id',
    'clientSecret' => 'Secret',
    'sandbox' => false, // 是否开启沙箱模式
];
```
### payPal获取异步通知参数
```php
<?php
$requestBody = file_get_contents('php://input');
```
# payssion
### payssion配置
```php
<?php
$set = [
   'apiKey' => 'apiKey',
   'secretKey' => 'secretKey'
];
```

## 银联
### 银联说明

手机控件支付：银联需要先获取流水号再返回给前端app，app支付成功后才能成功接收银联的异步通知结果。
### 银联配置

```php
<?php
$test = true; // 是否测试环境
$config = [
    // 配置和证书的路径
  'certsPath' => dirname(__DIR__) . '/config/certs'.(!$test ? '' : '_test').'/',
  'merId' => $test ? 777290058110048 : '商户号',
];
/**
 * certsPath文件说明：
 * 以下文件当你和银联那边对接通之后，他们的资源包会包含以下文件，除了复制到程序里之外，你还需要根据银联的教程将部分文件配置好。
 * 
 * 生产环境 certsPath 应包含的文件
 * config/certs/acp_prod_enc.cer
 * config/certs/acp_prod_middle.cer
 * config/certs/acp_prod_root.cer
 * config/certs/acp_sdk.ini
 * config/certs/cfca.cer
 * config/certs/cfca.pfx
 * 
 * 
 * 测试环境 certsPath 应包含的文件
 * config/certs_test/acp_sdk.ini
 * config/certs_test/acp_test_enc.cer
 * config/certs_test/acp_test_middle.cer
 * config/certs_test/acp_test_root.cer
 * config/certs_test/acp_test_sign.pfx
 */
```

### 获取银联流水号
```php
<?php

$orderSn = '订单号';
$payMoney = '支付金额/单位分';
try {
    $tn = UnionPay::init($config)->createOrder($orderSn, $payMoney);
} catch (\Exception $e) {
    throw $e;
}
```

# 首信易支付
### 首信易配置
```php
<?php
$conifg = [
    'apiKey' => 'apiKey',
    'checkValidate' => 'md5', // 验证方式
    'pemPath' => '证书路径', 
];

```


### 首信易异步通知
```php
<?php

try {

    $orderSn = $_REQUEST['v_oid'] ?? '';
    if (empty($orderSn)) throw new \Exception('订单号没有获取到');
    if(BeijinPay::init([])->validate($_REQUEST)) {

        $count = $_REQUEST['v_count'] ?? 0;//订单个数
        if ($count <= 0) throw new \Exception('订单个数小于0');

        $v_oid=$_REQUEST['v_oid'];//订单编号组
        $v_pstatus=$_REQUEST['v_pstatus'];//支付状态组
        $v_amount=$_REQUEST['v_amount'];//订单支付金额
        $v_moneytype=$_REQUEST['v_moneytype'];//订单支付币种

        $sp = '|_|';
        $a_oid = explode($sp, $v_oid);
        $a_pstatus = explode($sp, $v_pstatus);
        $a_amount = explode($sp, $v_amount);

        // 通知可能包含多个订单通知，所以循环
        for ($i = 0; $i < $count; $i++) {

            $orderSn = preg_replace('/(.*)-/', '', $a_oid[$i]);
            if($a_pstatus[$i]=='1')
            {
                // 支付成功，业务代码
            }
        }

        exit('success');
    } else {
        throw new \Exception('验证订单失败');
    }

} catch(\Exception $e) {
    throw $e;
}
```