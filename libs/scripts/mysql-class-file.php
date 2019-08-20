<?php

	/*
	 * 数据库更新到结构文件，作为业务操作的基础
	 * 一旦数据库名称发生变化这里必须重新生成文件
	 */

	include_once "scritpt-path.php";

    $DataBase = array();
	foreach( $workPath as $pathName ) {
	    $databaseFile =  HOME . "/" . $pathName . "/include/database.php";
        if(is_file($databaseFile)){
            include $databaseFile;
            $DataBase[ $MysqlDefine["MysqlDatabase"] ] = $MysqlDefine;
        }
    }



	echo "\n";
	echo "rm old dir ";
	foreach( $DataBase as $DatabaseName => $val ) {
		//清理原文件夹 清理缓存文件
		echo $DatabaseName . " ";
		shell_exec( "sudo rm -rf " . PHP_CLASS_PATH . "/" . $DatabaseName . "/*" );
	}

	sleep(3);   //防止缓存文件在清理中出现冲突


	class CMysqlDescApp extends CScriptsDB
	{
		public $dataFieldType = array(
			//数字
			"TINYINT" => 0,
			"SMALLINT" => 0,
			"MEDIUMINT" => 0,
			"INT" => 0,
			"BIGINT" => 0,

			//浮点型
			"DECIMAL" => 0.00,
			"FLOAT" => 0.00,
			"DOUBLE" => 0.00,
			"REAL" => 0,

			"BIT" => 0,
			"BOOLEAN" => 0,
			"SERIAL" => 0,

			//日期
			"DATE" => "\"0000-00-00\"",
			"DATETIME" => "\"0000-00-00 00:00:00\"",
			"TIMESTAMP" => 0,
			"TIME" => "\"00:00:00\"",
			"YEAR" => "\"0000\"",
		);
		public $dataFieldTypeNumeric = array(
			//数字
			"TINYINT" => 0,
			"SMALLINT" => 0,
			"MEDIUMINT" => 0,
			"INT" => 0,
			"BIGINT" => 0,

			//浮点型
			"DECIMAL" => 0.00,
			"FLOAT" => 0.00,
			"DOUBLE" => 0.00,
			"REAL" => 0,

			"BIT" => 0,
			"BOOLEAN" => 0,
			"SERIAL" => 0,

			//日期
			"TIMESTAMP" => 0,
		);
		public $dataFieldTypeSearch = array(
			"TINYINT" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
                "_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
			),
			"INT" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"DECIMAL" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"FLOAT" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"DATE" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"DATETIME" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"TIMESTAMP" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"TIME" => array(
				"" => array( "expression" => "="),
				"_min" => array( "expression" => ">="),
				"_max" => array( "expression" => "<="),
                "_not" => array( "expression" => "!="),
            ),
			"CHAR" => array(
				"" => array( "expression" => "="),
				"_like" => array( "expression" => "like", "left" => "%", "right" => "%",),
                "_not" => array( "expression" => "!="),
            ),
			"VARCHAR" => array(
				"" => array( "expression" => "="),
				"_like" => array( "expression" => "like", "left" => "%", "right" => "%",),
                "_not" => array( "expression" => "!="),
            ),
		);
		function runApp()
		{
			$DatabaseDB = new CDatabaseDB();

            Global $DataBase;
			echo "create dir ";
			foreach( $DataBase as $DatabaseName => $MysqlDefine ) {
			    echo $DatabaseName . " ";
                $tableData = array();

                $DatabaseDB -> dataConfig( $MysqlDefine );
                if( !$DatabaseDB -> ConnectMysql() ) {
                    $this -> errorMsg("connect mysql fail");
                    return;
                }

                $databasePath = PHP_CLASS_PATH . "/" . $DatabaseName;

				//重新创建文件夹
				createDiv( $databasePath );

				$DatabaseDB -> MysqlConn -> query( "use `" . $DatabaseName . "`" );

				$sqlString = "show tables";
				$Result = $DatabaseDB -> MysqlConn -> query($sqlString);

				echo $DatabaseName . " ";

				$tableArray = array();
				while( ( $Row = $Result -> fetch_array() ) != FALSE) {
					if (!$Row)
						continue;

					$tableArray[] = trim($Row[0]);
				}
				$Result -> close();

				foreach ( $tableArray as $table_name)
				{
					//echo $table_name . " ";

					$write_in_file = "";
					$write_in_file .= "<?php";
					$write_in_file .= "\nnamespace ".$DatabaseName.";";

					$class_name = $table_name;

					//特殊初始化
					$write_in_file .= "\n\$fileTmp = LIB_PATH . \"" . "/tableInit/" . $DatabaseName . "/" . $table_name . ".php\";";
					$write_in_file .= "\nif( is_file( \$fileTmp) )";
					$write_in_file .= "\n{";
					$write_in_file .= "\n	include_once \$fileTmp;";
					$write_in_file .= "\n}";
					$write_in_file .= "\n";

					//结构类
					$write_in_file .= "\n" . "class C" . $class_name;
					$write_in_file .= "\n" . "{" ;

					$sqlString = "show full columns from `". $table_name."`";
					$ResultDesc = $DatabaseDB -> MysqlConn -> query($sqlString);

					if(!$ResultDesc)
						continue;

					$descArray = array();
					$thisPriKey = "";
					$thisFirKey = "";
					while( ( $RowDesc = $ResultDesc -> fetch_array() ) != FALSE)
					{

						if(!$RowDesc)
							continue;

						$descData = array();

						$preg = "/(\w+)\((\d+)\)/";
						preg_match($preg, $RowDesc["Type"],$pregArray);

						if( count($pregArray)==3 )
						{
							$thisType = strtoupper($pregArray[1]);
							$thisLength = $pregArray[2];
						}else{
							$thisType = strtoupper($RowDesc["Type"]);
							$thisLength = 0;
						}

						$descData["Field"] = $RowDesc["Field"];
						$descData["type"] = $thisType;
						$descData["length"] = $thisLength;
						$descData["Comment"] = $RowDesc["Comment"];

						$descArray[$descData["Field"]] = $descData;

						$thisValue = "\"\"";
						if( key_exists($thisType, $this -> dataFieldType))
							$thisValue = $this -> dataFieldType[$thisType];

						$write_in_file .= "\n	public \$" . $RowDesc["Field"] . "	=	" . $thisValue . ";";
						$write_in_file .= "		#   " . $RowDesc["Comment"];

						if( $thisFirKey=="" ){
							//没有主键默认第一个
							$thisFirKey = $RowDesc["Field"];
						}
						if( $RowDesc["Key"] == "PRI" ) {
							$thisPriKey = $RowDesc["Field"];
						}

					}
					$ResultDesc -> close();

					$havePrimaryKey = true;
					if( $thisPriKey == "" ){
						$thisPriKey = $thisFirKey;
						$havePrimaryKey = false;
					}

					$write_in_file .= "\n" . "}" ;

					//  操作类
					$write_in_file .= "\n" . "class C"  . $class_name ."DB extends \CDatabaseDB";
					$write_in_file .= "\n" . "{" ;

					$write_in_file .= "\n";
					$write_in_file .= "\n	function initDatabase()";
					$write_in_file .= "\n	{";
					$write_in_file .= "\n		\$this -> tableName = \"`" . $DatabaseName . "`.`" . $table_name .  "`\";";
					$write_in_file .= "\n		\$this -> primaryKey = \"" . $thisPriKey .  "\";";
					$write_in_file .= "\n		\$this -> havePrimaryKey = " . ($havePrimaryKey?"true":"false") .  ";";

					$write_in_file .= "\n		\$this -> tableItemClass = \"\\". $DatabaseName . "\\C".$class_name."\";";

					$write_in_file .= "\n		\$this -> editDataArray = (array)(new \$this -> tableItemClass());";

					$write_in_file .= "\n		\$this -> databaseOnly = \"" . $DatabaseName . "\";";
					$write_in_file .= "\n		\$this -> tableNameOnly = \"" . $table_name .  "\";";


					$tableNameExt = $table_name . "_desc_ext";
					if( in_array($tableNameExt,$tableArray)){
						$write_in_file .= "\n		\$this -> tableNameExtFlag = true;";
						$write_in_file .= "\n		\$this -> tableNameExtDBClass = \"\\". $DatabaseName . "\\C".$tableNameExt."DB\";";
					}

					$write_in_file .= "\n		return true;";
					$write_in_file .= "\n	}";



					$write_in_file .= "\n";
					#初始化数据
					$write_in_file .= "\n	public function initData(&\$objData)";
					$write_in_file .= "\n	{";
					$write_in_file .= "\n		if(\$objData -> {\$this -> primaryKey} == 0)";
					$write_in_file .= "\n			return false;";
					$write_in_file .= "\n";

					$functionName = "\\" . $DatabaseName . "\\" . $table_name . "\\" . "init_data";
					$write_in_file .= "\n		if( function_exists('" . $functionName ."') )";
					$write_in_file .= "\n		{";
					$write_in_file .= "\n			" . $functionName . "(\$objData);";
					$write_in_file .= "\n		}";

					$write_in_file .= "\n";
					foreach ($descArray as $key => $val)
					{
						switch (  strtolower($val["type"]) ){
							case "datetime":
								$write_in_file .= "\n		\$objData -> " . $key . "_show = date( \"Y.m.d H:i\",strtotime(\$objData -> " . $key . "));";
								break;
							case "text":
								$write_in_file .= "\n		\$objData -> " . $key . "_show = nl2br( \$objData -> " . $key . ");";
								break;
							case "int":
								if( substr($key, -1 * strlen("_time"))=="_time" ){
									$write_in_file .= "\n		\$objData -> " . $key . "_show = date( \"Y-m-d H:i:s\", \$objData -> " . $key . ");";
									$write_in_file .= "\n		\$objData -> " . $key . "_list_show = date( \"Y.m.d H:i\", \$objData -> " . $key . ");";
								}
								if( substr($key, -1 * strlen("_date"))=="_date" ){
									$write_in_file .= "\n		\$objData -> " . $key . "_show = date( \"Y-m-d\", \$objData -> " . $key . ");";
								}

								break;
						}
					}

					$write_in_file .= "\n		return true;";
					$write_in_file .= "\n	}";

					$write_in_file .= "\n";
					$write_in_file .= "\n	public function BuileQuerySqlString( \$searchKey )";
					$write_in_file .= "\n	{";
					$write_in_file .= "\n		\$sqlString = \"\";";


					$functionName = "\\" . $DatabaseName . "\\" . $table_name . "\\" . "query_string";
					$write_in_file .= "\n";
					$write_in_file .= "\n		if( function_exists('" . $functionName ."') )";
					$write_in_file .= "\n		{";
					$write_in_file .= "\n			" . $functionName . "(\$sqlString, \$searchKey, \$this);";
					$write_in_file .= "\n		}";
					

					foreach($descArray as $descData)
					{
						if( array_key_exists($descData["type"],$this -> dataFieldTypeSearch) )
						{
							$thisSqlGoData = $this -> dataFieldTypeSearch[$descData["type"]];

							foreach($thisSqlGoData as $key => $excuteArray)
							{
								$write_in_file .= "\n		if(";
								$write_in_file .= " isset(\$searchKey[\"" . $descData["Field"] . $key . "\"])";
								if( array_key_exists($descData["type"],$this -> dataFieldTypeNumeric) )
								{
									$write_in_file .= " && is_numeric(\$searchKey[\"" . $descData["Field"] . $key . "\"]) ";
								}else{
									$write_in_file .= " && trim(\$searchKey[\"" . $descData["Field"] . $key . "\"]) != \"\"";
								}
								$write_in_file .= " )";
								$write_in_file .= "\n		{";
								$write_in_file .= "\n			\$sqlString .= \" and `" . $descData["Field"] . "` " .
									$excuteArray["expression"] .
									" '" . $excuteArray["left"] . "\".".
									"\$this -> realEscapeString(\$searchKey[\"" . $descData["Field"] . $key . "\"])" .
									".\"".$excuteArray["right"] . "'\";";

								$write_in_file .= "\n		}";
							}
						}
					}


					$write_in_file .= "\n		if( is_array( \$searchKey['order_by'] ) && count(\$searchKey['order_by'])>0 )";
					$write_in_file .= "\n		{";
					$write_in_file .= "\n			\$orderString = \"\";";
					$write_in_file .= "\n			foreach( \$searchKey['order_by'] as \$key => \$val )";
					$write_in_file .= "\n			{";
					$write_in_file .= "\n				\$val = strtolower( \$val );";
					$write_in_file .= "\n				if( array_key_exists(\$key,\$this -> editDataArray) && (\$val=='' || \$val=='desc' || \$val=='asc' ) )";
					$write_in_file .= "\n				{";
					$write_in_file .= "\n					\$orderString .= (\$orderString!='')?',':'';";
					$write_in_file .= "\n					\$orderString .= \"`\" . \$key  . \"` \" . \$val . \"\";";
					$write_in_file .= "\n				}";
					$write_in_file .= "\n			}";
					//$write_in_file .= "\n			if( !array_key_exists(\$this -> primaryKey, \$searchKey['order_by']) ){";
					//$write_in_file .= "\n				\$orderString .= (\$orderString!='')?',':'';";
					//$write_in_file .= "\n				\$orderString .= ' `' . \$this -> primaryKey . '` desc';" ;
					//$write_in_file .= "\n			}";

					$write_in_file .= "\n			\$sqlString .= \" order by \" . \$orderString;";

					//$write_in_file .= "\n		}else{";
					//$write_in_file .= "\n			\$sqlString .= ' order by `' . \$this -> primaryKey . '` desc';" ;
					$write_in_file .= "\n		}";
					$write_in_file .= "\n";

					$write_in_file .= "\n		return \$sqlString;";
					$write_in_file .= "\n	}";

					$write_in_file .= "\n" . "}" ;
					$write_in_file .= "\n";

					$filePath = $databasePath . "/" . $table_name . ".class.php";
					$handle = fopen($filePath ,"w+");
					fwrite($handle, $write_in_file);
					fclose($handle);

					//---------------
					$tmpArray = array(
						"fileName" => $table_name,
						"fileNameLink" => $table_name . ".class.php",
						"fileDir" => $DatabaseName,
						"filePath" => $filePath,
						"className" => "\\" . $DatabaseName . "\\C" . $table_name,
						"classNameDB" => "\\" . $DatabaseName . "\\C" . $table_name . "DB",
					);
					$tableData[ "\"" . "\\" . $DatabaseName . "\\" . $table_name . "\"" ] = $tmpArray;

					//文件权限不需要调整， 这里只需要给网站账号读取权限即可
					//ChangePermissions($filePath);
				}

                $write_in_file = "";
                $write_in_file .= "<?php";
                foreach ($tableData as $key => $value) {
                    $write_in_file .= "\n\$DBFILEARRAY[ " . $key . " ] = array(";
                    foreach ($value as $keyItem => $valueItem) {
                        $write_in_file .= "\n	\"" .$keyItem . "\" => \"" .$valueItem . "\",";
                    }
                    $write_in_file .= "\n);";

                    $write_in_file .= "\ninclude_once \"" . $value["filePath"] . "\";";
                    $write_in_file .= "\n";
                }
                $filePath = $databasePath . "/include_all_class.php";
                $handle = fopen($filePath ,"w+");
                fwrite($handle, $write_in_file);
                fclose($handle);

            }



			$DatabaseDB -> CloseMysql();
			echo "\n";
			return;
		}

	}

	$tmp = new CMysqlDescApp;
	$tmp -> runApp();
	return;
