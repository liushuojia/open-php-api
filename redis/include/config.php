<?php
	//define("DebugFlag", 0);	

	# 内部调试开关  1：开启  0：关闭
    define( "HOME",preg_replace("#/(\w+)/include#U", "", dirname(__FILE__)) );

	// define("DebugFlag", 0);
	if( strstr( HOME, '/home/')!==false )
	{
		define("DebugFlag", 1);
	}else{
		define("DebugFlag", 0);
	}
	#配置文件
	require_once(HOME . "/libs/include/webConfig.php");

