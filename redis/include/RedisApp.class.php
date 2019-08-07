<?php

require_once(LIB_PATH . "/include/Email.class.php");
require_once(LIB_PATH . "/include/redis.class.php");
require_once(LIB_PATH . "/include/curl.class.php");
require_once(LIB_PATH . "/include/TWeixinAction.class.php");

//所有的OP系统程序，都是这个类的派生类
class RedisApp extends ShowApp
{
	function WebInitReconstruction(){
		return;
	}

	#编译目录， 基类自己根据实际情况设置 默认是WWW的
	public $smarty_path = REDIS_PATH;

}


