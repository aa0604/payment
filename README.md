# payment
支付插件 支持支付宝和微信
## 插件介绍

本插件使用简单方便，扩展性强，它拥有如下特点：

1、各支付平台的类都继承使用interface接口规范，保证整个支付模块工作统一规范

2、使用工厂服务调度，可以方便的切换调用各个支付平台驱动

3、通过工厂服务可以一次调用出所有支持的支付平台的app参数

4、使用命名空间，可以方便的集成到TP3.2以上，YII2等主流框架中使用

### 使用示例
```
// 生成全部app需要的参数
\xing\payment\drive\Factory::getAppsParam($sets, $orderSn, $money, $title);
// 生成支付宝app需要的参数
\xing\payment\drive\Factory::getInstance('aliPay')->init($set)->set($orderSn, $money, $title)->getSign();
```

