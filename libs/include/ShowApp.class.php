<?php

//所有的显示数据的基类程序，都是这个类的派生类
/*
	www目录有一个 WWWApp.class.php	继承该类
		目录底下的php程序继承 WWWApp

*/
require_once(SMARTY_LIB.'/Smarty.class.php');
require_once(LIB_PATH."/include/runTime.class.php");
require_once(LIB_PATH."/include/PublicFunction.php");
require_once(LIB_PATH."/include/curl.class.php");

class ShowApp extends CRunTime
{
	//数据库连接 等基础弄好了这里需要去掉

    // api版本号
    public $prefix_path = "v1";         #访问前缀   api.hotel.com/[prefix_path]/[model]

	#smarty
	public $smarty;
	function showTimeSpent(){
		$this -> stop();
		echo "cost " . $this -> spent();
		return;
	}

	function WebInitReconstruction(){
		#在不同域名中重构该函数，初始化对象的时候需要初始化数据
		return;
	}

	#编译目录， 基类自己根据实际情况设置 默认是WWW的
	public $smarty_path = WWW_PATH;
	public $this_host = "";
	public $this_url_host = "";
	public $this_url = "";


    //判断登录用户
    public $tokenUser;
    public $tokenFlag = false;
    public $token = "";


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

	// 构建token
    function BuildToken( $data )
    {
        $data["key"] = API_KEYWORD;
        $encryptString = encryptMD5Key( $data );

        $obj = new convert(32);
        $UID = $obj->idToString($data["time"]) . "-" . $encryptString;
        return $UID;
    }
    // 跟进传入的用户生成token
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

    // 接口直接传递数据
    function curlSendData( $url, &$returnData = array(), $data = array() ) {

        $HttpCurlDataDB = new CHttpCurlDataDB();
        $HttpCurlDataDB -> headerMsg["token"] = trim($_SERVER["HTTP_TOKEN"]);
        $HttpCurlDataDB -> headerMsg["User-Agent"] = trim($_SERVER["HTTP_USER_AGENT"]);
        $HttpCurlDataDB -> init( $url );
        foreach ($data as $key => $val) {
            $HttpCurlDataDB -> {$key} = $val;
        }

        if( ! $HttpCurlDataDB -> sendHeader() ){
            $returnData = array(
                "flag" => 1,
                "msg" => "网络错误, 发送数据包出现错误",
            );
            return false;
        }

        $returnData = json_decode($HttpCurlDataDB -> returnHtmlContent,true);
        if( !is_array($returnData) || count($returnData)==0 ){
            $returnData = array(
                "flag" => 1,
                "msg" => "服务器返回数据错误 #空数据",
            );
            return false;
        }

        if( $returnData["flag"]!=0 ){
            return false;
        }

        return true;
    }
    // 检查token是否有效
    function CheckToken() {
        //判断UID是否合法,及用户是否已经登录, 这里根据redis去获取admin, 如果获取不到需要api到用户域名判断
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

                $admin_id = $obj -> stringToId($tmp[0]);
                $RedisDB = new TRedisDB();
                $redisAdminKey = redisAdminPrefix . $admin_id;

                if( !$this -> tokenUser = $RedisDB -> get($redisAdminKey)  ) {

                    //redis 获取不到数据, 需要api到用户中心判断
                    if( !$this -> curlSendData( UserTokenCheck, $returnData ) ){
                        return false;
                    }

                    $this -> tokenUser = $returnData["data"];
                }

                $this -> tokenUser = array_to_object($this -> tokenUser);

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

    function clearAdminRedis( $admin_id ){
        $admin_id = (int)($admin_id);
        if( $admin_id<=0 )
            return;

        $RedisDB = new TRedisDB();
        $redisAdminKey = redisAdminPrefix . $admin_id;
        $RedisDB -> delete( $redisAdminKey );
        return;
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

    //路由仅仅只是做了文件加载, 不进行任何数据调整
    function notFound(){
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        exit();
    }

    //显示header状态码
    public $showHeaderStatus = false;
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
        if( DebugFlag==1 ){
            $array["route"] = $routeMatchData;
        }

        echo json_encode($array, true);
        return false;
    }

	public function CheckInput(&$CheckMsg)	//检查CGI输入的合法性，应用需要重载此函数
	{
		return TRUE;
	}

	//数据库连接
	public $DB = array(
		//
		//  "admin"				#系统自动增加 \\$MysqlDefine["MysqlDatabase"]\\admin
		//  "TTableFieldExtend" => "\\THILOYSQLDB\\TTableFieldExtend",
		//
		//		$key => table name
		//		$val => database\table name
	);
	public $DBAction = array(); //auto add db msg
	public $DBFILEARRAY;
	function TConnectMysql()
	{
        Global $MysqlDefine;
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
				$val = "\\" . $MysqlDefine["MysqlDatabase"] . "\\". $key;
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
