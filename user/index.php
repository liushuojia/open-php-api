<?php

require_once("include/config.php");
require_once( LIB_PATH . "/include/route.class.php");

class CIndexApp extends routeApp
{
    function RunApp()
	{
	    $this -> routeFilePath = OPEN_PATH . '/include/route.php';
        $this -> RouteAction();
		return;
	}
}

$App = new CIndexApp;
$App -> RunApp();
return;