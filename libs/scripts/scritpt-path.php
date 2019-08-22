<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/10/20
 * Time: 01:37
 */

if(class_exists('CScriptsDB')){
	//该页面已经加载了一次了
	return;
}

$WEB_HOME = preg_replace("#/libs/scripts/?#U", "", dirname(__FILE__));

if($argc > 1)
	$WEB_HOME = $argv[1];

if( substr($WEB_HOME, strlen($WEB_HOME)-1,1  )!="/" )
	$WEB_HOME .= "/";


ini_set("memory_limit","1024M");
ini_set("max_execution_time", "60");


require_once($WEB_HOME."/user/include/config.php");


class CScriptsDB extends CRunTime
{
	public $costTime;
	function getTimeSpent()
	{
		$this -> stop();
		return $this -> spent();
	}

	function __construct()			#每个类初始化的时候执行的咚咚
	{
		#统计页面时间
		$this -> start();
		return;
	}

	function errorMsg( $msg )
	{
		echo "\n";
		echo $msg;
		echo "\n";
		return;
	}
}

