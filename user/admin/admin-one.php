<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件
	
	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在path_match里面拿数据

*/
require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CAdminOneApp extends UserApp
{
	public $admin_id;
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;
		$this -> admin_id = (int) ($routeMatchData["params"]["admin_id"]);

		if( $this -> admin_id<=0 )
		    return false;

		return true;
	}
    public $DB = array(
        "admin",
        "login",
        "weixin_account",
    );

	function RunApp()
	{
		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg);
			return;
		}

        if(!$this -> TConnectMysql()){
            $this -> showMsg( 500, "数据库连接超时 #mysql" );
            return false;
        }

        if( !$this -> adminDB -> SelectOneData($admin, array(
            "admin_id" => $this -> admin_id,
        )) ) {
            $this -> showMsg( 404, "查无该账户信息" );
            $this -> TCloseMysql();
            return;
        }

        $weixinArray = array();
        $this -> weixin_accountDB -> QueryData($weixinAccountList,0,0);
        foreach( $weixinAccountList as $key => $weixinAccount ){
            $weixinArray[ $weixinAccount -> weixin_id ] = $weixinAccount -> weixin_title;
        }

        $this -> loginDB -> QueryData($loginList, 0, 0, array(
            "admin_id" => $this -> admin_id,
        ) );

        foreach ( $loginList as $key => &$loginData ) {
            $loginData -> weixin_title = $weixinArray[ $loginData -> weixin_id ];
        }

        $this -> showMsg( 200, "OK", array(
            "admin" => $admin,
            "loginArray" => $loginList,
        ) );
		return;
	}
}

$App = new CAdminOneApp();
$App -> RunApp();
return;
