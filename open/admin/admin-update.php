<?php

/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 PUT
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CAdminUpdateApp extends ApiApp
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
        if(!$this -> CheckInput($ErrMsg)){
            $this -> showMsg( 406, $ErrMsg);
            return;
        }    

        $this -> showMsg( 200, "更新");
		return;
	}
}

$App = new CAdminUpdateApp;
$App -> RunApp();
return;
