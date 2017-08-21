# payment
支付插件 支持支付宝和微信
## 插件介绍
1、本插件设计了接口和工厂模式
2、通过工厂服务可以方便的切换调用各个支付平台
3、通过工厂服务可以一次调用出所有支持的支付平台的app参数

### 使用示例
```
// 生成全部app需要的参数
Factory::getAppsParam($sets, $orderSn, $money, $title);
// 生成支付宝app需要的参数
Factory::getInstance('aliPay')->init($set)->set($orderSn, $money, $title)->getSign();
```

