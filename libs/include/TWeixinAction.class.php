<?php

require_once(LIB_PATH . "/include/curl.class.php");
require_once(LIB_PATH . "/include/PublicFunction.php");
#------------------------------------------------------------------

class TNewWeixinDB extends CHttpCurlDataDB
{
	public $weixinToken = "";
	public $weixin_user_id = "";			//	微信原始ID
	public $appID = "";						//	开发者后台的appID
	public $AppSecret = "";					//	开发者后台的AppSecret
	public $weixinTokenDef = "";			//	开发者中心自己设置的token
	public $EncodingAESKey = "";			//	加解密密钥
	public $EncryptFlag = 0;				//	加密开关 1开 0关

	public $WeixinAccount = null;
	public function initWeixinAccount( $WeixinAccount )	//	每个类初始化的时候执行的咚咚
	{
		$this -> weixin_user_id = $WeixinAccount -> weixin_user_id;
		$this -> appID = $WeixinAccount -> app_id;
		$this -> AppSecret = $WeixinAccount -> app_secret;
		$this -> weixinTokenDef = $WeixinAccount -> token_web;
		$this -> EncodingAESKey = $WeixinAccount -> encoding_aes_key;
		$this -> EncryptFlag = ($WeixinAccount -> encoding_type>0)?1:0;
			
		#---------------------------------------------------------------
		$this -> WeixinAccount = $WeixinAccount;

		return;
	}

	//数据库连接
	public $WeixinAccountDB, $WeixinMenuDB, $WeixinKeyTextDB, $WeixinImgContentDB;
	public $connectFlag = false;
	public $errorMsg = "";

	public function TConnectMysql()
	{

		if(!$this -> connectFlag)
		{
			Global $MysqlDefine;

		    $className = "\\" . $MysqlDefine["MysqlDatabase"] . "\\CWeixinAccountDB";
            $this -> WeixinAccountDB  = new $className();
            if(!$this -> WeixinAccountDB -> ConnectMysql())
                return false;

            $className = "\\" . $MysqlDefine["MysqlDatabase"] . "\\CWeixinMenuDB";
            $this -> WeixinMenuDB  = new $className();
            if(!$this -> WeixinMenuDB -> ConnectMysql())
                return false;

            $className = "\\" . $MysqlDefine["MysqlDatabase"] . "\\CWeixinKeyTextDB";
            $this -> WeixinKeyTextDB  = new $className();
            if(!$this -> WeixinKeyTextDB -> ConnectMysql())
                return false;

            $className = "\\" . $MysqlDefine["MysqlDatabase"] . "\\CWeixinImgContentDB";
            $this -> WeixinImgContentDB  = new $className();
            if(!$this -> WeixinImgContentDB -> ConnectMysql())
                return false;

			$this -> connectFlag = true;
		}

		return TRUE;
	}
			
	public function TCloseMysql()
	{
		$this -> WeixinAccountDB -> CloseMysql();
		unset($this -> WeixinAccountDB);

		$this -> WeixinMenuDB -> CloseMysql();
		unset($this -> WeixinMenuDB);

		$this -> WeixinKeyTextDB -> CloseMysql();
		unset($this -> WeixinKeyTextDB);

		$this -> WeixinImgContentDB -> CloseMysql();
		unset($this -> WeixinImgContentDB);
			
		return TRUE;
	}
		
	public function setWeixinAccountAction( $weixin_user_id )
	{
		if(!$this -> TConnectMysql())
		{
			$this -> errorMsg = "连接到数据库失败";
			return false;
		}

		if( !$this -> WeixinAccountDB -> SelectOneData($WeixinAccount,array( "weixin_user_id" => $weixin_user_id )) )
		{
			$this -> errorMsg = "获取微信用户端未添加";
			return false;
		}

		$this -> initWeixinAccount($WeixinAccount);
		return true;
	}

