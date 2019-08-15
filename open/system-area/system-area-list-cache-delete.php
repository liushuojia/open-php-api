<?php

require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CSystemAreaTypeListCacheDeleteApp extends ApiApp
{
	function RunApp()
	{
        $RedisDB = new TRedisDB();
        if( $RedisDB -> exists(redisSystemArea) ) {
            $RedisDB -> delete(redisSystemArea);
        }
        $this -> showMsg( 200,  "OK");
        return;
	}
}

$App = new CSystemAreaTypeListCacheDeleteApp;
$App -> RunApp();
return;
