<?php
# 下载类 或 post 数据
define("debug",0);

class CHttpPostDataDB
{
	public $hostMsg = array(
		"host" => "",
		"ip" => "",
		"port" => "80",
		"url" => "",
		"scheme" => "HTTP",
		"method" => "GET",			# GET or POST    postData 只有在post的时候才生效
	);
	public $headerMsg = array(
		"Referer" => "",
		"User-Agent" => "User-Agent:Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; GTB6; SLCC1; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.5.30729; .NET CLR 3.0.30618)",
		"Content-Type" => "application/x-www-form-urlencoded",
		"Accept-Type" => "*/*",
		"Accept-Encoding" => "",
		"Cache-Control" => "",
	);
	
	public $connect;						# 连接到服务器的变量
	public $Connection = "Close";			# http 1.1 下载文件或者其他请使用 close ， 特殊情况请使用 Keep-Alive，程序去自动截取，如果没有返回长度，有点慢

	public $postDataString = "";			# 如果该string存在则不考虑 postData
	public $postData = array();				# key => value 

	public $CookieDataString = "";			# 如果该string存在则不考虑 CookieData
	public $CookieData = array();			# key => value 

	public $headerArray = array();			# 头文件信息
	public $errorMsg = "";					# 错误信息

	public $returnHtml = "";				# 返回的信息 包括Header信息 
	public $returnHtmlContent = "";			# 返回的信息 不包括Header信息 
	public $returnHtmlHeaderContent = "";	# 返回的信息 Header信息 

