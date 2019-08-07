<?php

$WEB_HOME = strstr( dirname(__FILE__), "/redis", true );

require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/email/email-config.php");
require_once(REDIS_PATH . "/sms/sms-config.php");
require_once(REDIS_PATH . "/weixin/weixin-config.php");

/*
	这里是自动运行脚本, 脚本每10s执行一次, 自动监控该运行的脚本

*/
class scriptsAutoApp extends RedisApp
{
	public $scritsArray = array(
		"email" => array(
			"key" => mailKey,
			"file" => "/email/email-action.php",
			"log" => "/log/email.log"
		),
		"sms" => array(
			"key" => smsKey,
			"file" => "/sms/sms-action.php",
			"log" => "/log/sms.log"
		),
		"weixin" => array(
			"key" => weixinKey,
			"file" => "/weixin/weixin-action.php",
			"log" => "/log/weixin.log"
		),

	);

	public function RunApp()
	{
		//订阅发送邮件队列
		$redisDB = new TRedisDB;
		while(true){

			foreach ($this -> scritsArray as $scriptData) {
				# code...
				$dataSize = $redisDB -> lSize($scriptData["key"]);
				
				echo "\n";
				echo $scriptData["key"] . " - " . $dataSize;
				if( $dataSize>0 ) {
					$file = "/usr/local/php5/bin/php -q " . REDIS_PATH . $scriptData["file"] . " >> " . REDIS_PATH . $scriptData["log"] . " &";

					echo "\n" . $file;
					exec( $file );
				}

			}

			sleep(5);
		}
		return;
	}
}

$App = new scriptsAutoApp();
$App -> RunApp();
