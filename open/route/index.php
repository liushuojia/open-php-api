<?php
	
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CIndexApp extends ApiApp
{
    // 不检查访问权限, 不检查token
    public $checkRoleFlag = false;
    public $checkTokenFlag = false;

    function RunApp()
	{
        $this -> RouteAction();
		return;
	}
}

$App = new CIndexApp;
$App -> RunApp();
return;