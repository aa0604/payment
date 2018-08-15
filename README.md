# 概要
本库使用interface规范，工厂模式编写，代码质量高，统一规范。美中不足的是，部分代码是用php7的新特性写的，不兼容老版本的，我们做开发的特别是新项目自然是要走在技术的前端才对。

# 功能说明
1、支持：支付宝、微信、银联、payPal、payssion，首信易支付

2、主要功能：全部支持支付和验证异步通知

3、支付宝、微信：可生成app签名，可原路退款

4、通过工厂服务可以一次调用出所有支持的支付平台的app参数


### 安装
composer require xing.chen/payment dev-master


### 支付宝、微信配置和生成app支付签名
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

// 生成支付宝app需要的参数
$sign = \xing\payment\drive\PayFactory::getInstance('aliPay')
  ->init($aliConfig)
  ->set('订单号', '金额', '支付标题（商品名）')
  ->customParams('自定义参数值，不需要请删除')
  ->getAppParam();


// 生成微信app需要的参数
$wechatConfig = [
    'title' => '微信支付',
    'appId' => '微信支付appId',
    'mchId' => '商户id',
    'notifyUrl' => '异步通知 url',
    // 请换成你自己的相应的文证书件地址
    'SSL_CERT_PATH' =>  'vendor/xing.chen/payment/sdk/wechatPay/cert/apiclient_cert.pem',
    'SSL_KEY_PATH' => 'vendor/xing.chen/payment/sdk/wechatPay/cert/apiclient_key.pem',
    'key' => '微信32位的API支付密钥',
];
$sign = \xing\payment\drive\PayFactory::getInstance('weChatPay')
  ->init($wechatConfig)
  ->set('订单号', '金额', '支付标题（商品名）')
  ->customParams('自定义参数值，不需要请删除')
  ->getAppParam();
 
// 获取所有参数
$paySet = [
    'aliPay' => $aliConfig,
    'weChatPay' => $wechatConfig
];
$payChannel= \xing\payment\drive\PayFactory::getAppsParam($paySet, '订单号', '金额', '支付标题（商品名）');

```


### 支付宝 异步通知
```php
<?php
# 支付宝异步通知


$drive = Yii::$app->request->post('passback_params'); // 自定义参数
$orderSn = Yii::$app->request->post('out_trade_no'); // 订单号
$payMoney = Yii::$app->request->post('total_amount'); // 支付金额
            
$payName = 'aliPay';
if (!\xing\payment\drive\PayFactory::getInstance($payName)->init($aliConfig)->validate($_POST)) throw new \Exception('验证失败');
```

## 微信异步通知
```php
<?php

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
## 支付宝、微信退款（原路退回）
```php
<?php
$payName = 'aliPay或weChatPay';
\xing\payment\drive\PayFactory::getInstance($payName)->init('上面的微信或支付宝配置')->set('订单号', '退款金额')->refund();
?>
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

# 银联
说明：

手机控件支付：银联需要先获取流水号再返回给前端app，app支付成功后才能成功接收银联的异步通知结果。

## 获取银联流水号
```php
<?php
$orderSn = '订单号';
$payMoney = '支付金额/单位分';
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
try {
    $tn = UnionPay::init($config)->createOrder($orderSn, $payMoney);
} catch (\Exception $e) {
    throw $e;
}
```

### 银联异步通知
```php
<?php

try {
    if (!UnionPay::init($config)->validate($_POST)) throw new \Exception('验签失败');

    $orderSn = $_POST['orderId'];
    // 分转元
    $payMoney = round(Yii::$app->request->post('txnAmt') / 100, 2);
    // 成功后业务代码
    exit('success');
} catch (\Exception $e) {
    throw $e;
}
```

# 首信易支付
### 异步通知
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