	public $timeOut = 10;					# 超时时间
	public function init($url)
	{
		if($url == "") 
			return false;

		$UrlArray = parse_url($url);
		$this -> hostMsg["host"] = $UrlArray["host"];
		$this -> hostMsg["ip"] = gethostbyname($this -> hostMsg["host"]);
		$this -> hostMsg["url"] =  $UrlArray["path"];
		$this -> hostMsg["scheme"] =  strtoupper($UrlArray["scheme"]);
		switch( $this -> hostMsg["scheme"] )
		{
			case "HTTPS":
				$this -> hostMsg["port"] =  443;
				break;
			case "HTTP":
				$this -> hostMsg["port"] =  80;
				break;
		}
		$UrlArray = explode("?",$url);
		if($UrlArray[1]!="")
		{
			$this -> hostMsg["url"] .="?".$UrlArray[1];
		}
		return true;
	}
	public function connectHost()
	{
		if($this -> hostMsg["ip"] =="")
		{
			$this -> errorMsg =  "域名解析地址错误";
			return false;
		}
		$this -> connect = fsockopen($this -> hostMsg["ip"], $this -> hostMsg["port"], $errno, $errstr, $this -> timeOut);
	
		if (!$this -> connect ) 
		{
			$this -> errorMsg =  "$errstr ($errno)<br />\n";
			return false;
		}

		return true;;
	}
	public function closeHost()
	{
		@fclose($this -> connect);
	}
	public function setPostKeyValue($name,$value)
	{
		if($name=="") 
			return false;
		$this -> postData[$name] = $value;
		return true;
	}
	public function setCookieKeyValue($name,$value)
	{
		if($name=="") 
			return false;
		$this -> CookieData[$name] = $value;
		return true;
	}
	public function ContentSafeToFlie($FilePath)
	{
		$filename = $FilePath;
		$somecontent = $this -> returnHtmlContent;
		if(!$this -> buildFile($somecontent,$filename))
		{
			$this -> errorMsg =  "不能打开文件 $filename";
			return false;
		}
		return true;
	}
	public function sendHeader()
	{
		if(debug==1)
		{
			echo "\n连接服务器";
		}
		if(!$this -> connectHost())
		{
			return false;
		}
		if(debug==1)
		{
			echo "\n连接成功";
		}
		# POST 数据初始化
		if($this -> postDataString =="")
		{
			$PostDataString = "";
			foreach($this -> postData  as $key => $value)
			{
				if($value!="" && $key!="")
				{
					if($PostDataString !="") 
						$PostDataString .="&";
					$PostDataString .= urlencode($key)."=".urlencode($value);
				}
			}		
		}else{
			$PostDataString = $this -> postDataString;
		}

		# 发送数据类型
		if($PostDataString!="") 
			$this -> hostMsg["method"] = "POST";

		$HeaderString = $this -> hostMsg["method"] . " " . $this -> hostMsg["url"] ." ". $this -> hostMsg["scheme"] ."/1.1\r\n";
		foreach($this -> headerMsg as $key => $value)
		{
			if($value!="") 
				$HeaderString .= $key.":".$value."\r\n";		
		}
		$HeaderString .= "Host: ".$this -> hostMsg["host"]."\r\n";

		if($PostDataString != "") 
			$HeaderString .= "Content-Length:".strlen($PostDataString)."\r\n";

		if($this -> CookieDataString =="")
		{
			$CookieString = "";
			foreach($this -> CookieData as $key => $value)
			{
				if($value!="" && $key!="")
				{
					if($CookieString =="") 
						$CookieString .="Cookie: ";
					$CookieString .= urlencode($key)."=".urlencode($value).";";
				}
			}
			if($CookieString!="") $CookieString .="\r\n";
			$HeaderString .= $CookieString;		
		}else{
			$HeaderString .= "Cookie: ".$this -> CookieDataString;	
		}
		
		$HeaderString .= "Connection: ".$this -> Connection."\r\n\r\n";
		if($PostDataString != "")
		{
			$HeaderString .= $PostDataString."\r\n";
		}

		//集阻塞/非阻塞模式流,$block==true则应用流模式
		stream_set_blocking($this -> connect, true);

		//设置流的超时时间
		stream_set_timeout($this -> connect, $this -> timeOut);
		if(debug==1)
		{
			echo "\n发送头";
		}
		fwrite($this->connect, $HeaderString);
		if(debug==1)
		{
			echo "\n发送头成功";
		}

		if(debug==1)
		{
			echo "\n获取状态";
		}
		$status = stream_get_meta_data($this->connect);
		if(debug==1)
		{
			echo "\n获取状态成功";
		}
		if($status['timed_out'])
		{
			$this -> errorMsg =  "操作超时";
			return false;
		}

		$this -> returnHtml = "";
		$this -> returnHtmlContent = "";
		$this -> returnHtmlHeaderContent = "";

		$index = 0;
		$returnHtml = "";
		$strLength = 0;
		$headerEnd = 1;
		if(debug==1)
		{
			echo "\n读数据";
		}

		if(!feof($this->connect))
		{
			$this -> headerArray["request-line"] = (fgets($this -> connect, 128));
			$this -> returnHtmlHeaderContent .= $this -> headerArray["request-line"];
		}
		
		while (!feof($this->connect)) 
		{

			$status = stream_get_meta_data($this->connect);
			if($status['timed_out'])
			{
				$this -> errorMsg =  "操作超时";
				return false;
			}

			$returnTempHtml = (fgets($this->connect, 128));

			$returnHtml .= $returnTempHtml;
			if($returnHtml=="") break;

			if($headerEnd==1)
			{
				if(ord(substr($returnTempHtml,0,1))==13)
				{
					$headerEnd =0;
					continue;
				}
				$this -> returnHtmlHeaderContent .= $returnTempHtml;

				$ArrayTempObj = explode(":",$returnTempHtml);
				$this -> headerArray[$ArrayTempObj[0]] = $ArrayTempObj[1];

				#获取页面长度
				$search = strstr( strtoupper($returnTempHtml), strtoupper('Content-Length') );
				if( $search )
				{
					$TempArray = explode("\r\n",$search);
					$TempArray[0] = str_replace(strtoupper("Content-Length"),"",$TempArray[0]);
					$TempArray[0] = str_replace(":","",$TempArray[0]);
					$TempArray[0] = trim($TempArray[0]);
					if($this -> Connection != "Close")
						$strLength = (int)($TempArray[0]);
				}
			}else{
				$this -> returnHtmlContent .= $returnTempHtml;
			}
			
			# 如果获取页面长度后，拿到网页的数据超过该数字时自动停止
			if( $strLength !=0 )
			{
				if(strlen($this -> returnHtmlContent)>=$strLength)
				{
					break;
				}
			}
		}
		if(debug==1)
		{
			echo "\n读数据成功";
		}
		$this -> returnHtml = $this -> returnHtmlHeaderContent ."\r\n".$this -> returnHtmlContent;
		$this -> closeHost();
		return true;
	}
	public function buildFile($content,$path)
	{
		$filename = $path;
		$somecontent = $content;
		
		if (!$handle = fopen($filename, 'w')) 
		{
			return false;
		}

		// 将$somecontent写入到我们打开的文件中。
		if (fwrite($handle, $somecontent) === FALSE) 
		{
			fclose($handle);
			return false;
		}
		fclose($handle);
		return true;
	}
	public function errorOut($msgString)
	{
		echo $msgString;
		return;
	}

}
?>