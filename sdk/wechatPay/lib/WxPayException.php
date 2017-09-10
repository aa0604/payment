<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */

namespace xing\payment\sdk\wechatPay\lib;

class WxPayException extends \Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
