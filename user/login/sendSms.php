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
if( !defined("DOCUMENT_ROOT") ){
	return;
}
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CSendSmsApp extends UserApp
{
    public $checkRoleFlag = false;

    public $DB = array(
		"Admin",
	);
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		$this -> mobile = trim($_GET["mobile"]);
		if( $this -> mobile =="" )
 			return false;

 		if( !CheckMobile($this -> mobile) ){
 			$ErrMsg = "手机号码输入错误";
 			return false;
		}

		return true;
	}

	function RunApp()
	{

		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406,$ErrMsg );
			return;
		}

		if(!$this -> TConnectMysql()){
			$this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
			return ;
		}

		if(!$this -> AdminDB -> SelectOneData($Admin,array(
			"admin_mobile" => $this -> mobile,
			"admin_status" => 1,
			"is_delete" => 0,
		))) {
			$this -> showMsg( 404, "查无账号, 或账户已停用. " );
			return false;
		}

		//开始发送短信
		$smscode = random(6, 1);
		if( !$this -> SendSms( $this -> mobile, $smscode, $ErrMsg ) ){
			$this -> showMsg( 406, $ErrMsg );
			return;			
		}

		$this -> TCloseMysql();
		$this -> showMsg( 200, "OK". (DebugFlag==1?(" - ".$smscode):""  ) );
		return;
	}
}

$App = new CSendSmsApp;
$App -> RunApp();
return;
