<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件
	
	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在path_match里面拿数据

*/
if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CRouterOneApp extends UserApp
{
	public $router_id;
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;
		$this -> router_id = (int) ($routeMatchData["params"]["router_id"]);

		if( $this -> router_id<=0 )
		    return false;

		return true;
	}
    public $DB = array(
        "Router",
        "RouterMethod",
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

        if( !$this -> RouterDB -> SelectOneData( $router, array(
            "router_id" => $this -> router_id,
        )) ) {
            $this -> showMsg( 404, "查无该账户信息" );
            $this -> TCloseMysql();
            return;
        }

        $searchArray = array(
            "router_id" => $router -> router_id,
        );
        $this -> RouterMethodDB -> QueryData($RouterMethodItemList, 0, 0, $searchArray );



        $searchArray = array(
            "folder" => $router -> folder,
            "router_id_not" => $router -> router_id,
            "is_delete" => 0,
        );
        $this -> RouterDB -> QueryData($RouterItemList, 0, 0, $searchArray );






        $this -> showMsg( 200, "OK", array(
            "router" => $router,
            "RouterItemList" => $RouterItemList,
            "RouterMethodItemList" => $RouterMethodItemList,
        ) );

		return;
	}
}

$App = new CRouterOneApp();
$App -> RunApp();
return;
