<?php


$WEB_HOME = strstr( dirname(__FILE__), "/redis/", true );

require_once($WEB_HOME . "/redis/include/config.php");
require_once(REDIS_PATH . "/include/RedisApp.class.php");
require_once(REDIS_PATH . "/email/email-config.php");
require_once(MAIL_LIB . "/class.phpmailer.php");

/*
	可能存在跨服务器的 这里的附件应该如何处理呢

*/
class emailActionApp extends RedisApp
{
	public function getFileName( $path ){
		while (true) {
			# code...
			$fileName = md5( rand(1,10000) . "-" . $path );
			if( !file_exists($fileName) ){
				return $fileName;
			}
		}
		return;
	}

	public function getFileData( $path ){
		$tmp = strtolower($path);
		if( 
			substr($tmp, 0, strlen("http://")) == "http://" 
			|| substr($tmp, 0, strlen("https://")) == "https://" 
		){
			//网络文件
			$HttpCurlDataDB = new CHttpCurlDataDB();
			$HttpCurlDataDB -> init( $path );
			if( $HttpCurlDataDB -> sendHeader() ){
				$tmpFile = mailTmpPAth . "/" . $this -> getFileName($path);
				if( $HttpCurlDataDB -> ContentSafeToFlie($tmpFile) ){
					return $tmpFile;
				}
			}
		}else{
			//本地文件
			if( file_exists($path) ){
				$tmpFile = mailTmpPAth . "/" . $this -> getFileName($path);
				if( copy( $path, $tmpFile ) )
					return $tmpFile;
			}
		}
		return false;
	}
	public function RunApp()
	{

		//订阅发送邮件队列
		$redisDB = new TRedisDB;

		while( $redisDB -> lSize(mailKey) > 0 ){
			$mailContent = $redisDB -> lPop(mailKey);
			if( $mailContent !== false && is_array($mailContent) && count($mailContent)>0 ){

				$email = new Email();
				$email -> AddAddress = $mailContent["AddAddress"];
				$email -> cc = $mailContent["cc"];
				$email -> bcc = $mailContent["bcc"];
				$email -> AddAttachment = $mailContent["AddAttachment"];
				$email -> Subject = trim($mailContent["Subject"]);
				$email -> Body = trim($mailContent["Body"]);
				$email -> AltBody = trim($mailContent["AltBody"]);

				if( !is_array($email -> AddAddress) || count($email -> AddAddress)<=0 )
					continue;
				
				if( !is_array($email -> cc) || count($email -> cc)<=0 ){
					$email -> cc = array();
				}
				if( !is_array($email -> bcc) || count($email -> bcc)<=0 ){
					$email -> bcc = array();
				}


				if( !is_array($email -> AddAttachment) || count($email -> AddAttachment)<=0 ){
					$email -> AddAttachment = array();
				}
				foreach( $email -> AddAttachment as $key => $AddAttachment ){
					if($AddAttachment["name"]==''){
						preg_match('/\/([^\/]*?)$/',$AddAttachment["path"],$tempArr);
						$email -> AddAttachment[$key]["name"] = $tempArr[1];
					}
					if( ($path = $this -> getFileData($AddAttachment["path"])) === false ){
						unset($email -> AddAttachment[$key]);
						continue;
					}
					$email -> AddAttachment[$key]["path"] = $path;
				}

				if( $email -> Subject=="" )
					continue;

				EmailDB::SentEmail($email);

				//邮件发送完毕, 清理临时文件
				foreach( $email -> AddAttachment as $key => $AddAttachment ){
					unlink($AddAttachment["path"]);
				}
			}

		}
		return;
	}
}

$App = new emailActionApp();
$App -> RunApp();
