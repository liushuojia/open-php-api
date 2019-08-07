<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件

	获取list数据, 使用的是get方式获取数据


*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CAdminListApp extends ApiApp
{
	public $searchArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;

		if( isset($_GET["search_key"]) && $_GET["search_key"]!="")
			$this -> searchArray["search_key"] = trim($_GET["search_key"]);

		if( isset($_GET["admin_status"]) && is_numeric($_GET["admin_status"]))
			$this -> searchArray["admin_status"] = (int)($_GET["admin_status"]);

		if( isset($_GET["is_delete"]) && is_numeric($_GET["is_delete"]))
			$this -> searchArray["is_delete"] = (int)($_GET["is_delete"]);

		$this -> page_id = (int)($_GET["page_id"]);
		$this -> one_page_num = (int)($_GET["one_page_num"]);

		if( $this -> page_id<1 )
			$this -> page_id = 1;

		if( $this -> one_page_num<1 )
			$this -> one_page_num = 30;

		return true;
	}

	public $DB = array(
		"admin",
	);
	function RunApp()
	{
		if( !$this -> CheckStatus() ){
			//判断权限,这个页面的token必须是系统用户
			$this -> showMsg( 404, "登录超时" );
			return;
		}
		if( $this -> tokenUser -> admin_role !=1 ){
			//只有管理员能访问
			$this -> showMsg( 404, "您没有相应的权限,请与网站部联系" );
			return;			
		}

		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg );
			return;
		}

		if(!$this -> TConnectMysql()){
			$this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
			return ;
		}

		$searchArray = $this -> searchArray;

		$StartPos = ($this -> page_id - 1) * $this -> one_page_num;
		$Num = $this -> one_page_num;

		$this -> adminDB -> QueryData($AdminItemList, $StartPos, $Num, $searchArray );
		$this -> adminDB -> GetNumData($totalNum, $searchArray);

		$this -> TCloseMysql();

		$this -> showMsg( 200,  "OK", array(
			"content" => $AdminItemList,
			"page" => array(
				"page_id" => $this -> page_id,
				"one_page_num" => $this -> one_page_num,
				"total_num" => $totalNum,
				"total_page" => ceil($totalNum/$this -> one_page_num),
			),
		));

		return;
	}
}

$App = new CAdminListApp;
$App -> RunApp();
return;
