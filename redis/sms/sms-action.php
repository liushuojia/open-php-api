<?php

$WEB_HOME = strstr( dirname(__FILE__), "/redis/", true );

require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/sms/sms-config.php");
require_once(LIB_PATH . "/include/smsSendAction.class.php");

/*
	可能存在跨服务器的 这里的附件应该如何处理呢

*/
class smsActionApp extends RedisApp
{
	public function RunApp()
	{

		//订阅发送邮件队列
		$redisDB = new TRedisDB;

		while( $redisDB -> lSize(smsKey) > 0 ){
			$dataContent = $redisDB -> lPop(smsKey);
			if( $dataContent !== false && is_array($dataContent) && count($dataContent)>0 ){
				if( $dataContent["mobile"]=="" || $dataContent["content"]=="" )
					continue;
				
				if( !CheckMobile($dataContent["mobile"]) )
					continue;

				$SendSmsDB = new CSendSmsDB();
				$SendSmsDB -> sendmsg($dataContent["content"],$dataContent["mobile"]);

				echo $SendSmsDB -> returnMsg;
			}

		}

		return;
	}
}

$App = new smsActionApp();
$App -> RunApp();
