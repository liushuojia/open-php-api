<?php

require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeListCacheDeleteApp extends SystemAraeApp
{
	function RunApp()
	{
        $RedisDB = new TRedisDB();
        if( $RedisDB -> exists(redisSystemAreaType) ) {
            $RedisDB -> delete(redisSystemAreaType);
        }
        $this -> showMsg( 200,  "OK");
        return;
	}
}

$App = new CSystemAreaTypeListCacheDeleteApp;
$App -> RunApp();
return;
