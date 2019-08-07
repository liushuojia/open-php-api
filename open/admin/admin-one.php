<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件
	
	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在path_match里面拿数据

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CAdminOneApp extends ApiApp
{
	public $admin_id;
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;
		$this -> admin_id = $routeMatchData["params"]["admin_id"];

		return true;
	}

	function RunApp()
	{
		if( !$this->CheckToken() ){
			//判断权限
			$this -> showMsg( 404, "登录超时" );
			return;
		}
        if( $this -> tokenUser -> admin_role !=1 ){
            //只有管理员能访问
            $this -> showMsg( 404, "您没有相应的权限,请与网站部联系" );
            return;
        }

		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 1, $ErrMsg);
			return;
		}
		
		$array = array(
		    "id" => $this -> admin_id,
            "name" => "刘硕嘉",
            "mobile" => "13725588389",
		);

		$this -> showMsg( 200, $array );
		return;
	}
}

$App = new CAdminOneApp();
$App -> RunApp($path_match);
return;