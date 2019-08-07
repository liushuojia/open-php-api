<?php

	include_once "scritpt-path.php";


	$DataBase = array(
		THILOYDB,
		THILOYSQLDB,
	);

	foreach( $DataBase as $MysqlDB )
	{
		echo "\n";
		echo "database " . $MysqlDB;

		$MysqlConn = new mysqli( MysqlHost, MysqlUser, MysqlPasswd, $MysqlDB);

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

