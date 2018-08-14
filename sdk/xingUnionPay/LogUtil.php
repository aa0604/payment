<?php
/**
 * Created by PhpStorm.
 * User: xing.chen
 * Date: 2018/7/9
 * Time: 11:28
 */

namespace xing\payment\sdk\xingUnionPay;

use xing\payment\sdk\xingUnionPay\SDKConfig;
use xing\payment\sdk\xingUnionPay\PhpLog;

class LogUtil
{
    private static $_logger = null;
    public static function getLogger()
    {
        if (LogUtil::$_logger == null ) {
            $l = SDKConfig::getSDKConfig()->logLevel;
            if("INFO" == strtoupper($l))
                $level = PhpLog::INFO;
            else if("DEBUG" == strtoupper($l))
                $level = PhpLog::DEBUG;
            else if("ERROR" == strtoupper($l))
                $level = PhpLog::ERROR;
            else if("WARN" == strtoupper($l))
                $level = PhpLog::WARN;
            else if("FATAL" == strtoupper($l))
                $level = PhpLog::FATAL;
            else
                $level = PhpLog::OFF;
            LogUtil::$_logger = new PhpLog ( SDKConfig::getSDKConfig()->logFilePath, "PRC", $level );
        }
        return self::$_logger;
    }
}