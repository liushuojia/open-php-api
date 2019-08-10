<?php

require_once OPEN_PATH . '/include/route.php';

/*
	api 路由类 这里只是实现自动化

*/
class ApiApp  extends ShowApp {
	//显示header状态码
	public $showHeaderStatus = false;

	//判断登录用户
	public $tokenUser;
	public $tokenFlag = false;
	public $token = "";

	/* ------------------------------------------------------------------------------
	 * 检查权限一般整个站点所有页面都需要检查, 除了登录页面外; 登录页面请根据实际情况设置开关
	 */
    public $checkRoleFlag = true;   //检查用户是否有权限访问页面
    public $checkTokenFlag = true;  //检查页面访问的token是否正确
    /*
     * ------------------------------------------------------------------------------
   	 */

    function BuildToken( $data )
	{
		$data["key"] = API_KEYWORD;
		$encryptString = encryptMD5Key( $data );

		$obj = new convert(32);
		$UID = $obj->idToString($data["time"]) . "-" . $encryptString;
		return $UID;
	}

	function BuildTokenAdmin( $Admin, $dataOld = array() ) {

		$data = array(
			"userAgent" => trim($_SERVER["HTTP_USER_AGENT"]),
			"verify" => $Admin -> admin_verify,
			"id" => $Admin -> admin_id,
			"time" => time(),
		);
		if( is_array($dataOld) ){
			if( $dataOld["time"]>0 )
				$data["time"] = $dataOld["time"];

			if( $dataOld["userAgent"]!="" )
				$data["userAgent"] = $dataOld["userAgent"];
		}

		$obj = new convert(32);
		$UID = $obj->idToString($data["id"]) . "-" . $this -> BuildToken( $data );

        return $UID;
	}

	//后台需要用户已经登录的token,使用的方法
	function CheckStatus(){
		if( !$this -> CheckToken() )
			return false;

		return $this -> tokenFlag;
	}

    //检查登录用户是否有权限
    function checkRole(){
        if( $this -> tokenUser -> admin_role ==1 ) {
            return true;
        }

        return false;
    }

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

