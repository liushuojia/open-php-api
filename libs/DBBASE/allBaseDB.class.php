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

$filePath = PHP_CLASS_PATH . "/include_all_class.php";
include_once $filePath;

	/*
	$dir = PHP_CLASS_PATH;
	if(@$handle = opendir($dir))
	{
		while(($file = readdir($handle)) !== false) {
			if($file != ".." && $file != ".") {
				if(is_dir($dir."/".$file)) {
					//数据库底下的类
					if( @$handleFile = opendir($dir."/".$file) ){
						while(($fileSec = readdir($handleFile)) !== false) {
							if($fileSec != ".." && $fileSec != ".") {
								if( preg_match("/\w\.class\.php$/",$fileSec) == true){

									preg_match("/(\w+)\.class\.php$/", $fileSec,$pregArray);
									$fileName = $pregArray[1];


									$DBFILEARRAY["\\" . $file . "\\" . $fileName] = array(
										"fileName" => $fileName,
										"fileNameLink" => $fileSec,
										"fileDir" => $file,
										"filePath" => $dir."/".$file."/".$fileSec,
										"className" => "\\" . $file . "\\C" . $fileName,
										"classNameDB" => "\\" . $file . "\\C" . $fileName . "DB",
									);
								}
							}
						}
					}
				}
			}
		}
		closedir($handle);

		foreach( $DBFILEARRAY as $className => $classMsg)
		{
			include_once $classMsg["filePath"];
		}
	}
	*/
