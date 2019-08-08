<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件

	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在$routeMatchData里面拿数据

*/

require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CLoginStatusApp extends ApiApp
{
    // 不检查访问权限, 不检查token
    public $checkRoleFlag = false;
    public $checkTokenFlag = false;

    public $weixin_id;
    public $uid;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> weixin_id = (int)($routeMatchData["params"]["weixin_id"]);
        if( $this -> weixin_id<=0 )
            return false;

        if( !$this -> CheckToken() ){
            return false;
        }

        $this -> uid = $this -> token;

        return true;
    }

    public $DB = array(
        "weixin_account",
    );

    function RunApp()
    {

        if ( !$this -> CheckInput($ErrMsg) ) {
            $this -> showMsg( 401, $ErrMsg);
            return;
        }

        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisWeixinLoginPrefix . $this -> uid) ){
            $loginObj =  $array = array(
                "uid" => $this -> uid,
                "open_id" => "",
                "create_time" => time(),
                "check_msg" => "请使用微信扫一扫登录",
                "check_status" => 0,
                "userAgent" => trim($_SERVER["HTTP_USER_AGENT"]),
            );
            $loginObj = $RedisDB -> set(redisWeixinLoginPrefix . $this -> uid, $loginObj, 1*60*60);
        }else{
            $loginObj = $RedisDB -> get(redisWeixinLoginPrefix . $this -> uid);
        }

        $this -> showMsg( 200,$loginObj["msg"], array("wx_status" => $loginObj) );
        return;
    }

}

$App = new CLoginStatusApp();
$App -> RunApp();
return;
