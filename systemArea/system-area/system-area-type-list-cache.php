<?php

require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeListCacheApp extends SystemAraeApp
{
	public $DB = array(
		"system_area_type",
	);
	function RunApp()
	{
        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisSystemAreaType) ) {

            if(!$this -> TConnectMysql()){
                $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
                return ;
            }

            $this -> system_area_typeDB -> QueryData($SystemAreaTypeList, 0, 0, array(
                'status' => '1',
                'is_delete' => '0',
                'order_by' => array('weight' => 'desc'),
            ) );
            $this -> TCloseMysql();

            $RedisDB -> set(redisSystemAreaType,$SystemAreaTypeList,redis_ext_time);
            
        }

        $retrunData = $RedisDB -> get(redisSystemAreaType);
        $this -> showMsg( 200,  "OK", array(
            "content" => $retrunData,
        ));
		return;
	}
}

$App = new CSystemAreaTypeListCacheApp;
$App -> RunApp();
return;
