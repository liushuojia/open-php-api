<?php

/*
	该文件是在 /route/index.php 根目录包含执行, 所以该文件

		方法 PUT
		传递参数 json 
		可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CAdminListUpdateApp extends UserApp
{
	public $admin_id_array = array();
	public $editArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;

		$postArray = $this -> GetBody();

		if( !is_array($postArray["searchKey"]) || count($postArray["searchKey"])==0 )
			return false;

		//查询的key
		if( !isset($postArray["searchKey"]["admin_id_in"]) || !is_array($postArray["searchKey"]["admin_id_in"]) || count($postArray["searchKey"]["admin_id_in"])==0 ){
			return false;
		}

		foreach ($postArray["searchKey"]["admin_id_in"] as $admin_id) {
			if( is_numeric($admin_id) && $admin_id*1>0 )
				$this -> admin_id_array[] = $admin_id;
		}

		if( count($this -> admin_id_array)==0 )
			return false;

		if( !isset($postArray["editObj"]) || !is_array($postArray["editObj"]) || count($postArray["editObj"])==0 )
			return false;


		//更新的内容
		if( $postArray["editObj"]["is_delete"]=="1" ){
            $this -> editArray["is_delete"] = 1;
            $this -> editArray["stop_date"] = time();
		}else{
			$this -> editArray["is_delete"] = 0;
			if( $postArray["editObj"]["admin_status"]=="1" ){
				$this -> editArray["admin_status"] = 1;
			}elseif( $postArray["editObj"]["admin_status"]=="0" ){
				$this -> editArray["admin_status"] = 0;
                $this -> editArray["stop_date"] = time();
            }else{
				return false;
			}
		}

		return true;
	}

	public $DB = array(
		"admin",
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

        $this -> editArray["update_time"] = time();
		$this -> adminDB -> UpdateDataQuickEditMore($this -> editArray, array(
			"admin_id_array" => $this -> admin_id_array,
		));

        foreach ($this -> admin_id_array as $admin_id) {
            $this -> clearAdminRedis($admin_id);
        }

        $this -> showMsg( 200, "更新数据成功");
		return;
	}
}

$App = new CAdminListUpdateApp;
$App -> RunApp();
return;
