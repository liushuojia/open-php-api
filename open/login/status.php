<?php

/*
	该文件是在 /route/index.php 根目录包含执行, 所以该文件

		方法 GET
		
		未传参数
			临时授权使用, 允许发送登录短信

		传参数 验证账号
			mobile  手机号码
			verify  账号加密字符串

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CCheckTokenApp extends ApiApp
{
    public $checkRoleFlag = false;

    function RunApp()
	{
		$returnArray = array(
            "login_flag" => $this -> tokenFlag
        );
		if ( $this -> tokenFlag ){
		    $tmp = array(
                "headimg" => $this -> tokenUser -> headimg,
                "realname" => $this -> tokenUser -> realname,
                "admin_role" => $this -> tokenUser -> admin_role,
            );
            $returnArray["admin"] = $tmp;
        }


		$this -> showMsg( 200,"OK", $returnArray );
		return;
	}
}

$App = new CCheckTokenApp;
$App -> RunApp();
return;