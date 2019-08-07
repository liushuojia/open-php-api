<?php
	/*
		*公共函数库
	*/

	/**
		*function: CheckMobile 校验手机号码
		* @param: $mobile_num
		* result: false 不合法; true合法
	*/	
	function CheckMobile($mobile_num)
	{
		return preg_match("/^[0]?1[0-9]{10}$/", $mobile_num);
	}

	/*
		format time
	*/	
	function formatTime($second, $type=0)
	{
		if($second==0)
			return "";
		
		$absFlag = ($second<0?"－":"");

		$second = abs($second);
		if($type==0){
			$d = (int)($second/60/60/24);
			$h = (int)($second/60/60)%24;
		}else{
			$d = 0;
			$h = (int)($second/60/60);
		}
		$m = (int)($second/60)%60;
		$s = ($second%60);

		$returnString = "";
		if($d!=0){
			$returnString .= $d."天 ";
		}
		if($h!=0){
			$returnString .= (strlen($h)<2?"0":"").$h.":";
		}
		$returnString .= (strlen($m)<2?"0":"").$m.":";
		$returnString .= (strlen($s)<2?"0":"").$s;

		return $absFlag.$returnString;
	}


function formatSecond($second)
{
	if( $second<1 )
		return ceil($second*1000) . "ms";

	if($second==0)
		return "0秒";

	$absFlag = ($second<0?"－":"");

	$second = abs($second);
	$d = (int)($second/60/60/24);
	$h = (int)($second/60/60)%24;
	$m = (int)($second/60)%60;
	$s = ($second%60);

	$returnString = "";
	if($d>0){
		$returnString .= $d."天 ";
	}
	if($h>0){
		$returnString .= $h."小时";
	}
	if($m>0){
		$returnString .= $m."分钟";
	}
	if($returnString=="")
		$returnString .= $s."s";
	else
		$returnString .= $s."秒";

	return $absFlag.$returnString;
}



