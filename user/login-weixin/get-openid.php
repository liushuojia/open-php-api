<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件

	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在$routeMatchData里面拿数据

*/

require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");
require_once( LIB_PATH . "/include/TWeixinAction.class.php");

class CGetOpenidApp extends UserApp
{
    // 不检查访问权限, 不检查token
    public $checkRoleFlag = false;
    public $checkTokenFlag = false;

    public function GetHttpsCurl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $str = curl_exec($ch);
        curl_close($ch);
        return $str;
    }

    public $weixin_id;
    public $uid;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;
        $this -> weixin_id = (int)($routeMatchData["params"]["weixin_id"]);
        if( $this -> weixin_id<=0 )
            return false;

        $this -> uid = trim($routeMatchData["params"]["uid"]);
        if( $this -> uid=="" )
            return false;

        return true;
    }

    public $DB = array(
        "weixin_account",
    );

    function RunApp()
    {
        if(!$this -> CheckInput($ErrMsg)){
            $this -> mobileMsg( $ErrMsg);
            return;
        }

        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisWeixinLoginPrefix . $this -> uid) ){
            $this -> mobileMsg( "二维码失效,请您刷新页面重新扫码 #redis" );
            return false;
        }

        $loginObj = $RedisDB -> get(redisWeixinLoginPrefix . $this -> uid);

        if(!$this -> TConnectMysql()){
            $this -> mobileMsg( "连接数据库失败,请与网站部联系 #mysql" );
            return ;
        }

        if(!$this -> weixin_accountDB -> SelectOneData($weixin_account, array(
            "weixin_id" => $this -> weixin_id
        ) )){
            $this -> mobileMsg( "公众号已经注销,请与网站部联系 #no found" );
            $this -> TCloseMysql();
            return;
        }

        $NewWeixinDB = new TNewWeixinDB();
        $NewWeixinDB -> initWeixinAccount($weixin_account);
        if( !$NewWeixinDB -> getToken() ) {
            $this -> mobileMsg( "公众号获取授权出现异常,请与网站部联系 #no found" );
            $this -> TCloseMysql();
            return;
        }

        //获取open_id 并跳到处理页面, 处理页面不在这个php处理, 防止不小心多次刷新出现问题
        $state = trim($_REQUEST["state"]);

        $open_id_session = "WeixinOpenId_".$state."_".$this -> weixin_id;
        $open_id = $_COOKIE[$open_id_session];

        if($open_id == '')
        {
            #用户端的微信id
            $app_id = $weixin_account -> app_id;
            $app_secret = $weixin_account -> app_secret;

            $code = trim($_REQUEST["code"]);

            if(empty($code))
            {
                $this -> mobileMsg( "获取open_id失败#02");
                $this -> TCloseMysql();
                return;
            }
            $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$app_id.
                "&secret=".$app_secret."&code=".$code."&grant_type=authorization_code";

            $response = $this -> GetHttpsCurl($token_url);
            $msg = json_decode($response);

            if (isset($msg->errcode))
            {
                $msg = "<h3>error:</h3>" . $msg->errcode .
                    "<h3>msg  :</h3>" . $msg->errmsg;
                $this -> mobileMsg( $msg . "#02");
                $this -> TCloseMysql();

                return;
            }

            if($msg -> openid != '')
                $open_id = $msg -> openid;

            $_COOKIE[$open_id_session] = $open_id;
        }

        if($open_id == '') {
            $this -> mobileMsg("获取openid失败，请和网站部联系");
            $this -> TCloseMysql();
            return;
        }


        $NewWeixinDB = new TNewWeixinDB();
        $NewWeixinDB -> initWeixinAccount($weixin_account);
        if( $NewWeixinDB -> getToken() ){
            $url = "https://api.weixin.qq.com/cgi-bin/user/info" .
                "?access_token=" . $NewWeixinDB -> weixinToken .
                "&openid=" . $open_id .
                "&lang=zh_CN";

            $HttpCurlDataDB = new CHttpCurlDataDB();
            $HttpCurlDataDB -> init( $url );
            if( $HttpCurlDataDB -> sendHeader() ){
                $obj = json_decode($HttpCurlDataDB -> returnHtmlContent,true);
                if( is_array($obj) && count($obj)>0 ){
                    $loginObj["headimgurl"] = $obj["headimgurl"];
                    $loginObj["nickname"] = $obj["nickname"];
                    $loginObj["subscribe"] = $obj["subscribe"];
                    $loginObj["sex"] = $obj["sex"];
                }
            }

        }
        $this -> TCloseMysql();


        $loginObj["weixin_id"] = $this -> weixin_id;
        $loginObj["open_id"] = $open_id;
        $loginObj["check_msg"] = "获取open_id成功";
        $loginObj["state"] = $state;
        $loginObj["key"] = API_KEYWORD;
        $loginObj["time"] = time();
        $loginObj["verifyMD5"] = encryptMD5Key( $loginObj );
        unset($loginObj["key"]);

        $returnUrl = "http://" . $_SERVER["HTTP_HOST"] .
            "/" . $this -> prefix_path .
            "/weixin" .
            "/openid" .
            "/success?" . arrayToString( $loginObj ) ;

        header( "Location:" . $returnUrl );

        return;
    }

}

$App = new CGetOpenidApp();
$App -> RunApp();
return;
