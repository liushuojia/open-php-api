<?php

/*
	获取token对应的用户

*/

require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CAdminOneApp extends UserApp
{
    public $checkRoleFlag = false;   //检查用户是否有权限访问页面
    public $checkTokenFlag = false;   //检查页面访问的token是否正确
    //防止死循环重构下
    function CheckToken() {return true;}

    public $DB = array(
        "admin",
    );
	function RunApp()
	{
        $HTTP_TOKEN = trim($_SERVER["HTTP_TOKEN"]);
        $HTTP_USER_AGENT = trim($_SERVER["HTTP_USER_AGENT"]);

        if( $HTTP_TOKEN=="" ){
            $this -> showMsg( 500, "参数传递错误 #token is null" );
            return false;
        }

        $this -> token = $HTTP_TOKEN;

        $obj = new convert(32);

        $tmp = explode("-", $HTTP_TOKEN);
        if( count($tmp)<3 ){
            $this -> showMsg( 500, "参数传递错误 #token is wrong" );
            return false;
        }

        if(!$this -> TConnectMysql()){
            $this -> showMsg( 500, "数据库连接超时 #mysql" );
            return false;
        }

        $admin_id = $obj -> stringToId($tmp[0]);
        if(!$this -> adminDB -> SelectOneData($this -> tokenUser,array(
            "admin_id" => $admin_id,
            "admin_status" => 1,
            "is_delete" => 0,
        ))) {
            $this -> showMsg( 404, "查无该数据" );
            $this -> TCloseMysql();
            return false;
        }

        $data = array(
            "userAgent" => $HTTP_USER_AGENT,
            "time" => $obj -> stringToId($tmp[1]),
        );
        $UID = $this -> BuildTokenAdmin( $this -> tokenUser, $data );

        $this -> tokenFlag = ($UID===$HTTP_TOKEN);

        if( $this -> tokenFlag ){
            $RedisDB = new TRedisDB();
            $redisAdminKey = redisAdminPrefix . $admin_id;
            $RedisDB -> set($redisAdminKey,$this -> tokenUser,redis_ext_time);
        }

        $this -> showMsg( 200, "OK", $this -> tokenUser );

        return $this -> tokenFlag;
	}
}

$App = new CAdminOneApp();
$App -> RunApp();
return;
