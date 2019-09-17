<?php

if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeListCacheApp extends SystemAraeApp
{
	public $DB = array(
		"SystemAreaType",
	);
	function RunApp()
	{
        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisSystemAreaType) ) {

            if(!$this -> TConnectMysql()){
                $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
                return ;
            }

            $this -> SystemAreaTypeDB -> QueryData($SystemAreaTypeList, 0, 0, array(
                'status' => '1',
                'is_delete' => '0',
                'order_by' => array('weight' => 'desc'),
            ) );

            $sqlString = "update SystemArea set area_type_name = (SELECT type_name FROM `SystemAreaType` where SystemArea.area_type = SystemAreaType.type_id)";
            $this -> SystemAreaTypeDB -> ExcuteSql($sqlString);

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
