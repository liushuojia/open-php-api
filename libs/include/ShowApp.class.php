<?php

//所有的显示数据的基类程序，都是这个类的派生类
/*
	www目录有一个 WWWApp.class.php	继承该类
		目录底下的php程序继承 WWWApp

*/
require_once(SMARTY_LIB.'/Smarty.class.php');
require_once(LIB_PATH."/include/runTime.class.php");
require_once(LIB_PATH."/include/PublicFunction.php");

class ShowApp extends CRunTime
{
	//数据库连接 等基础弄好了这里需要去掉

	#smarty
	public $smarty;
	function showTimeSpent()
	{
		$this -> stop();
		echo "cost " . $this -> spent();
		return;
	}

	function WebInitReconstruction()
	{
		#在不同域名中重构该函数，初始化对象的时候需要初始化数据
		return;
	}

	#编译目录， 基类自己根据实际情况设置 默认是WWW的
	public $smarty_path = WWW_PATH;
	public $mobile_flag = false;
	public $this_host = "";
	public $this_url_host = "";
	public $this_url = "";

	function __construct()			#每个类初始化的时候执行的咚咚
	{
		$this -> start();

		$this -> this_host = $_SERVER["HTTP_HOST"];
		$this -> this_url_host = $_SERVER["USER"] . "://" . $_SERVER["HTTP_HOST"] .
			( $_SERVER["SERVER_PORT"] =="80" ? "" : (":" . $_SERVER["SERVER_PORT"]) );

		$this -> this_url = $this -> this_url_host . $_SERVER["REQUEST_URI"];

		$this -> WebInitReconstruction();
		return;
	}

	//header("HTTP/1.0 404 Not Found");
	public function errorAjax($msg,$array = array())
	{
		$this -> AjaxData($msg, 1, $array);
		return false;
	}
	public function successAjax($msg,$array = array())
	{
		$this -> AjaxData($msg, 0, $array);
		return false;
	}

	public function AjaxData( $msg, $errorFlag=1, $obj = array() )
	{
		$this -> stop();
		$tmp = $this -> spent();

		if($tmp<1000)
			$excute_time = $tmp . "ms";
		else{
			$excute_time = formatSecond(ceil($tmp/1000));
		}

		$array = array(
			"flag" => $errorFlag,
			"msg" => $msg,
			"excute_time" => $excute_time,
			"data" => $obj,
		);

		echo json_encode($array, true);
		return;
	}

	public function CheckInput(&$CheckMsg)	//检查CGI输入的合法性，应用需要重载此函数
	{
		return TRUE;
	}


	//数据库连接
	public $DB = array(
		//
		//  "admin"				#系统自动增加 \\MAINDBData\\admin
		//  "TTableFieldExtend" => "\\THILOYSQLDB\\TTableFieldExtend",
		//
		//		$key => table name
		//		$val => database\table name
	);
	public $DBAction = array(); //auto add db msg
	public $DBFILEARRAY;
	function TConnectMysql()
	{
		if(!is_array($this -> DBFILEARRAY))
		{
			Global $DBFILEARRAY;
			$this -> DBFILEARRAY = $DBFILEARRAY;
		}
		foreach( $this -> DB as $key => $val )
		{
			if( is_numeric($key) )
			{
				$key = $val;
				$val = "\\" . MAINDBData . "\\". $key;
			}
			if( !key_exists($val,$this -> DBFILEARRAY))
			{
				echo "\n";
				echo "table " . $key . " is wrong ";
				echo "\n";
				die();
			}

			if( !property_exists($this, $key. "DB" )  ){
				$this -> DBAction[$key] = $this -> DBFILEARRAY[$val];
				$goDB = $this -> DBFILEARRAY[ $val ]["classNameDB"];
				$this -> {$key. "DB"} = new $goDB();
				if(!$this -> {$key. "DB"} -> ConnectMysql())
					return false;	
			}
		}
		return true;
	}

	function TCloseMysql()
	{
		foreach( $this -> $DB as $key => $val )
		{
			$this -> {$key."DB"} -> CloseMysql();
			unset($this -> {$key."DB"});
		}
		return;
	}

	//生成唯一的MD5加密字符串
	function buildVerify($thisVerify, $key)
	{
		$thisVerify["key"] = $key;
		$string = encryptMD5Key( $thisVerify );
		unset($thisVerify["key"]);
		return $string;
	}

}
