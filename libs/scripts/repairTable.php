<?php

	include_once "scritpt-path.php";

    $DataBase = array();
    foreach( $workPath as $pathName ) {
        $databaseFile =  HOME . "/" . $pathName . "/include/database.php";
        if(is_file($databaseFile)){
            include $databaseFile;
            $DataBase[ $MysqlDefine["MysqlDatabase"] ] = $MysqlDefine;
        }
    }

	foreach( $DataBase as $MysqlDB => $dataArray )
	{
		echo "\n";
		echo "database " . $MysqlDB;

		$MysqlConn = new mysqli( $dataArray["MysqlHost"], $dataArray["MysqlUser"], $dataArray["MysqlPasswd"], $dataArray["MysqlDatabase"]);

		# check connection
		if ($MysqlConn -> connect_errno)
			continue;

		#utf8
		$MysqlConn -> set_charset("utf8");

		$Result = $MysqlConn -> query("show tables");

		echo "\n";
		echo "tables ";
		while( ( $Row = $Result -> fetch_array() ) != FALSE)
		{
			echo " " . $Row[0];
			$MysqlConn -> query("OPTIMIZE TABLE " . $Row[0]);
			$MysqlConn -> query("REPAIR TABLE " . $Row[0]);
		}
		$Result -> close();

		$MysqlConn -> close();
		echo "\n";
	}