	public function getToken()
	{
		if(!$this -> TConnectMysql())
		{
			$this -> errorMsg = "连接到数据库失败";
			return false;
		}

		$reGetDataFlag = false;
		if( $this -> WeixinAccount != null )
		{
			if( $this -> WeixinAccount -> weixin_token!="" && $this -> WeixinAccount -> token_exp>=time() )
			{
				$reGetDataFlag = true;
				$this -> weixinToken = $this -> WeixinAccount -> weixin_token;
			}
		}

		if( !$reGetDataFlag )
		{
			$url  = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this -> appID . "&secret=". $this -> AppSecret;
			$this -> init( $url );
			if(!$this -> sendHeader()) {
				$this -> errorMsg = "获取weixin网站信息失败！";
				return false;
			}

			$obj = json_decode($this -> returnHtmlContent);

			if( !$this -> weixinErrorCheck( $obj ) )
				return false;
				
			$expires_in = (int)($obj -> expires_in);
			if( $expires_in>2000 )
				$expires_in -= 1000;
				
			$expire_time = time() + $expires_in;
			$this -> weixinToken = $obj -> access_token;

			#有些情况可能不会传 WeixinAccount
			if( $this -> WeixinAccount != null && $this -> TConnectMysql() )
			{
				#-----------------------------------------------------------
				$this -> WeixinAccount -> weixin_token = $this -> weixinToken;
				$this -> WeixinAccount -> token_exp = $expire_time;

				$editArray = array(
					"weixin_token" => $this -> weixinToken,
					"token_exp" => $expire_time,
				);
				$searchArray = array( "weixin_id" => $this -> WeixinAccount -> weixin_id );
				$this -> WeixinAccountDB -> UpdateDataQuickEditMore($editArray, $searchArray);
			}

		}
		return true;
	}

	function getJssdk( &$signPackage )
	{
		if(!$this -> TConnectMysql())
		{
			$this -> errorMsg = "连接到数据库失败";
			return false;
		}

		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		$jssdk = new JSSDK($this -> appID, $this -> AppSecret, $this -> WeixinAccount);
		$signPackage = $jssdk -> GetSignPackage();

		if( $jssdk -> EditFlag )
		{
			$editArray = array(
				"jsapi_ticket" => $this -> WeixinAccount -> jsapi_ticket,
				"jsapi_ticket_exp" => $this -> WeixinAccount -> jsapi_ticket_exp,
			);
			$searchArray = array( "weixin_id" => $this -> WeixinAccount -> weixin_id );
			$this -> WeixinAccountDB -> UpdateDataQuickEditMore($editArray, $searchArray);
		}
		return true;
	}

	public function getGuestUserInfo($open_id)
	{
		//获取授权用户个人信息
		if($open_id=='')
			return false;

		// 先取token
		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this -> weixinToken."&openid=".$open_id."&lang=zh_CN";
		$this -> init( $url );
		if(!$this -> sendHeader())
		{
			$this -> errorMsg = "POST数据失败！";
			return false;
		}

		$obj = json_decode($this -> returnHtmlContent);
		if( !$this -> weixinErrorCheck( $obj ) )
			return false;

		return $obj;
	}
		
	public function downMedia($media_id,$safePath)
	{
		if($media_id=="")
		{
			$this -> errorMsg = "media_id为空";
			return false;
		}
		if($safePath=="")
		{
			$this -> errorMsg = "保存路径为空";
			return false;
		}

		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->weixinToken."&media_id=".$media_id;
		$this -> init( $url );
		if(!$this -> sendHeader())
		{
			$this -> errorMsg = "POST数据失败！";
			return false;
		}

		$this -> buildFile($this -> returnHtmlContent,$safePath);
		return true;
	}
		
	function getHostIP()
	{
		if( $this -> weixinToken=="" )
		{
			$this -> errorMsg = "请先获取getToken";
			return false;
		}

		$url  = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=" . $this -> weixinToken;
		$this -> init( $url );
		if(!$this -> sendHeader())
		{
			$this -> errorMsg = "获取weixin网站信息失败！";
			return false;
		}

		$obj = json_decode($this -> returnHtmlContent);
		return $obj;
	}

	public function getEncryptMsg($msg)
	{
		//加密
		if($this->EncryptFlag==0)
			return $msg;

		$app = new WXBizMsgCrypt($this -> weixinToken, $this -> EncodingAESKey, $this -> appID);

		$tempMsg = "";
		$timestamp = time();
		$nonce = random(16);
		$errCode = $app -> encryptMsg( $msg, $timestamp, $nonce, $tempMsg );
			
		if ($errCode != 0) {
			return $msg;
		}
		return $tempMsg;
	}

