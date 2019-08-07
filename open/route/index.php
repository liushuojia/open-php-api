<?php
	
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CIndexApp extends ApiApp
{
	function RunApp()
	{
        $this -> RouteAction();
		return;
	}
}

$App = new CIndexApp;
$App -> RunApp();
return;