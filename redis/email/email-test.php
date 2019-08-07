<?php

$WEB_HOME = strstr( dirname(__FILE__), "/redis/", true );

require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/email/email-config.php");


class emailTestApp extends RedisApp
{
	public function RunApp()
	{
		//订阅发送邮件队列
		$mailContent = array(
			"AddAddress" => array(
				array(
					"Email" => "hiloy@landtu.com",
					"Ename"=>"刘硕嘉",
				),
			),
			"cc" => array(
				array(
					"Email" => "liushuojia@qq.com",
					"Ename"=>"刘硕嘉",
				),
			),

			"bcc" => array(
				/*
				array(
					"Email" => "49650719@qq.com",
					"Ename"=>"刘硕嘉",
				),
				*/
			),
			"AddReplyTo" => array(
				array(
					"Email" => "liushuojia@qq.com",
					"Ename"=>"刘硕嘉",
				),
			),
			//附件文件可以是 http://  https:// 开头文件, 如果是绝对路径, 请确保邮件发送模块与该程序属于同一个服务器
			"AddAttachment" => array(
				array(
					"path"=>"https://www.baidu.com/img/baidu_resultlogo@2.png",
					"name"=>"image.jpg",
				),
				array(
					"path"=>"/home/liushuojia/admin.hiloy.com/op/images/default_handsome.jpg",
				),				
			),
			"Subject" => "测试发送邮件".time(),
			"Body" => "您的预订已经成功, 我们将于1个工作日内与您联系",
			"AltBody" => "您的预订已经成功, 我们将于1个工作日内与您联系",
		);

		$redisDB = new TRedisDB;
		$redisDB -> rPush(mailKey,$mailContent);
		return;
	}
}

$App = new emailTestApp();
$App -> RunApp();