/**
		*function: CheckEmail 校验 Email
		* @param: $email
		* result: false 不合法; true合法
	*/	
	function CheckEmail($email)
	{		
		return preg_match("/^[_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,3}$/i", $email);
	}


	/*
		随机函数
			type  0  字符串
				  1  数字
			
			len   长度
	*/
	function random($len,$type=0)
	{
		switch($type){
			case 1:
				$srcstr="0123456789";break;
			case 2:
				$srcstr="abcdefghijklmnpqrstuvwxyz";break;
			default:
				$srcstr="ABCDEFGHIJKLMNPQRSTUVWXYZ123456789abcdefghijklmnpqrstuvwxyz";break;
		}
		mt_srand();
		$strs="";
		for($i=0;$i<$len;$i++)
		{
			$strs.=substr($srcstr,mt_rand(0,strlen($srcstr)-1),1);
		}
		return ($strs);
	}

	//计算字符串长度 一个中文字算两个
	function strlenCount($str, $code_type = "utf-8")
	{
		$searchString = "ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789abcdefghijklmnpqrstuvwxyz~!@#$%^&*()_+`-|\\<>?,./";
		$tempString = $str;
		$thisLength = 0;
		while( mb_strlen($tempString,$code_type)>0 )
		{
			$thisString = mb_substr( $tempString,0,1,$code_type );
			if( strstr($searchString,$thisString)!==false)
			{
				$thisLength += 1;
			}else{
				$thisLength += 2;
			}
			$tempString = mb_substr( $tempString,1,mb_strlen($tempString,$code_type),$code_type );
	
		}

		return $thisLength;
	}

	function strCut($str, $length,$code_type = "utf-8")
	{
		if( strlenCount($str,$code_type) < $length)
		{
			return $str;
		}

		$searchString = "ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789abcdefghijklmnpqrstuvwxyz~!@#$%^&*()_+`-|\\<>?,./";

		$index = 0;
		$thisLength = 0;
		$tempString = "";
		while( $thisLength < $length )
		{
			$thisString = mb_substr($str,$index,1,$code_type );
			if( $searchString=="" )
				break;

			if( strstr($searchString,$thisString)!==false)
			{
				 $thisLength += 1;
			}else{
				 $thisLength += 2;
			}

			$tempString .= $thisString;

			$index++;
		}

		return $tempString;
	}


	//判断奇数，是返回TRUE，否返回FALSE
	function is_odd($num)
	{
		return (is_numeric($num)&($num&1));
	}

	//判断偶数，是返回TRUE，否返回FALSE
	function is_even($num)
	{
		return (is_numeric($num)&(!($num&1)));
	}


	// date("w") 星期中的第几天，数字表示 0（表示星期天）到 6（表示星期六） 
	function weekdayName( $date, $type=0 )
	{
		switch( $type )
		{
			case 1:
				$weekNameArray = array(
					0 => "星期日",
					1 => "星期一",
					2 => "星期二",
					3 => "星期三",
					4 => "星期四",
					5 => "星期五",
					6 => "星期六",
				);
				break;			
			case 0:
			default:
				$weekNameArray = array(
					0 => "周日",
					1 => "周一",
					2 => "周二",
					3 => "周三",
					4 => "周四",
					5 => "周五",
					6 => "周六",
				);
				break;
		
		}
		$num = date("w",strtotime($date));
		return $weekNameArray[$num];
	}

	/*
		#来源IP
	*/
	function GetIp()
	{
		$onlineip = "";
		if($_SERVER['HTTP_CLIENT_IP'])
		{  
			$onlineip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif($_SERVER['HTTP_X_FORWARDED_FOR']){  
			$onlineip=$_SERVER['HTTP_X_FORWARDED_FOR']; 
		}else{
			$onlineip=$_SERVER['REMOTE_ADDR'];
		} 
		return $onlineip;
	}

	/*
		去除html标签
	*/
	function DelHtml($str,$CutLen=0,$type=0)
	{
		$str = preg_replace("/'/", "", $str);
		$str = preg_replace("/\"/", "", $str);
		$str = preg_replace("/<\/[^>]+>/", "", $str);
		$str = preg_replace("/<[^>]+>/", "", $str);
		if($type==0)
		{
			$str = preg_replace("/[\n]+/", "", $str);
			$str = preg_replace("/\r/", "", $str);
		}
		$str = preg_replace("/&nbsp;/i", "", $str);
		$str = preg_replace("/ +/", " ", $str);
		$str = trim($str);

		if($CutLen>0)
			$str = mb_substr($str,0,$CutLen,"UTF-8");

		return $str;
	}


	/*
		文件大小 字节转化为KB、MB
	*/

	function FormatFileSize($size,$n=1)
	{
		$tempZ = 0;
		if($size>1024 && $size<1024*1024){
			$tempZ = round($size/1024,$n)."KB";
			
		}elseif($size>=1024*1024){
			$tempZ =  round($size/1024/1024,$n)."MB";
		}else{
			$tempZ = $size."B";
		}
		return $tempZ;
	}

	/***通过经纬度计算两点间的距离***/
	function fn_rad($d)  
	{  
		return $d * pi() / 180.0;  
	}  
	
	function GetP2PDistance($lat1,$lng1,$lat2,$lng2){  
	// 纬度1,经度1 ~ 纬度2,经度2 
		$EARTH_RADIUS = 6378.137 * 1000;  
		$radLat1 = fn_rad($lat1);  
		$radLat2 = fn_rad($lat2);  
		$a = $radLat1 - $radLat2;  
		$b = fn_rad($lng1) - fn_rad($lng2);  
		$s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));  
		$s = $s * $EARTH_RADIUS;  
		#返回浮点数 单位（米）
		return $s;				
	#	$s = round($s * 10000) / 10000; 
	#	return number_format($s,2);  
	}  


	#重新排列数组， 随即排列
	function resetArray(&$resetArray)
	{  
		$tempArray = $resetArray;
		$tempForArray = array();
		foreach( $tempArray as  $tempData )
		{
			while(1)
			{
				$key = rand( 10000000 , 99999999 );
				if( !array_key_exists($key, $tempForArray) )
					break;			
			}
			$tempForArray[$key] = $tempData;		
		}
		krsort($tempForArray);
		$resetArray = $tempForArray;
		return true;
	}  

	#create by carl 12-12-11
	#判断是否为utf-8编码
	function is_utf8($string) 
	{ 
		if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$string) == true ||		preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$string) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$string) == true) 
		{ 
			return true; 
		} 
		else 
		{ 
			return false; 
		} 
	}
	function arrayToString( $array )
	{
		$string = "";
		foreach( $array as $key => $val )
		{
			$string .= ($string!=""?"&":"") . $key . "=" . $val;
		}
		return $string;
	}

	function encryptMD5Key( $array )
	{
		ksort($array);
		$string = "";
		foreach( $array as $key => $val )
		{
			$string .= ($string!=""?"&":"") . $key . "=" . $val;
		}
  //      echo $string;
		$string = strtoupper(md5($string));
		return $string;
	}

	function downloadFile($path , $filename)
	{
		$file_name = $path . "/" . $filename;
		if(!file_exists($file_name)) {   
			echo "文件不存在！";
			return false;
		} else {  
			$file = fopen($file_name,"r"); // 打开文件
			$file_size = filesize($file_name);
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".$file_size);
			Header("Content-Disposition: attachment; filename=" . $filename);
			// 输出文件内容
			$buffer=1024; 
			$file_count=0; 
			//向浏览器返回数据 
			while(!feof($file) && $file_count<$file_size){ 
				$file_con=fread($file,$buffer); 
				$file_count+=$buffer; 
				echo $file_con; 
			}  
			fclose($file);
			exit();
		}
	}

	function createDir($filePath){
		if(!is_dir($filePath)) {
			if(!mkdir($filePath)) {
				return false;
			}
		}
		return true;
	}


	/*
	*上传图片
	*/
	function uploadDirCreate( &$safePath ="", &$webPath = "" )
	{
		if(!createDir(photoPath)) {
			return false;
		}

		$yearPath = photoPath . "/" . date("Y");
		if(!createDir($yearPath))
			return false;

		@chown($yearPath,webUser);

		$monthPath = photoPath."/" . date("Y")."/".date("m");
		if(!createDir($monthPath))
			return false;

		@chown($monthPath,webUser);

		$dayPath = photoPath."/" .date("Y")."/".date("m")."/".date("d");
		if(!createDir($dayPath))
			return false;

		@chown($dayPath,webUser);

		$safePath = $dayPath;
		$webPath = "/file/".date("Y")."/".date("m")."/".date("d");

		return true;
	}
	
	/*
	*function  OutputCsv 页面输出csv
	*param  fileName 文件名
	*param  ExcelString 输出字符串
	*/
	function OutputCsv( $fileName = '' , $ExcelString = '' )
	{
		if(!$fileName) $fileName = date("YmdHis").rand(1,9999).".csv";

		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: inline; filename = ".$fileName);
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		header('Pragma: public');  
		echo $ExcelString;
	}


	/*
	数组 对象 转换
	*/
	function array_to_object($arr) {
		if (gettype($arr) != 'array') {
			return;
		}
		foreach ($arr as $k => $v) {
			if (gettype($v) == 'array' || getType($v) == 'object') {
				$arr[$k] = (object)array_to_object($v);
			}
		}
	 
		return (object)$arr;
	}

	function object_to_array($obj) {
		$obj = (array)$obj;
		foreach ($obj as $k => $v) {
			if (gettype($v) == 'resource') {
				return;
			}
			if (gettype($v) == 'object' || gettype($v) == 'array') {
				$obj[$k] = (array)object_to_array($v);
			}
		}
		return $obj;
	}

	function trimall($str)//删除空格
	{
		$qian=array(" ","　","\t","\n","\r","	");
		$hou=array("","","","","","");
		return str_replace($qian,$hou,$str);
	}

	function createDiv( $AbsPath )
	{
		if(!is_dir($AbsPath))
		{
			mkdir($AbsPath);
		}
		@chown($AbsPath,webUser);
		//@chmod($AbsPath,"750");
		return;
	}

	function ChangePermissions( $AbsPath )
	{
		@chown($AbsPath,webUser);
		//@chmod($AbsPath,"750");
		return;
	}

	function deletedir($path){
		//如果是目录则继续
		if(is_dir($path))
		{
			if( substr($path,-1)!="/")
				$path .= "/";

			if ( $handle = opendir( $path) )
			{
				while (false !== ($file = readdir($handle))) {
					if ($file !== "." && $file !== "..") {	  //排除当前目录与父级目录
						$file = $path . $file;

						if (is_dir($file)) {
							deletedir($file);
						} else {
							@unlink($file);
						}
					}
				}
			}
			@rmdir($path);
			exit();
		}else{
			@unlink($path);
		}
	}

	//去掉' "
	function cleansc( &$array ){
		foreach ( $array as $key => &$val){
			if( is_array($val) ){
				cleansc($val);
			}else{
				$val = str_replace("'","", $val);
				$val = str_replace("\"","", $val);
				$val = trim($val);
			}
		}
		return;
	}