	//查询菜单
	public function getWeixinMenu()
	{
		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		if( !$this -> TConnectMysql() )
		{
			$this -> errorMsg = "无法连接数据库";
			return false;
		}

		if( $this -> WeixinAccount -> weixin_id==0 )
		{
			$this -> errorMsg = "参数传递错误";
			return false;
		}

		$searchKey = array(
			"weixin_id" => $this -> WeixinAccount -> weixin_id,
		);
		$this -> WeixinMenuDB -> DeleteData($searchKey);


		#------------------------------------------------------------------------------------------
		$url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=" .$this -> weixinToken;

		$this -> init( $url );
		if(!$this -> sendHeader())
		{
			$this -> errorMsg = "POST数据失败！";
			return false;
		}

		$obj = json_decode($this -> returnHtmlContent);
		if( !$this -> weixinErrorCheck( $obj ) )
			return false;

		$buttonArray = $obj -> menu;
		$buttonArray = $buttonArray -> button;
		$WeixinMenuArray = array();
		$codeIndex = 0;
		foreach( $buttonArray as $dataArray )
		{
			$codeIndex++;
			$WeixinMenu = new $this -> WeixinMenuDB -> tableItemClass();
			$WeixinMenu -> menu_id = 0;
			$WeixinMenu -> weixin_id = (int)($this -> WeixinAccount -> weixin_id);
			$WeixinMenu -> menu_code = ($codeIndex<10 ? "0":"") . $codeIndex;
			$WeixinMenu -> menu_name = trim($dataArray -> name);

			switch( trim($dataArray -> type) )
			{
				case "click":
					$WeixinMenu -> menu_type = "Click";
					$WeixinMenu -> menu_content = trim($dataArray -> key);
					$this -> WeixinMenuDB -> CreateData($WeixinMenu);
					break;
				case "view":
					$WeixinMenu -> menu_type = "View";
					$WeixinMenu -> menu_content = trim($dataArray -> url);
					$this -> WeixinMenuDB -> CreateData($WeixinMenu);
					break;
				default:
					$WeixinMenu -> menu_type = "Menu";
					$this -> WeixinMenuDB -> CreateData($WeixinMenu);
					$WeixinMenu -> menu_sec = array();
					$codeSecIndex = 0;
					foreach( $dataArray -> sub_button as $dataSecArray )
					{
						$codeSecIndex++;

						$WeixinMenuSec = new $this -> WeixinMenuDB -> tableItemClass();
						$WeixinMenuSec -> menu_id = 0;
						$WeixinMenuSec -> weixin_id = (int)($this -> WeixinAccount -> weixin_id);
						$WeixinMenuSec -> menu_code = $WeixinMenu -> menu_code . ($codeSecIndex<10 ? "0":"") . $codeSecIndex;
						$WeixinMenuSec -> menu_name = trim($dataSecArray -> name);

						switch( trim($dataSecArray -> type) )
						{
							case "click":
								$WeixinMenuSec -> menu_type = "Click";
								$WeixinMenuSec -> menu_content = trim($dataSecArray -> key);
								break;
							case "view":
								$WeixinMenuSec -> menu_type = "View";
								$WeixinMenuSec -> menu_content = trim($dataSecArray -> url);
								break;
						}

						$this -> WeixinMenuDB -> CreateData($WeixinMenuSec);
						$WeixinMenu -> menu_sec[] = $WeixinMenuSec;
					}
					break;
			}
			$WeixinMenuArray[] = $WeixinMenu;
		}
		return	true;
	}

