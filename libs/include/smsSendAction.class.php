<?php

require_once(LIB_PATH."/include/curl.class.php");

class CSendSmsDB extends CHttpCurlDataDB
{
	#增加请勿回复前的备份
	public $returnMsg = "";
	function sendmsg($smsContent,$smsMobile)
	{
		#	江泽民，精装修，入住，户型
		$smsContent = preg_replace("/入住/", "入 住", $smsContent);
		$smsContent = preg_replace("/户型/", "户 型", $smsContent);
		$smsContent = preg_replace("/拨打/", "拨 打", $smsContent);
		$smsContent = preg_replace("/黄色/", "黄 色", $smsContent);
		$smsContent = preg_replace("/tom/", "t om", $smsContent);
		$smsContent = preg_replace("/游行/", "游 行", $smsContent);

		$smsMobile = preg_replace("/^(\+86)?0+/", "", $smsMobile);
		if (!preg_match("/^(\+86)?[0-9]{11}$/",$smsMobile)){
			$this -> returnMsg =  "\n[".$smsMobile."] ".$smsContent." 电话号码输入错误!";
			return false;
		}

		$url = smsUrl .
			"?method=Submit" .
			"&account=" . smsAccount .
			"&password=" . smsPasswd .
			"&mobile=" . $smsMobile .
			"&content=" . urlencode($smsContent);

		$this -> init($url);

		if(!$this -> sendHeader()){
			$this -> returnMsg = "\n[".$smsMobile."] ".$smsContent." 短信发送失败 无法连接服务端。";
			return false;
		}
		
		$urlContent = $this -> returnHtmlContent;
		preg_match_all ("|<code>(.*)</code>|U", $urlContent, $out, PREG_PATTERN_ORDER);
		$code = (int)($out[1][0]);

		preg_match_all ("|<msg>(.*)</msg>|U", $urlContent, $out, PREG_PATTERN_ORDER);
		$msg = trim($out[1][0]);

		switch( $code )
		{
			case 2:
				return true;
				break;
			default:
				break;
		}
		$this -> returnMsg = "\n[".$smsMobile."] ".$smsContent. " " . $code ." " . $msg;
		return false;
	}

}
