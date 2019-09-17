<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件

	获取list数据, 使用的是get方式获取数据


*/
if( !defined("DOCUMENT_ROOT") ){
	return;
}
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeListApp extends SystemAraeApp
{
	public $searchArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;

		if( isset($_GET["search_key"]) && $_GET["search_key"]!="")
			$this -> searchArray["search_key"] = trim($_GET["search_key"]);

		if( isset($_GET["status"]) && is_numeric($_GET["status"]))
			$this -> searchArray["status"] = (int)($_GET["status"]);

		if( isset($_GET["is_delete"]) && is_numeric($_GET["is_delete"]))
			$this -> searchArray["is_delete"] = (int)($_GET["is_delete"]);

		if( is_array($_GET["order_by"]) && count($_GET["order_by"])>0){
            $this -> searchArray["order_by"] = ($_GET["order_by"]);
        }

		$this -> page_id = (int)($_GET["page_id"]);
		$this -> one_page_num = (int)($_GET["one_page_num"]);

		if( $this -> page_id<1 )
			$this -> page_id = 1;

		if( $this -> one_page_num<1 )
			$this -> one_page_num = 30;

		return true;
	}

	public $DB = array(
		"SystemAreaType",
	);
	function RunApp()
	{
		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg );
			return;
		}

		if(!$this -> TConnectMysql()){
			$this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
			return ;
		}

		$searchArray = $this -> searchArray;
        $searchArray['order_by'] = array('weight' => 'desc');

		$StartPos = ($this -> page_id - 1) * $this -> one_page_num;
		$Num = $this -> one_page_num;

		$this -> SystemAreaTypeDB -> QueryData($SystemAreaTypeList, $StartPos, $Num, $searchArray );
		$this -> SystemAreaTypeDB -> GetNumData($totalNum, $searchArray);

		$this -> TCloseMysql();

		$this -> showMsg( 200,  "OK", array(
			"content" => $SystemAreaTypeList,
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

$App = new CSystemAreaTypeListApp;
$App -> RunApp();
return;
