<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件

	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在$routeMatchData里面拿数据

*/
if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CGetOpenidApp extends UserApp
{
    // 不检查访问权限, 不检查token
    public $checkRoleFlag = false;
    public $checkTokenFlag = false;

    public $getData = array();
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";

        $tmp = $_GET;
        unset($tmp["verifyMD5"]);
        $tmp["key"] = API_KEYWORD;
        $tmp["verifyMD5"] = encryptMD5Key( $tmp );
        unset($tmp["key"]);

        if( $tmp["verifyMD5"] !== $_GET["verifyMD5"] )
            return false;

        $this -> getData = $tmp;

        if(
            $this -> getData["uid"] == ""
            || $this -> getData["open_id"] == ""
            || $this -> getData["weixin_id"]*1 <=0
        ){
            return false;
        }

        return true;
    }

    public $DB = array(
        "WeixinAccount",
        "Login",
        "Admin",
    );

    function RunApp()
    {
        if(!$this -> CheckInput($ErrMsg)){
            $this -> mobileMsg( $ErrMsg);
            return;
        }

        if(!$this -> TConnectMysql()){
            $this -> mobileMsg( "连接数据库失败,请与网站部联系 #mysql" );
            return ;
        }

        if(!$this -> WeixinAccountDB -> SelectOneData($weixin_account, array(
            "weixin_id" => $this -> getData["weixin_id"]
        ) )){
            $this -> mobileMsg( "公众号已经注销,请与网站部联系 #no found" );
            $this -> TCloseMysql();
            return;
        }

        if( !$this -> LoginDB -> SelectOneData($login, array(
            "open_id" => $this -> getData["open_id"],
            "weixin_id" => $this -> getData["weixin_id"],
            "is_delete" => 0,   //未删除状态
        ) )){
            $login = new $this -> LoginDB -> tableItemClass;
            $login -> login_type = 1;
            $login -> open_id = $this -> getData["open_id"];
            $login -> weixin_id = $this -> getData["weixin_id"];
            $login -> create_time = time();
        }

        //获取openid成功,设置状态
        $this -> getData["check_status"] = 1;


        //
        /*
         * state
         * 1 后台用户登录
         * 0 前台用户登录
        */
        switch ( $this -> getData["state"] ){
            case "1":
                $this -> getData["admin_id"] = $login -> admin_id;

                //后台账号登录
                if( $login -> admin_id > 0 ){
                    //已经绑定用户, 获取账号情况
                    $this -> getData["check_msg"] = "登录成功";

                    if(!$this -> AdminDB -> SelectOneData($Admin,array(
                        "admin_id" => $login -> admin_id,
                        "admin_status" => 1,
                        "is_delete" => 0,
                    ))) {
                        $this -> mobileMsg(  "查无账号, 或账户已停用. " );
                        return false;
                    }

                    $editArray = array(
                        "headimg" => $this -> getData["headimgurl"],
                    );
                    $searchArray = array(
                        "admin_id" => $Admin -> admin_id,
                    );
                    $this -> AdminDB -> UpdateDataQuickEditMore($editArray, $searchArray);

                    $encryptMD5Key = $this -> BuildTokenAdmin( $Admin, array(
                        "userAgent" => $this -> getData["userAgent"],
                    ) );

                    $this -> getData["success_uid"] = $encryptMD5Key;


                }else{
                    //未绑定后台账号需要重新绑定
                    $this -> getData["check_msg"] = "微信未绑定账号,请您绑定账号";
                }

                if ($login -> login_id==0) {
                    $this -> LoginDB -> CreateData($login);
                }

                $RedisDB = new TRedisDB();
                if( !$RedisDB -> set(redisWeixinLoginPrefix . $this -> getData["uid"] , $this -> getData,2*60*60) ){
                    $this -> mobileMsg(  "连接数据库失败,请与网站部联系 #redis " );
                    return false;
                }

                $this -> mobileMsg( $this -> getData["check_msg"] );
                break;
        }

        $this -> TCloseMysql();
        return;
    }

}

$App = new CGetOpenidApp();
$App -> RunApp();
return;
