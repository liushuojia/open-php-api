<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/10/22
 * Time: 00:26
 */
// 基础数据类信息

$tmp = explode("/",$_SERVER["SCRIPT_FILENAME"] );

if( 
	$tmp[ count($tmp) - 1] == 'mysql-class-file.php'
	|| $tmp[ count($tmp) - 1] == 'install.php'
){
	//脚本执行重新构建类文件
	return;
}


if(!is_array($DBFILEARRAY))
	$DBFILEARRAY = array();

//  一个组件一个数据库
//  包含这个工作目录的所有方法
//
$filePath = PHP_CLASS_PATH . "/". $MysqlDefine["MysqlDatabase"] . "/include_all_class.php";
include_once $filePath;
