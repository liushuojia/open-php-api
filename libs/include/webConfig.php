<?php

#时区设置
date_default_timezone_set('PRC');
header("content-type:text/html; charset=utf-8");

if( LandTuDebug==1 )
{
	@ini_set("display_errors","On");
	error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | !E_WARNING | E_PARSE | !E_NOTICE);
}else{
	@ini_set("display_errors","Off");
	error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | !E_WARNING | E_PARSE | !E_NOTICE);
}

# 网站目录
define("LIB_PATH", HOME."/libs");
define("OP_PATH", HOME."/html");
define("OPEN_PATH", HOME."/open");
define("SHARE_PATH", HOME."/share");
define("PHP_CLASS_PATH", HOME."/share/php/class");
define("REDIS_PATH", HOME."/redis");
define("ACTIONMQ_PATH", HOME."/actionmq");


#图片路径
define("photoPath", SHARE_PATH."/file");


#开源文件目录
//请根据服务器进行调整
define("OPENLIB_PATH", "/home/liushuojia/open_lib");
define("MAIL_LIB",OPENLIB_PATH . "/PHPMailer_v5.1");
define("WEIXIN_PATH",OPENLIB_PATH . "/weixin");
define("SMARTY_LIB",OPENLIB_PATH . "/Smarty-3.1.14/libs");


# smarty cache 时间
if( LandTuDebug!=1 )
{
	define("SmartyCacheTimeList",24*60*60);			//列表页面cache时间
	define("login_ext_time", 24*60*60);				//登录的验证有效期 30min
	define("api_get_ext_time", 24*60*60);			//登录后验证有效期 60min
}else{
	define("SmartyCacheTimeList",60*60);			//列表页面cache时间
	define("login_ext_time", 30*60);				//登录的验证有效期 30min
	define("api_get_ext_time", 1*60*60);			//登录后验证有效期 60min
}

if( LandTuDebug==1 )
{
	# 数据库的链接方式
	define("MysqlHost","192.168.1.30");			#服务器读的服务器	//define("MysqlHost","192.168.1.31");
	define("MysqlEditHost","192.168.1.30");		#数据库写的服务器

	define("MAINDBData","TUserDB");					#数据库名称

	define("MysqlUser","root");					#用户
	define("MysqlPasswd","liushuojia");			#密码

}else{
	# 数据库的链接方式
	define("MysqlHost","127.0.0.1");			#服务器读的服务器	//define("MysqlHost","192.168.1.31");
	define("MysqlEditHost","127.0.0.1");		#数据库写的服务器

	define("MAINDBData","TUserDB");					#数据库名称

	define("MysqlUser","root");					#用户
	define("MysqlPasswd","liushuojia");			#密码
}


# 域名
define("domain_name","liushuojia.com");


#cookie 记录时间长度 1年
define("cookieTime",60*60*24*30*12 );


#附件保存命名文件夹
define("commonPath",date("Y")."/".date("m")."/".date("d")."/");


#定义一个空类
class emptyObj{}

#smarty 不缓存的位置
function smarty_block_dynamic($param, $content, &$smarty)
{
	return $content;
}
	
#数据库基类
require_once(LIB_PATH."/DBBASE/DatabaseDB.class.php");
require_once(LIB_PATH."/DBBASE/define.php");

#包含所有的数据库基础类
require_once(LIB_PATH."/DBBASE/allBaseDB.class.php");


#显示数据基类
require_once(LIB_PATH."/include/ShowApp.class.php");

#网站用户
if( LandTuDebug==1 )
{
	define("webUser","nginx");		//列表页面cache时间
}else{
	define("webUser","daemon");	//列表页面cache时间
}

require_once(LIB_PATH."/include/PublicFunction.php");
require_once(LIB_PATH."/include/curl.class.php");
require_once(LIB_PATH."/include/convert.class.php");

//API_KEYWORD
define("API_KEYWORD","sadfasdfj1212sadlfksajdsadfasd;flkfhsqeuyioweorweyui");

#微信
define("WEIXINID", "2");				//  默认微信id

if( LandTuDebug==1 )
{
	#open url
	define("OP_URL", "http://html.liushuojia.com");
	define("API_URL", "http://api.liushuojia.com");
}else{
	#open url
	define("OP_URL", "http://html.liushuojia.com");
	define("API_URL", "http://api.liushuojia.com");
}

# 邮件账户
define("EmailHost","smtp.qq.com");
define("EmailUsername","liushuojia@qq.com");
define("EmailPassword","urwsifgzssbcbhfd");
define("FromName","刘硕嘉");
require_once(LIB_PATH."/include/Email.class.php");


#短信的端口	互亿无线定义		3
define("smsAccount", "cf_landtu");									# 登录用户名
define("smsPasswd", "landtu123");									# 登录密码
define("smsUrl", "http://106.ihuyi.cn/webservice/sms.php");			# 发送短信的接口地址
require_once(LIB_PATH."/include/smsSendAction.class.php");


#redis 存储地址
define("redisHost", "127.0.0.1");							# 登录用户名
define("redisPort", "6379");								# 登录密码
define("redisPasswd", "liushuojia");						# 发送短信的接口地址
require_once(LIB_PATH."/include/redis.class.php");


#redis 异步存储的队列key

//邮件
define("redisMailQueue","MailQueue");

//短信
define("redisSmsQueue","SmsQueue");	    //发送队列
define("redisSmsCheckKey","SmsCheck");	//验证码验证key

//微信
define("redisWeixinQueue","WeixinQueue");   //微信发送信息队列
define("redisAdminPrefix","admin_id_");     //用户数据前缀
define("redisWeixinLoginPrefix","wx_login_");     //用户微信登录前缀

