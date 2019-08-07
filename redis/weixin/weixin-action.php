<?php

$WEB_HOME = strstr( dirname(__FILE__), "/redis/", true );
require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/weixin/weixin-config.php");

/*
	可能存在跨服务器的 这里的附件应该如何处理呢

*/
class weixinActionApp extends RedisApp
{
	public function RunApp()
	{

		//订阅发送邮件队列
		$redisDB = new TRedisDB;
		$WeixinDB = new TNewWeixinDB;

		while( $redisDB -> lSize(weixinKey) > 0 ){
			$dataContent = $redisDB -> lPop(weixinKey);

			if( $dataContent !== false && is_array($dataContent) && count($dataContent)>0 ){
				if( $dataContent["weixin_user_id"]=="" )
					continue;

				if( $dataContent["type"]=="" )
					continue;

				if(!$WeixinDB -> setWeixinAccountAction( $dataContent["weixin_user_id"] ))
					return;

				if( is_array($dataContent["content"]) && count($dataContent["content"])>0 ){
					$postString = json_encode($dataContent["content"]);
					switch($dataContent["type"]){
						case "text":
							$WeixinDB -> sentTextMsg($dataContent["content"]["text"]["content"],$dataContent["content"]["touser"]);
							break;
						case "tempalte":
							$WeixinDB -> tempalteMsg($postString);
							break;
					}
				}
			}
			
		}
		return;
	}
}

$App = new weixinActionApp();
$App -> RunApp();
