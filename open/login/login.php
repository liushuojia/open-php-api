<?php

/*
	该文件是在 /route/index.php 根目录包含执行, 所以该文件

		方法 GET
		
		未传参数
			临时授权使用, 允许发送登录短信

		传参数 验证账号
			mobile  手机号码
			verify  账号加密字符串

        获取redis微信绑定数据

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CLoginApp extends ApiApp
{
	public $DB = array(
        "login",
        "admin",
	);

	public $mobile;
	public $code;
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误##1";
		$data = $this -> GetBody();
		$this -> mobile = trim($data["mobile"]);
		if( $this -> mobile=="" ){
			$ErrMsg = "请输入手机号码";
 			return false;
		}

 		if( !CheckMobile($this -> mobile) ){
			$ErrMsg = "请正确输入手机号码";
 			return false;
		}

		$this -> code = trim($data["code"]);
		if( $this -> code =="" ){
			$ErrMsg = "请输入短信验证码";
 			return false;
		}

		return true;
	}

	function RunApp()
	{
		if(!$this -> CheckToken()){
			$this -> showMsg( 401, "登录超时 #token" );
			return;
		}

        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisWeixinLoginPrefix . $this -> token) ){
            $this -> showMsg( 401,"登录超时, 请您刷新页面, 重新扫码绑定账号 #redis" );
            return false;
        }
        $loginObj = $RedisDB -> get(redisWeixinLoginPrefix . $this -> token);

        if(
            $loginObj["open_id"] == ""
            || $loginObj["check_status"] == 0
            || $loginObj["weixin_id"] * 1 <=0
        ) {
            $this -> showMsg( 401,"登录超时, 请您刷新页面, 重新扫码绑定账号 #redis array error" );
            return false;
        }

		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406,$ErrMsg );
			return;
		}

		if(!$this -> TConnectMysql()){
			$this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
			return ;
		}

		if( LandTuDebug != 1 ){
		    // 本地不验证短信
            if(! $this -> CheckSms( $this -> mobile, $this -> code, $ErrMsg )){
                $this -> showMsg( 406, $ErrMsg );
                return;
            }
        }

		if(!$this -> adminDB -> SelectOneData($Admin,array(
			"admin_mobile" => $this -> mobile,
			"admin_status" => 1,
			"is_delete" => 0,
		))) {
			$this -> showMsg( 404, "查无账号, 或账户已停用. " );
			return false;
		}

        if( !$this -> loginDB -> SelectOneData($login, array(
            "open_id" => $loginObj["open_id"],
            "weixin_id" => $loginObj["weixin_id"],
            "is_delete" => 0,   //未删除状态
        ) )){
            $login = new $this -> loginDB -> tableItemClass;
            $login -> login_type = 1;
            $login -> open_id = $loginObj["open_id"];
            $login -> weixin_id = $loginObj["weixin_id"];
            $login -> create_time = time();
        }

        $login -> admin_id = $Admin -> admin_id;
        $login -> wx_headimgurl = $loginObj["headimgurl"];
        $login -> wx_nickname = $loginObj["nickname"];
        $login -> wx_sex = $loginObj["sex"];
        $login -> wx_subscribe = $loginObj["subscribe"];

        if ($login -> login_id==0) {
            $this -> loginDB -> CreateData($login);
        }else{
            $this -> loginDB -> UpdateData($login);
        }

        $editArray = array(
            "headimg" => $loginObj["headimgurl"],
            "admin_mobile_flag" => 1,
        );
        $searchArray = array(
            "admin_id" => $Admin -> admin_id,
        );

		//手机验证开关
		$this -> adminDB -> UpdateDataQuickEditMore($editArray, $searchArray);

		$encryptMD5Key = $this -> BuildTokenAdmin( $Admin );

		$this -> TCloseMysql();
		$this -> showMsg( 200, "OK", $encryptMD5Key);
		return;
	}
}

$App = new CLoginApp;
$App -> RunApp();
return;