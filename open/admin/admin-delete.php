<?php
	
/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 DELETE
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CAdminDeleteApp extends ApiApp
{
    public $admin_id;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $path_match;
        $this -> admin_id = $path_match[1];

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
        $this -> showMsg( 200, "删除成功");
		return;
	}
}

$App = new CAdminDeleteApp;
$App -> RunApp();
return;
