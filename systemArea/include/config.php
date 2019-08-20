<?php

    define( "HOME",preg_replace("#/(\w+)/include#U", "", dirname(__FILE__)) );

    # 内部调试开关  1：开启  0：关闭
	if( strstr( HOME, '/home/')!==false || strstr( HOME, '/Users/')!==false )
	{
		define("DebugFlag", 1);
	}else{
		define("DebugFlag", 0);
	}

	// ----------------------------------------------------------------------------------------------------------
    $DOCUMENT_ROOT = trim($_SERVER["DOCUMENT_ROOT"]);
    if( substr($DOCUMENT_ROOT, -1) == "/" ) {
        $DOCUMENT_ROOT = substr($DOCUMENT_ROOT, 0, strlen($DOCUMENT_ROOT)-1 );
    }
    define("DOCUMENT_ROOT", $DOCUMENT_ROOT);

	#配置文件
	require_once(HOME . "/libs/include/webConfig.php");