	function CheckToken() {
		//判断UID是否合法,及用户是否已经登录
		$HTTP_TOKEN = trim($_SERVER["HTTP_TOKEN"]);
		$HTTP_USER_AGENT = trim($_SERVER["HTTP_USER_AGENT"]);

		if( $HTTP_TOKEN=="" )
			return false;

        $this -> token = $HTTP_TOKEN;

		$obj = new convert(32);

		$tmp = explode("-", $HTTP_TOKEN);
		switch (count($tmp)) {
			case 2:
				$data = array(
					"userAgent" => $HTTP_USER_AGENT,
					"verify" => md5($HTTP_USER_AGENT),
					"id" => 0,
					"time" => $obj -> stringToId($tmp[0]),
				);
				$UID = $this -> BuildToken( $data );

				return ($UID===$HTTP_TOKEN);
				break;
			case 3:
				if( !in_array("admin", $this -> DB) ){
					$this -> DB[] = "admin";
				}

				if(!$this -> TConnectMysql()){
					return false;
				}

				$admin_id = $obj -> stringToId($tmp[0]);

				$RedisDB = new TRedisDB();
				$redisAdminKey = redisAdminPrefix . $admin_id;
				if( !($this -> tokenUser = $RedisDB -> get($redisAdminKey)) ){
					if(!$this -> adminDB -> SelectOneData($this -> tokenUser,array(
						"admin_id" => $admin_id,
						"admin_status" => 1,
						"is_delete" => 0,
					))) {
						return false;
					}
					$RedisDB -> set($redisAdminKey,$this -> tokenUser,60*60*2);
				}else{
					//redis出来的是数组, 需要转换成对象
					$this -> tokenUser = array_to_object($this -> tokenUser);
				}

                $data = array(
					"userAgent" => $HTTP_USER_AGENT,
					"time" => $obj -> stringToId($tmp[1]),
				);

                $UID = $this -> BuildTokenAdmin( $this -> tokenUser, $data );

                $this -> tokenFlag = ($UID===$HTTP_TOKEN);
				return $this -> tokenFlag; 
				break;
			default:
				return false;
				break;
		}
		
		//如果是合法用户 会初始化 $this -> tokenUser 为登录用户的数据
		return true;
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

	//body 默认返回为数组, 如果页面传递body非json返回空数组
	function GetBody(){
		$DATA_BODY = $GLOBALS['HTTP_RAW_POST_DATA'];
		if (empty($DATA_BODY)) {
		   $DATA_BODY = file_get_contents('php://input');
		}

		$DATA_BODY = json_decode($DATA_BODY, true);

		if( count($DATA_BODY)==0 )
			return array();

		return $DATA_BODY;
	}

	//开启路由功能
	//路由仅仅只是做了文件加载, 不进行任何数据调整
	function notFound(){
		header("HTTP/1.1 404 Not Found");  
		header("Status: 404 Not Found");  
		exit();
	}

	public $prefix_path = "v1";         #访问前缀   api.hotel.com/[prefix_path]/[model]
	function RouteAction()
	{
		Global $routeArray;
		Global $routeMatchData;

        $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
		$PATH_INFO = $_SERVER["PATH_INFO"];
		$tmp = explode("/", $PATH_INFO);

		if( $tmp<3 ){
			$this -> notFound();
			return;
		}

		$prefix_path = trim($tmp[1]);
		$controller = trim($tmp[2]);

		if( $prefix_path !== $this -> prefix_path ){
			$this -> notFound();
			return;
		}

		if( !array_key_exists( $controller, $routeArray) ){
			$this -> notFound();
			return;
		}

		$routeController = $routeArray[$controller];

		if( count($tmp) == 3)
			$tmp[3] = "";

		if( $tmp[count($tmp)-1]=="index.php" )
			$tmp[count($tmp)-1] = "";

		$filePath = "";
		if( count($tmp)==4 ){
		    /*
		     * 判断 /v1/admin  or /v1/admin/1  or /v1/admin/status
		     * 这种路径是否存在,存在则不需要正则判断
		     */
			if( array_key_exists( $tmp[3], $routeController) ){
				$ActionArray = $routeController[ $tmp[3] ];
				if( array_key_exists( $REQUEST_METHOD, $ActionArray ) ){
					$filePath = OPEN_PATH . $ActionArray[$REQUEST_METHOD];
				}
			}
		}

        //正则匹配到的字段
        $params = array();
        if($filePath ==""){
		    // 正则判断数据是否存在 或 如无正则则判断是否路径是否存在

            $lastPath = "";
            for( $i=3; $i<count($tmp); $i ++ ) {
                $lastPath .= ($lastPath!=""?"/":"");
                $lastPath .= $tmp[$i];
            }

            foreach( $routeController as $key => $val ){
				if( $key=="" ){
					continue;
				}
                preg_match_all( "/\[[:|@|#]([a-z|A-Z|_]+)\]/", $key,$path_match_key );
				if( count($path_match_key[1])>0 ){

                    $key = preg_replace("/\[:[a-z|A-Z|_]+\]/", "([a-z|A-Z|0-9|_]+)", $key);
                    $key = preg_replace("/\[#[a-z|A-Z|_]+\]/", "([0-9]+)", $key);
                    $key = preg_replace("/\[@[a-z|A-Z|_]+\]/", "([a-z|A-Z|0-9|_|-]+)", $key);

                    $key = str_replace("/", "\/", $key);
                    $key = '/^' . $key . '$/';


                    if( preg_match($key, $lastPath, $path_match) ){
                        if( array_key_exists( $REQUEST_METHOD, $val) ){
                            $params = array();
                            foreach( $path_match_key[1] as $index => $valTmp ){
                                $params[$valTmp] = $path_match[$index+1];
                            }
                            $filePath = OPEN_PATH . $val[$REQUEST_METHOD];
                            break;
                        }
                    }

                }else{
                    if( $key==$lastPath ){
                        if( array_key_exists( $REQUEST_METHOD, $val) ){
                            $filePath = OPEN_PATH . $val[$REQUEST_METHOD];
                            break;
                        }
				    }
                }
            }
		}

		if( $filePath == "" || !file_exists($filePath) ){

			if( LandTuDebug ){
				print_r($REQUEST_METHOD . " : " . $PATH_INFO);
                print_r("\n");
                if( $filePath == "" ) {
                    print_r("没有匹配的路由, 请先添加路由 ") ;
                }else{
                    print_r("匹配到了路由, 但是路由文件未创建, 路由文件地址: ". $filePath) ;
                }
				return;
			}else{
				$this -> notFound();
				return;
			}
		}

		$routeMatchData[ "REQUEST_METHOD" ] = $REQUEST_METHOD;
		$routeMatchData[ "PATH_INFO" ] = $PATH_INFO;
        $routeMatchData[ "filePath" ] = $filePath;
        $routeMatchData[ "params" ] = $params;

        include_once $filePath;
		return;
	}

	function showMsg( $flag, $pageMsg="", $data = array() )
	{
		/*返回数据格式 json
		  flag   	0  处理成功   1 出现错误
		  status 	header头返回
		  msg    	处理成功描述/或失败描述
		  data   	返回数据内容, 可为空
				 	返回数据内容   单个数据, 多条数据
						地址范例: v1/admin/1
						单条数据  data = { "name":"刘硕嘉",mobile:"13725588389" }

						地址范例: v1/admin?page_id=1&one_page_num=100s
						多条数据  data = { 
								"content" : [
									{ "name":"刘硕嘉",mobile:"13725588389" },
									{ "name":"刘硕嘉",mobile:"13725588389" },
									{ "name":"刘硕嘉",mobile:"13725588389" }
								]
								"page" : {
									"total_num" : 1000,     //总记录数
									"total_page" : 10,      //总页数
									"page_id" : 1,          //页码
									"one_page_num" : 100    //一页多少条记录
								}
							}
		 */

		$flag = (int)($flag);
		$msgShow = "";
		switch($flag){
			case 1:
				$msg = "执行失败";
				break;
			case 0:
				$msg = "OK";
				break;
			case 200:
				$msg = "OK";
				$msgShow = "[GET]：服务器成功返回用户请求的数据，该操作是幂等的（Idempotent）。";
				break;
			case 201:
				$msg = "CREATED";
				$msgShow = "[POST/PUT/PATCH]：用户新建或修改数据成功。";
				break;
			case 202:
				$msg = "Accepted";
				$msgShow = "表示一个请求已经进入后台排队（异步任务）";
				break;
			case 204:
				$msg = "NO CONTENT";
				$msgShow = "[DELETE]：用户删除数据成功。";
				break;
			case 400:
				$msgShow = "[POST/PUT/PATCH]：用户发出的请求有错误，服务器没有进行新建或修改数据的操作，该操作是幂等的。";
				$msg = "INVALID REQUEST";
				break;
			case 401:
				$msgShow = "[*]表示用户没有权限（令牌、用户名、密码错误）。";
				$msg = "Unauthorized";
				break;
			case 403:
				$msgShow = "[*] 表示用户得到授权（与401错误相对），但是访问是被禁止的。";
				$msg = "Forbidden";
				break;
			case 404:
				$msgShow = "[*]：用户发出的请求针对的是不存在的记录，服务器没有进行操作，该操作是幂等的。";
				$msg = "NOT FOUND";
				break;
			case 406:
				$msgShow = "[GET]：用户请求的格式不可得（比如用户请求JSON格式，但是只有XML格式）。";
				$msg = "Not Acceptable";
				break;
			case 410:
				$msgShow = "[GET]：用户请求的资源被永久删除，且不会再得到的。";
				$msg = "Gone";
				break;
			case 410:
				$msgShow = "[GET]：用户请求的资源被永久删除，且不会再得到的。";
				$msg = "Gone";
				break;
			case 422:
				$msgShow = "[POST/PUT/PATCH] 当创建一个对象时，发生一个验证错误。";
				$msg = "Unprocesable entity";
				break;							
			case 500:
				$msgShow = "[*]：服务器发生错误，用户将无法判断发出的请求是否成功。";
				$msg = "INTERNAL SERVER ERROR ";
				break;								
		}


		$status = $flag;
		if( $this -> showHeaderStatus ){
			switch($flag){
				case 0:
				case 1:
					header('HTTP/1.1 200 OK.'); 
					break;
				default:
					header('HTTP/1.1 ' .$flag. ' ' .$msg. ''); 
					break;
			}
		}else{
			switch($flag){
				case 0:
				case 200:
				case 201:
				case 202:
				case 203:
				case 204:
					header('HTTP/1.1 200 OK.'); 
					$flag = 0;
					break;
				case 1:
				default:
					$flag = 1;
					break;
			}			
		}

		$this -> stop();
		$tmp = $this -> spent();

		if($tmp<1000)
			$excute_time = $tmp . "ms";
		else{
			$excute_time = formatSecond(ceil($tmp/1000));
		}

		if( $pageMsg!="" )
			$msg = $pageMsg;

		$array = array(
			"flag" => $flag,
			"status" => $status,
			"msg" => $msg,
			"excute_time" => $excute_time,
			"data" => $data,
		);

		Global $routeMatchData;
		if( LandTuDebug==1 ){
			$array["route"] = $routeMatchData;
		}


		echo json_encode($array, true);
		return false;
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
