<?php 
/* 
to:  邮件接收地址 
subject： 邮件主题 
body： 邮件内容 
attachpath：附件地址 
cc： 邮件抄送地址 
bcc： 邮件暗抄送地址 
*/
function send_mail($to, $subject="", $body="", $attachpath="", $cc="", $bcc="") 
{ 
 // 对邮件内容进行必要的过滤 
 $body = eregi_replace("[\]",'',$body);  
  
 // 设定时区 
 date_default_timezone_set("PRC"); 
  
 require_once('class.phpmailer.php'); 
 require_once("class.smtp.php"); 
  
 // 实例化PHPMailer对象 
 $mail = new PHPMailer();     
  
 // 设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置为 UTF-8 
 $mail->CharSet ="UTF-8"; 
  
 // 设定使用SMTP服务 
 $mail->IsSMTP(); 
  
 // 启用 SMTP 验证功能 
 $mail->SMTPAuth = true; 
  
 // SMTP 安全协议 
 $mail->SMTPSecure = "ssl"; 
  
 // SMTP 服务器 
 $mail->Host = "smtp.qq.com"; 
  
 // SMTP服务器的端口号 
 $mail->Port = 465; 
  
 // SMTP服务器用户名和密码 
 $mail->Username = "xxxxxx@qq.com";  
 $mail->Password = "xxxxxx";   
  
 // 设置发件人地址和名称，名称可有可无 
 $mail->SetFrom("xxxxxx@qq.com", "xxxxxx"); 
  
 // 设置邮件接收地址和名称，第二个参数无所谓。必须用AddAddress添加邮件接收地址。AddReplyTo方法没什么用。 
 //$mail->AddReplyTo("xxxxxx@163.com", "xxxxxx"); 
 $mailaddrs = split(",", $to); 
 foreach ($mailaddrs as $addres) 
 { 
 //校验邮箱地址是否合法 
 if (filter_var($addres, FILTER_VALIDATE_EMAIL)) 
 { 
  $mail->AddAddress($addres);  
 } 
 } 
  
 // 设置邮件抄送地址 
 if ($cc != "") 
 { 
 $ccaddrs = split(",", $cc); 
 foreach ($ccaddrs as $ccaddr) 
 { 
  //校验邮箱地址是否合法 
  if (filter_var($ccaddr, FILTER_VALIDATE_EMAIL)) 
  { 
  $mail->addCC($ccaddr);  
  } 
 } 
 } 
  
 // 设置邮件暗抄送地址,私密发送 
 if ($bcc != "") 
 { 
 $bccaddrs = split(",", $bcc); 
 foreach ($bccaddrs as $bccaddr) 
 { 
  //校验邮箱地址是否合法 
  if (filter_var($bccaddr, FILTER_VALIDATE_EMAIL)) 
  { 
  $mail->addBCC($bccaddr);  
  } 
 } 
 } 
  
 // 设置邮件主题 
 $mail->Subject = $subject; 
  
 // 可选项，向下兼容考虑 
 $mail->AltBody = "为了查看该邮件，请切换到支持 HTML 的邮件客户端"; 
  
 // 设置邮件内容 
 $mail->MsgHTML($body);     
  
 //使用HTML格式发送邮件 
 $mail->IsHTML(true); 
  
 // 添加附件,第一个参数是附件地址，第二个参数附件名 
 //$mail->AddAttachment("images/phpmailer.gif"); 
 $mail->AddAttachment($attachpath); 
  
 // 发送邮件 
 if(!$mail->Send()) 
 { 
 echo "发送失败：" . $mail->ErrorInfo . PHP_EOL; 
 } 
 else
 { 
 echo "恭喜，邮件发送成功！" . PHP_EOL; 
 } 
} 
$emailAddr = "xxxxxx@163.com,xxxxxx@qq.com,"; 
send_mail($emailAddr, "测试邮件", "<h1>使用PHPMailer类发送的邮件。</h1>", "mail/20170216.gif", "xxxxxx@qq.com", ""); 
?>