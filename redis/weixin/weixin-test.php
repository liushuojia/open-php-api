<?php

$WEB_HOME = strstr( dirname(__FILE__), "/redis/", true );

require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/weixin/weixin-config.php");


class emailTestApp extends RedisApp
{
	public function RunApp()
	{
		//订阅发送邮件队列

		$redisDB = new TRedisDB;

		$msgArray = array(
			"weixin_user_id" => "gh_67ec21283b5f",	//微信 原始id
			"type" => "text",						//类型 text 纯文本 tempalte 模板消息
			"content" => array(						//消息主体
				"touser" => "ovUDAjiHz81i5Jul69f4g59mZMlo",		//发送给谁
				"msgtype" => "text",							//消息类型
				"text" => array(								//内容
					"content" => "你好啊, 测试" . time(),
				),
			),
		);
		$redisDB -> rPush(weixinKey,$msgArray);

		return;
	}
}

$App = new emailTestApp();
$App -> RunApp();