function getFileType($fileName)
{
	if (function_exists("finfo_open")) {
		$handle = finfo_open(FILEINFO_MIME_TYPE);
		$fileType = finfo_file($handle, $fileName);// Return information about a file
		finfo_close($handle);
	} else {
		//TODO:: 若没有启用扩展 fileinfo 采用此方式获取类型，待完善
		$file = fopen($fileName, 'rb');
		$bin = fread($file, 2); //只读2字节
		fclose($file);
		$strInfo = @unpack('C2chars', $bin);
		$typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
		switch ($typeCode) {
			case 255216:
				$fileType = 'image/jpeg';
				break;
			case 7173:
				$fileType = 'image/gif';
				break;
			case 13780:
				$fileType = 'image/png';
				break;
			default:
				$fileType = "application/octet-stream";
		}
		//Fix
		if ($strInfo['chars1'] == '-1' && $strInfo['chars2'] == '-40') {
			return 'image/jpeg';
		}
		if ($strInfo['chars1'] == '-119' && $strInfo['chars2'] == '80') {
			return 'image/png';
		}
	}

	return $fileType;
}

/**
 * 十进制数转换成62进制
 *
 * @param integer $num
 * @return string
 */
function from10_to62($num) {
    $to = 62;
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ret = '';
    do {
        $ret = $dict[bcmod($num, $to)] . $ret;
        $num = bcdiv($num, $to);
    } while ($num > 0);
    return $ret;
}

/**
 * 62进制数转换成十进制数
 *
 * @param string $num
 * @return string
 */
function from62_to10($num) {
    $from = 62;
    $num = strval($num);
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($num);
    $dec = 0;
    for($i = 0; $i < $len; $i++) {
        $pos = strpos($dict, $num[$i]);
        $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
    }
    return $dec;
}
