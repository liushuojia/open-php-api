<?php

class CHttpCurlDataDB
{
	public $headerMsg = array(
		"Referer" => "",
		"User-Agent" => "User-Agent:Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; GTB6; SLCC1; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.5.30729; .NET CLR 3.0.30618)",
		"Content-Type" => "application/x-www-form-urlencoded",		//传文件的时候， 要清掉这个数据
		"Accept-Type" => "*/*",
		"Accept-Encoding" => "",
		"Cache-Control" => "",
	);
	public $curl;							# 连接到服务器的变量
	public $Connection = "Close";			# http 1.1 下载文件或者其他请使用 close ， 特殊情况请使用 Keep-Alive，程序去自动截取，如果没有返回长度，有点慢

	public $postFileData = array();			# key => value	要上传的本地文件地址	"upload" => "@C:/wamp/www/test.zip"  @绝对路径
	public $postDataString = "";			# 如果该string存在则不考虑 postData		a=1&b=2;
	public $postData = array();				# key => value

	public $CookieDataString = "";			# 如果该string存在则不考虑 CookieData	e=5;f=6;
	public $CookieData = array();			# key => value 

	public $headerArray = array();			# 头文件信息
	public $errorMsg = "";					# 错误信息
	public $curlInfo;						# curl 的信息

	public $returnHtmlContent = "";			# 返回的信息 不包括Header信息 
	public $returnHtmlHeaderContent = "";	# 返回的信息 Header信息 


	public $safeCookie = "";				#保存cookie 绝对路径
	public $readCookie = "";				#读取cookie 绝对路径
	
	public $timeOut = 10;					# 超时时间
	public $header_show = 0;				# 是否显示头信息 1 显示  0 不显示
	public $returnTransfer = 1;				# 获取页面内容，不直接输出到页面，CURLOPT_RETURNTRANSFER参数设置
		
	#设置属性
	public $setCurloptArray = array(
		//CURLOPT_USERPWD => "hiloy:liushuojia",			# 超时时间
	
	);
	/*
			CURLOPT_USERPWD: 传递一个形如[username]:[password]风格的字符串,作用PHP去连接。 
			CURLOPT_PROXYUSERPWD: 传递一个形如[username]:[password] 格式的字符串去连接HTTP代理。 
			CURLOPT_RANGE: 传递一个你想指定的范围。它应该是'X-Y'格式，X或Y是被除外的。HTTP传送同样支持几个间隔，用逗句来分隔(X-Y,N-M)。 
			CURLOPT_REFERER: 在HTTP请求中包含一个'referer'头的字符串。 
			CURLOPT_USERAGENT: 在HTTP请求中包含一个'user-agent'头的字符串。 
			CURLOPT_FTPPORT: 传递一个包含被ftp 'POST'指令使用的IP地址。这个POST指令告诉远程服务器去连接我们指定的IP地址。 这个字符串可以是一个IP地址，一个主机名，一个网络界面名(在UNIX下)，或是‘-'(使用系统默认IP地址)。 
			CURLOPT_COOKIE: 传递一个包含HTTP cookie的头连接。 
			CURLOPT_SSLCERT: 传递一个包含PEM格式证书的字符串。 
			CURLOPT_SSLCERTPASSWD: 传递一个包含使用CURLOPT_SSLCERT证书必需的密码。 
			CURLOPT_COOKIEFILE: 传递一个包含cookie数据的文件的名字的字符串。这个cookie文件可以是Netscape格式，或是堆存在文件中的HTTP风格的头。 
			CURLOPT_CUSTOMREQUEST: 当进行HTTP请求时，传递一个字符被GET或HEAD使用。为进行DELETE或其它操作是有益的，更Pass a string to be used instead of GET or HEAD when doing an HTTP request. This is useful for doing or another, more obscure, HTTP request. 
			CURLOPT_USERPWD
	*/

	function __construct()
	{
		#每个类初始化的时候执行的咚咚
		return;
	}

