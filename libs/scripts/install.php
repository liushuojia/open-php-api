<?php
	/*

		sudo php -q install.php

	*/

include_once "scritpt-path.php";
$DirArray = array(
	"op/templates_c",
	"op/cache",
	"share",
	"share/file",
	"share/php",
	"share/php/class",
	"share/logs",	
	"redis/tmp",	
);

$FileList = array(
	//"libs/area/area-list-cache.php",
);	


foreach($DirArray as $DirName)
{
	$AbsPath = $WEB_HOME.$DirName;
	createDiv($AbsPath);

	exec("chown -R ".webUser.":".webUser." ".$AbsPath); 
}
	
foreach($FileList as $File => $UserChangeTo)
{
	$FilePath = $WEB_HOME.$File;   
		
	if(!file_exists($FilePath))
	{
		$handle = fopen($FilePath ,"w+");
		fclose($handle);
	}	
	
	if(chown($FilePath,webUser))
		echo "change owner of $FilePath to " . webUser . " succeed.\n";
	else
		echo "change owner of $FilePath to " . webUser . " failed.\n";
	
}


//程序文件初始化
include_once 'mysql-class-file.php';