	//创建菜单 https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN
	public function setWeixinMenu()
	{
		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		if( !$this -> TConnectMysql() )
		{
			$this -> errorMsg = "无法连接数据库";
			return false;
		}

		if( $this -> WeixinAccount -> weixin_id==0 )
		{
			$this -> errorMsg = "参数传递错误";
			return false;
		}

		$searchKey = array(
			"weixin_id" => $this -> WeixinAccount -> weixin_id,
			"order_by" => array(
				"menu_code" => "asc",
			),
		);
		$this -> WeixinMenuDB -> QueryData( $WeixinMenuListTemp, 0, 0, $searchKey );

		$WeixinMenuList = array();
		$WeixinMenuListTwo = array();
		foreach( $WeixinMenuListTemp as $WeixinMenu )
		{
			if( strlen($WeixinMenu -> menu_code) == ONE_WEIXIN_MENU_CODE_LEN )
			{
				$WeixinMenu -> secArray = array();
				$WeixinMenuList[ $WeixinMenu -> menu_code ] = $WeixinMenu;
			}else{
				$key = substr( $WeixinMenu -> menu_code,0, ONE_WEIXIN_MENU_CODE_LEN);
				if( !is_array($WeixinMenuListTwo[ $key ]) )
				{
					$WeixinMenuListTwo[ $key ] = array();
				}

				$WeixinMenuListTwo[ $key ][] = $WeixinMenu;
			}
		}

		foreach( $WeixinMenuList as $WeixinMenu )
		{
			if( array_key_exists($WeixinMenu -> menu_code, $WeixinMenuListTwo) )
			{
				$WeixinMenu -> secArray = $WeixinMenuListTwo[$WeixinMenu -> menu_code];
			}
		}

		$buttonPostString = "";
		foreach( $WeixinMenuList as $WeixinMenu )
		{
			$thisButtonString  = "";
			$thisButtonString .= '"name":"' . $WeixinMenu -> menu_name . '"';
			switch( $WeixinMenu -> menu_type )
			{
				case "Menu":
					$subArr = "";
					foreach( $WeixinMenu -> secArray as $WeixinMenuSec )
					{
						$subStr = '';
						$subStr .= '"name":"' . $WeixinMenuSec -> menu_name . '"';
						switch( $WeixinMenuSec -> menu_type )
						{
							case "Click":
								$subStr .= ',"type":"click"';
								$subStr .= ',"key":"' . $WeixinMenuSec -> menu_content . '"';
								break;
							case "View":
								$subStr .= ',"type":"view"';
								$subStr .= ',"url":"' . $WeixinMenuSec -> menu_content . '"';
								break;
						}
						$subStr = "{".$subStr."}";
						if($subArr!='')
							$subArr .= ",";
						$subArr .= $subStr;
					}

					$thisButtonString .= ',"sub_button":['.$subArr.']';
					break;
				case "Click":
					$thisButtonString .= ',"type":"click"';
					$thisButtonString .= ',"key":"' . $WeixinMenu -> menu_content . '"';
					break;
				case "View":
					$thisButtonString .= ',"type":"view"';
					$thisButtonString .= ',"url":"' . $WeixinMenu -> menu_content . '"';
					break;
			}

			if( $buttonPostString!="" )
				$buttonPostString .= ",";
				
			$buttonPostString .= "{".$thisButtonString."}";
		}

		$buttonPostString = '{"button":['.$buttonPostString."]}";

		#-----------------------------------------------------------------------------------------------
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $this -> weixinToken;
		$this -> postDataString = $buttonPostString;

		$this -> init( $url );
		if(!$this -> sendHeader())
		{
			$this -> errorMsg = "POST数据失败！";
			return false;
		}

		$obj = json_decode($this -> returnHtmlContent);
		if( !$this -> weixinErrorCheck( $obj ) )
			return false;

		return	true;
	}



	//回复文本消息 被动
	public function ResponseTextMsg($content,$open_id)
	{
		if($content=='' || $open_id=='')
			return false;

		$time = time();
		$str = "<xml>
				<ToUserName><![CDATA[".$open_id."]]></ToUserName>
				<FromUserName><![CDATA[".$this -> weixin_user_id."]]></FromUserName>
				<CreateTime>".$time."</CreateTime>
				<MsgType><![CDATA[text]]></MsgType>
				<Content><![CDATA[".$content."]]></Content>
				<FuncFlag>0</FuncFlag>
				</xml>";

		echo $str;
		return true;
	}

	//回复图文消息
	public function ResponseTextImgMsg($WeixinImgContentList,$open_id)
	{

		$num = count($WeixinImgContentList);
		if($num>10)
			$num = 10;

		if($num==0)
			return false;
			
		$WeixinImgContent = current($WeixinImgContentList);

		$time = time();
		$str = "<xml>
				<ToUserName><![CDATA[".$open_id."]]></ToUserName>
				<FromUserName><![CDATA[".$this -> weixin_user_id."]]></FromUserName>
				<CreateTime>".$time."</CreateTime>
				<MsgType><![CDATA[news]]></MsgType>
				<Content><![CDATA[".$WeixinImgContent -> img_title."]]></Content>
				<ArticleCount>".$num."</ArticleCount>
				<Articles>";

		$index = 0;
		foreach($WeixinImgContentList as $WeixinImgContent)
		{
			$str .= "<item>
					<Title><![CDATA[".$WeixinImgContent -> img_title."]]></Title> 
					<Description><![CDATA[".$WeixinImgContent -> img_desc."]]></Description>
					<PicUrl><![CDATA[".$WeixinImgContent -> img_url."]]></PicUrl>
					<Url><![CDATA[".$WeixinImgContent -> turn_url."]]></Url>
					</item>";

			if( $index++>=10 )
				break;
		}
		$str .= "</Articles>
				</xml>";
		
		echo $str;
		return true;
	}

