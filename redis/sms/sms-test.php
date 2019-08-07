<?php

$WEB_HOME = strstr( dirname(__FILE__), "/redis/", true );

require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/sms/sms-config.php");


class emailTestApp extends RedisApp
{
	public function RunApp()
	{
		//订阅发送邮件队列

		$redisDB = new TRedisDB;

		$msgArray = array(
			"mobile" => "13725588389",	//微信 原始id
			"content" => "您的验证码：221122。",						//类型 text 纯文本 tempalte 模板消息
		);
		$redisDB -> rPush(smsKey,$msgArray);

		return;
	}
}

$App = new emailTestApp();
$App -> RunApp();
