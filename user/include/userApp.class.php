<?php

/*
	api 路由类 这里只是实现自动化

*/
class UserApp  extends ShowApp {
    /* ------------------------------------------------------------------------------
     * 检查权限一般整个站点所有页面都需要检查, 除了登录页面外; 登录页面请根据实际情况设置开关
     */
    public $checkRoleFlag = true;   //检查用户是否有权限访问页面
    public $checkTokenFlag = true;  //检查页面访问的token是否正确

    /*
     * ------------------------------------------------------------------------------
   	 */

    function WebInitReconstruction() {
        #在不同域名中重构该函数，初始化对象的时候需要初始化数据

        //检查页面访问的token是否正确
        if(
            $this -> checkTokenFlag
            && !($this-> CheckToken())
        ) {
            $this -> showMsg( 401, "登录超时 #token is wrong" );
            exit();
        }

        //检查用户是否有权限访问页面
        if( $this -> checkRoleFlag ){
            if( !$this -> CheckStatus() ){
                // 登录超时
                $this -> showMsg( 401, "登录超时 #token time out" );
                exit();
            }
            if( !$this -> checkRole() ){
                // 检查是否有权限
                $this -> showMsg( 403, "无权限 #403 Forbidden" );
                exit();
            }
        }
        return;
    }

	function SendSms( $mobile, $smscode, &$ErrorMsg="" ){
		$queueKey = redisSmsQueue;
		$checkKey = redisSmsCheckKey . "_" . $mobile;

		$RedisDB = new TRedisDB();
		if( $RedisDB -> exists($checkKey) ) {
			$ErrorMsg = "请在60秒后重新获取短信";
			return false;
		}

		if( !$RedisDB -> set($checkKey,$smscode,60) ){
			$ErrorMsg = "连接数据库失败,请与网站部联系 #redis";
			return false;
		}

		$array = array(
			"mobile" => $mobile,
			"content" => "您的验证码是：" . $smscode . "。请不要把验证码泄露给其他人。",
		);
		if( !$RedisDB -> rPush($queueKey,$array) ){
			$ErrorMsg = "连接数据库失败,请与网站部联系 #redis rPush";
			return false;
		}
		return true;
	}
	function CheckSms( $mobile, $smscode, &$ErrorMsg="" ){

		$checkKey = redisSmsCheckKey . "_" . $mobile;
		$RedisDB = new TRedisDB();
		if( !$RedisDB -> exists($checkKey) ) {
			$ErrorMsg = "验证码已经失效,请您重新获取";
			return false;
		}

		if( !($code = $RedisDB -> get($checkKey)) ){
			$ErrorMsg = "连接数据库失败,请与网站部联系 #redis get";
			return false;
		}

		$code = trim($code);
		$smscode = trim($smscode);

		return ($code==$smscode);
	}

	// 微信处理完后 自动关闭页面
    function mobileMsg($msg){
        echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1">
		<title>页面发生错误</title>
	</head>
	<body><div style="margin: 10px;">' . $msg .  ', <span id="autoSpan">3秒后自动关闭</span></div>
		<script language="JavaScript">
		var index = 3;
		setInterval(function() {
		  document.getElementById("autoSpan").innerHTML = ( (--index) + "秒后自动关闭" );
		  if( index <=0 ){
            WeixinJSBridge.invoke(\'closeWindow\', {}, function(res) {
			    alert(res.err_msg);
		    });		      
		  }
		},1000);
        </script>
	</body>
</html>';
        return;
    }
}
