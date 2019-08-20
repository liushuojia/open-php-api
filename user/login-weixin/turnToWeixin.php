<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件
	
	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在path_match里面拿数据

*/

require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CTurnToWeixinApp extends UserApp
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
            $this -> mobileMsg( $ErrMsg );
			return;
		}

        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisWeixinLoginPrefix . $this -> uid) ) {
            $this -> mobileMsg( '服务器发生错误,请与网站部联系 #redis' );
            return;
        }

        if(!$this -> TConnectMysql()){
            $this -> mobileMsg( '连接数据库失败,请与网站部联系' );
            return;
        }

        if(!$this -> weixin_accountDB -> SelectOneData($weixin_account, array(
            "weixin_id" => $this -> weixin_id
        ) )){
            $this -> mobileMsg( '服务器发生错误,请与网站部联系' );
            $this -> TCloseMysql();
            return;
        }
        $this -> TCloseMysql();

        $loginObj = $RedisDB -> get(redisWeixinLoginPrefix . $this -> uid);
        $loginObj["check_msg"] = "微信已扫码, 请您点击登录";
        $loginObj["check_status"] = 0;

        if( !$RedisDB -> set(redisWeixinLoginPrefix . $this -> uid ,$loginObj,1*60*60) ){
            $this -> mobileMsg( '连接数据库失败,请与网站部联系 #redis' );
            return false;
        }

        // 跳转至微信的地址
        $returnUrl = "http://" . $_SERVER["HTTP_HOST"] .
            "/" . $this -> prefix_path .
            "/weixin" .
            "/getOpenid" .
            "/" . $weixin_account -> weixin_id .
            "/" . $this -> uid;

        /*
         * state
         * 1 后台用户登录
         * 0 前台用户登录
        */
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize" .
            "?appid=" . $weixin_account -> app_id .
            "&redirect_uri=" . urlencode($returnUrl) .
            "&response_type=code" .
            "&scope=snsapi_base" .
            "&state=1" .
            "#wechat_redirect";

        $this -> showGetOpenId($url);
		return;
	}

	function showGetOpenId($url) {
	    $pageString = '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1">
		<title>登录确认</title>
		<style>
			html{ color: #000; background: #FFF;}
			body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,button,textarea,p,blockquote,th,td{ margin: 0; padding: 0; -webkit-tap-highlight-color: rgba(0, 174, 255, 0.4);}
			table{ border-collapse: collapse; border-spacing: 0}
			fieldset,img{ border: 0}
			address,caption,cite,code,dfn,em,strong,th,var{ font-style: normal; font-weight: normal}
			ol,ul{ list-style: none}
			caption,th{ text-align: left}
			h1,h2,h3,h4,h5,h6{ font-size: 100%; font-weight: normal}
			q:before,q:after{ content: \'\'}
			abbr,acronym{ border: 0; font-variant: normal}
			sup{ vertical-align: text-top}
			sub{ vertical-align: text-bottom}
			input,textarea,select{ font-family: inherit; font-size: inherit; font-weight: inherit}
			input,textarea,select{ *font-size: 100%}
			legend{ color: #000}
			html,body{ position: relative; height: 100%; font-family: "Microsoft YaHei"; background: #efeff4; line-height: 24px; overflow-x: hidden;}
			a:link, a:visited, a:hover, a:active{ text-decoration: none;}
			.app-box{ padding: 16px; text-align: center;}
			.app-box .app-pic{ width: 130px; display: block; margin: 72px auto 0;}
			.app-box .login-text{ color: #484849; font-size: 16px; line-height: 30px; padding: 26px 0;}
			.app-box .login-btn{ width: 100%; height: 42px; line-height: 42px; display: block; border: 1px solid #03B401; background: #04be02; color: #fff; font-size: 16px; box-sizing: border-box; -webkit-box-sizing: border-box; border-radius: 5px; -webkit-border-radius: 5px;}
			.app-box .cancel-btn{ position: absolute; left: 0; bottom: 20px; display: block; width: 100%; line-height: 30px; font-size: 13px; color: #017BFF;}
		</style>
	</head>

	<body>
		<section class="app-column">
			<div class="app-box">
				<img class="app-pic" src="data:image/jpeg;base64,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">
				<h3 class="login-text">PC版登录确认</h3>
								<a class="login-btn" href="' . $url . '">确定登录</a>
																<a class="cancel-btn" id="closeWindow" href="javascript:;">关闭窗口</a>
			</div>
		</section>
		<script>
			document.querySelector(\'#closeWindow\').addEventListener(\'click\', function(e) {
				WeixinJSBridge.invoke(\'closeWindow\', {}, function(res) {
					alert(res.err_msg);
				});
			});
		</script>
	</body>
</html>';
	    echo $pageString;
	    return;
    }
}

$App = new CTurnToWeixinApp();
$App -> RunApp();
return;