	public function init( $url )
	{
		$this -> curl = @curl_init();
		if( $this -> curl == false ) 
		{
			$this -> errorMsg =  "初始化curl出现错误，php编译必须包含curl";
			return false;
		}

		# 设置 url
		curl_setopt( $this -> curl, CURLOPT_URL, $url );

		# 设置超时时间
		curl_setopt( $this -> curl,	CURLOPT_TIMEOUT, $this -> timeOut );

		return true;;
	}
	public function closeHost()
	{
		@curl_close($this -> curl);
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

		if( !$this -> curl )
		{
			$this -> errorMsg =  "请初始化curl变量失败";
			return false;
		}

		# 设置属性
		foreach( $this -> setCurloptArray as $key => $val )
		{
			curl_setopt( $this -> curl,	$key, $val );
		}

		#文件cookie
		if( $this -> safeCookie !="" )
		{
			//设置Cookie信息保存在指定的文件中 
			curl_setopt($this -> curl, CURLOPT_COOKIEJAR, $this -> safeCookie);
		}
		
		if( $this -> readCookie !="" )
		{
			//读取cookie 
			curl_setopt($this -> curl, CURLOPT_COOKIEFILE, $this -> readCookie);
		}

		#-----------------------------
		# POST 数据初始化
		//文件.
		if( count($this -> postFileData)>0 )
		{	
			$PostData = array();
			foreach( $this -> postData as $key => $val )
			{
				if( $key=="" )
					continue;

				$PostData[ urlencode($key) ] = urlencode($val);
			}

			//文件
			foreach( $this -> postFileData as $key => $val )
			{
				$PostData[ urlencode($key) ] = $val;
			}

			#传文件必须用数组
			$this -> headerMsg["Content-Type"] = "";
			curl_setopt( $this -> curl, CURLOPT_POST, 1 );
			curl_setopt( $this -> curl, CURLOPT_POSTFIELDS, $PostData );
		}else{
			#其他post数据
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
			if( $PostDataString!="" )
			{
				curl_setopt( $this -> curl, CURLOPT_POST, 1 );
				curl_setopt( $this -> curl, CURLOPT_POSTFIELDS, $PostDataString );
				$headerArray[] = "Content-Length:".strlen($PostDataString);
			}
		}
		#-----------------------------

		# 设置浏览器的特定header
		$headerArray = array();
		$headerArray[] = "Connection: ".$this -> Connection;
		foreach($this -> headerMsg as $key => $value)
		{
			if($value!="") 
			{
				$headerArray[] = $key.": ".$value;
			}
		}

		# 头部发送cookie
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
			if($CookieString!="") 
			{
				$headerArray[] = $CookieString;
			}
		}else{
			$headerArray[] .= "Cookie: ".$this -> CookieDataString;	
		}


		if( count($headerArray)>0 )
		{
			curl_setopt($this -> curl, CURLOPT_HTTPHEADER, $headerArray);
		}


		#是否显示头信息 1 显示  0 不显示
		curl_setopt($this -> curl, CURLOPT_HEADER, $this -> header_show);

		#返回结果
		curl_setopt($this -> curl, CURLOPT_RETURNTRANSFER, $this -> returnTransfer);
	    
		$output = curl_exec($this -> curl);

		if ($output === false) {
			$this -> errorMsg = "CURL Error: " . curl_error($this -> curl);
			return false;
		}

		$this -> curlInfo = curl_getinfo($this -> curl);

		if( $this -> header_show==1 )
		{
			//	1 显示
			$tempArray = explode("\n\r",$output);
			$this -> returnHtmlHeaderContent = strstr($output, "\n\r",true);
			$this -> returnHtmlContent = strstr($output, "\n\r");

			if( strstr($this -> returnHtmlHeaderContent,"Content-Encoding: gzip")!==false )
			{
				$this -> returnHtmlContent = substr($this -> returnHtmlContent,3);
				$this -> returnHtmlContent = gzdecode($this -> returnHtmlContent);
			}
		}else{
			//	0 不显示
			$this -> returnHtmlHeaderContent = "";
			$this -> returnHtmlContent = $output;
		}
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