	//发送文本消息 主动
	public function postAction( $url, $postStr ){
		$this -> postDataString = $postStr;
		$this -> init( $url );

		if(!$this -> sendHeader())
		{
			$this -> errorMsg = "POST数据失败！";
			return false;
		}
		$obj = json_decode($this -> returnHtmlContent);
		if( !$this -> weixinErrorCheck( $obj ) )
		{
			$this -> errorMsg = $obj -> errmsg;
			return false;
		}

		return true;
	}

	public function sentTextMsg($content,$open_id)
	{
		if($content=='' || $open_id=='')
			return false;

		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		$str = "{
			\"touser\":\"".$open_id."\",
			\"msgtype\":\"text\",
			\"text\":{
				\"content\":\"".$content."\"
			}
		}";
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this -> weixinToken;

		return $this -> postAction( $url, $str );
	}

	public function tempalteMsg($postStr)
	{
		//模板消息 主动
		if( $postStr=='')
			return false;
			
		if(!$this -> getToken())
		{
			$this -> errorMsg = "获取token失败";
			return false;
		}

		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$this->weixinToken;
		return $this -> postAction( $url, $postStr );
	}

	public function SentEmail($content)
	{
		$NewEmail = new Email();
		$NewEmail -> EmailFrom ="service@landtu.com";
		$NewEmail -> FromName ="蓝途旅游网";

		$tempArray = array(
			"Email" => "hiloy@landtu.com",
		);
		$NewEmail -> AddAddress[] = $tempArray;

		#email标题
		$NewEmail -> Subject = "测试返回";
		#邮件内容
		$NewEmail -> AltBody = $content;  //纯文本，不支持html的显示此内容
		$NewEmail -> Body =  str_replace(chr(92).chr(110),"<br>",$content);  //将纯文本中的/n转换为 换行符 [html内容]

		$CEmailDB = new EmailDB();
		if(!$CEmailDB->SentEmail($NewEmail)){
			//email 发送失败
		}
	}

	public static function writeWeixinTransmitData( $filePath = "./log.txt" )
	{
		$string = "";
		$string .= "\n\nurl\n";
		$string .= $_SERVER["REQUEST_URI"];
		$string .= "\n\n";
		$string .= "\$_GET";
		foreach( $_GET as $key => $val )
		{
			$string .= "\n" . $key;
			$string .= " => ";
			$string .= $val;
		}

		$string .= "\n\n";
		$string .= "\$_POST";
		foreach( $_POST as $key => $val )
		{
			$string .= "\n" . $key;
			$string .= " => ";
			$string .= $val;
		}


		$string .= "\n\n";
		$string .= "\$GLOBALS[\"HTTP_RAW_POST_DATA\"]";
		$string .= "\n";
		$string .= $GLOBALS["HTTP_RAW_POST_DATA"];

		$string .= "\n\n";
		$string .= "\file_get_contents[\"php://input\"]";
		$string .= "\n";
		$string .= file_get_contents('php://input');


		if (!$handle = @fopen($filePath, 'a'))
			return false;

		if (@fwrite($handle, "\n".$string)=== FALSE)
			return false;

		@fclose($handle);
		return true;
	}

	public function weixinErrorCheck( $obj )
	{
		if( $obj -> errcode == 0 )
			return true;

		$errcodeMsg = array(
			"-1"	=>	"系统繁忙",
			"40001"	=>	"获取access_token时AppSecret错误，或者access_token无效",
			"40002"	=>	"不合法的凭证类型",
			"40003"	=>	"不合法的OpenID",
			"40004"	=>	"不合法的媒体文件类型",
			"40005"	=>	"不合法的文件类型",
			"40006"	=>	"不合法的文件大小",
			"40007"	=>	"不合法的媒体文件id",
			"40008"	=>	"不合法的消息类型",
			"40009"	=>	"不合法的图片文件大小",
			"40010"	=>	"不合法的语音文件大小",
			"40011"	=>	"不合法的视频文件大小",
			"40012"	=>	"不合法的缩略图文件大小",
			"40013"	=>	"不合法的APPID",
			"40014"	=>	"不合法的access_token",
			"40015"	=>	"不合法的菜单类型",
			"40016"	=>	"不合法的按钮个数",
			"40017"	=>	"不合法的按钮个数",
			"40018"	=>	"不合法的按钮名字长度",
			"40019"	=>	"不合法的按钮KEY长度",
			"40020"	=>	"不合法的按钮URL长度",
			"40021"	=>	"不合法的菜单版本号",
			"40022"	=>	"不合法的子菜单级数",
			"40023"	=>	"不合法的子菜单按钮个数",
			"40024"	=>	"不合法的子菜单按钮类型",
			"40025"	=>	"不合法的子菜单按钮名字长度",
			"40026"	=>	"不合法的子菜单按钮KEY长度",
			"40027"	=>	"不合法的子菜单按钮URL长度",
			"40028"	=>	"不合法的自定义菜单使用用户",
			"40029"	=>	"不合法的oauth_code",
			"40030"	=>	"不合法的refresh_token",
			"40031"	=>	"不合法的openid列表",
			"40032"	=>	"不合法的openid列表长度",
			"40033"	=>	"不合法的请求字符，不能包含\uxxxx格式的字符",
			"40035"	=>	"不合法的参数",
			"40038"	=>	"不合法的请求格式",
			"40039"	=>	"不合法的URL长度",
			"40050"	=>	"不合法的分组id",
			"40051"	=>	"分组名字不合法",
			"41001"	=>	"缺少access_token参数",
			"41002"	=>	"缺少appid参数",
			"41003"	=>	"缺少refresh_token参数",
			"41004"	=>	"缺少secret参数",
			"41005"	=>	"缺少多媒体文件数据",
			"41006"	=>	"缺少media_id参数",
			"41007"	=>	"缺少子菜单数据",
			"41008"	=>	"缺少oauth code",
			"41009"	=>	"缺少openid",
			"42001"	=>	"access_token超时",
			"42002"	=>	"refresh_token超时",
			"42003"	=>	"oauth_code超时",
			"43001"	=>	"需要GET请求",
			"43002"	=>	"需要POST请求",
			"43003"	=>	"需要HTTPS请求",
			"43004"	=>	"需要接收者关注",
			"43005"	=>	"需要好友关系",
			"44001"	=>	"多媒体文件为空",
			"44002"	=>	"POST的数据包为空",
			"44003"	=>	"图文消息内容为空",
			"44004"	=>	"文本消息内容为空",
			"45001"	=>	"多媒体文件大小超过限制",
			"45002"	=>	"消息内容超过限制",
			"45003"	=>	"标题字段超过限制",
			"45004"	=>	"描述字段超过限制",
			"45005"	=>	"链接字段超过限制",
			"45006"	=>	"图片链接字段超过限制",
			"45007"	=>	"语音播放时间超过限制",
			"45008"	=>	"图文消息超过限制",
			"45009"	=>	"接口调用超过限制",
			"45010"	=>	"创建菜单个数超过限制",
			"45015"	=>	"回复时间超过限制",
			"45016"	=>	"系统分组，不允许修改",
			"45017"	=>	"分组名字过长",
			"45018"	=>	"分组数量超过上限",
			"46001"	=>	"不存在媒体数据",
			"46002"	=>	"不存在的菜单版本",
			"46003"	=>	"不存在的菜单数据",
			"46004"	=>	"不存在的用户",
			"47001"	=>	"解析JSON/XML内容错误",
			"48001"	=>	"api功能未授权",
			"50001"	=>	"用户未授权该api",
		);

		if ( array_key_exists( $obj -> errcode, $errcodeMsg))
		{
			$this -> errorMsg = $errcodeMsg[ $obj -> errcode ];
		}

		if($this -> errorMsg=="")
			$this -> errorMsg = "未知错误";

		return false;
	}

}
