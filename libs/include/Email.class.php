<?php
require(MAIL_LIB."/class.phpmailer.php");

class Email
{

	public $AddAddress = array();			//收件人地址(名称)
											//类似 $AddAddress[0]=array("Email"=>"liushuojia@126.com","Ename"=>"hiloy")
	public $cc = array();					//抄送地址(名称)
											//类似 $AddAttachment[0]=array("path"=>"/tmp/image.jpg","name"=>"image.jpg")
	public $bcc = array();					//暗抄送地址(名称)
											//类似 $AddAttachment[0]=array("path"=>"/tmp/image.jpg","name"=>"image.jpg")

	public $AddAttachment = array();		//附件地址(名称)
											//类似 $AddAttachment[0]=array("path"=>"/tmp/image.jpg","name"=>"image.jpg")



	public $Subject;						//email标题
	public $Body;							//html内容
	public $AltBody;						//若客户端浏览不支持html，则显示这个文本信息
}

class EmailDB
{
	public static function SentEmail($TEmail)
	{

		$mail = new PHPMailer();
		$mail -> IsSMTP();										// telling the class to use SMTP
		$mail -> Host		=	EmailHost;						// SMTP server
		$mail -> SMTPDebug	=	0;								// enables SMTP debug information (for testing)
																// 1 = errors and messages
																// 2 = messages only

		$mail -> SMTPAuth	=	true;							// enable SMTP authentication
		$mail -> Host		=	EmailHost;						// sets the SMTP server
		$mail -> Port		=	465;							// set the SMTP port for the GMAIL server
		$mail -> Username	=	EmailUsername;					// SMTP account username
		$mail -> Password	=	EmailPassword;					// SMTP account password
		$mail -> CharSet	=	"utf-8";
		$mail -> SMTPSecure	=	"ssl";

		$mail -> SetFrom( EmailUsername, FromName );
		$mail -> AddReplyTo( EmailUsername, FromName );

		$mail -> AddReplyTo( "hiloy@landtu.com", FromName );

		//添加收件人
		foreach($TEmail->AddAddress as $Address)
		{
			$mail -> AddAddress($Address["Email"], $Address["Ename"]);	
		}

		//抄送地址
		foreach($TEmail->cc as $Address)
		{
			$mail -> addCC($Address["Email"], $Address["Ename"]);	
		}

		//暗抄送地址
		foreach($TEmail->bcc as $Address)
		{
			$mail -> addBCC($Address["Email"], $Address["Ename"]);	
		}
		//类似 $AddAttachment[0]=array("path"=>"/tmp/image.jpg","name"=>"image.jpg")

		//添加附件
		foreach($TEmail->AddAttachment as $Attachment)
		{
			if($Attachment["path"]!='')
			{
				if($Attachment["name"]=='')
				{
					preg_match('/\/([^\/]*?)$/',$Attachment["path"],$tempArr);
					$Attachment["name"] = $tempArr[1];
				}
				$Attachment["name"] = "=?UTF-8?B?" . base64_encode($Attachment["name"]) . "?=";
				$mail -> AddAttachment($Attachment["path"], $Attachment["name"]);	
			}
		}


		//$mail -> IsHTML( $TEmail -> IsHTML );			//是否使用html标识 

		$mail -> Subject	=	$TEmail -> Subject;
		$mail -> AltBody	=	$TEmail -> AltBody;
		$mail -> MsgHTML($TEmail -> Body);

		return($mail->Send());
	}
}
