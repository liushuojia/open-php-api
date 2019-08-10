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

class CGetTokenApp extends ApiApp
{
    // 不检查访问权限, 不检查token
    public $checkRoleFlag = false;
    public $checkTokenFlag = false;

    public $HTTP_TOKEN;
	public $HTTP_USER_AGENT;
	public $mobile;
	public $verify;
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		$this -> HTTP_TOKEN = trim($_SERVER["HTTP_TOKEN"]);
		$this -> HTTP_USER_AGENT = trim($_SERVER["HTTP_USER_AGENT"]);

		$this -> mobile = trim($_GET["mobile"]);
		$this -> verify = trim($_GET["verify"]);
		if($this -> mobile!=""){
			if( !CheckMobile($this -> mobile) ){
				$ErrMsg = "手机号码输入错误";
				return false;
			}
			if( $this -> verify=="" ){
                $ErrMsg = "校验密钥为空";
                return false;
			}
		}

		return true;
	}

	public $DB = array(
		"admin",
	);

	function RunApp()
	{
		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg );
			return;
		}
		
		if($this -> mobile==""){
			$data = array(
				"userAgent" => $this -> HTTP_USER_AGENT,
				"verify" => md5($this -> HTTP_USER_AGENT),
				"id" => 0,
				"time" => time(),
			);
			$encryptMD5Key = $this -> BuildToken( $data );
			$this -> showMsg( 200, "OK", $encryptMD5Key);
			return;
		}else{
			if(!$this -> TConnectMysql()){
				$this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
				return ;
			}

			if(!$this -> adminDB -> SelectOneData($Admin,array(
				"admin_mobile" => $this -> mobile,
				"admin_status" => 1,
				"is_delete" => 0,
			))) {
				$this -> showMsg( 404, "查无账号, 或账户已停用. " );
				return false;
			}

			if( $Admin -> admin_verify !== $this -> verify){
				$this -> showMsg( 406, "参数传递错误" );
				return;
			}
			$encryptMD5Key = $this -> BuildTokenAdmin( $Admin );

			$this -> TCloseMysql();
			$this -> showMsg( 200, "OK", $encryptMD5Key);
			return;
		}
		return;
	}
}

$App = new CGetTokenApp;
$App -> RunApp();
return;
