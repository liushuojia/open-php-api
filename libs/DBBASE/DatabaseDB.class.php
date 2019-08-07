<?php
#
#	数据库类处理  正式版
#	create by hiloy 2019-03-11
#	每个表必须有一个主键  扩展表可以不要主键， 但第一列必须是索引表的主键
#	
#	这里默认处理一个表数据及固定扩展名称的扩展描述表
#	根据需要可以处理连表查询数据， 不过需要特殊调整， 暂时不考虑连表更新之类的操作
#        $this -> adminDB -> tableName = "admin INNER JOIN admin_desc_ext on admin.admin_id = admin_desc_ext.admin_id";
#        unset($this -> adminDB -> editDataArray["admin_id"]);
#        $this -> adminDB -> QueryDataSql($DataItemList,$sqlString);
#

#定义一个数据项空类
/*
	type显示的是访问类型，是较为重要的一个指标，结果值从好到坏依次是：
	system > const > eq_ref > ref > fulltext > ref_or_null > index_merge > unique_subquery > index_subquery > range > index > ALL

*/
class emptyDatabaseObj{}
class CDatabaseDB{
	function __construct(){
		//动态增加函数
		$this -> initDatabase();

		return;
	}

	// 各个类重构
	function initDatabase(){}

	#
	#	链接数据库
	#
	private $MysqlHost = MysqlHost,$MysqlUser = MysqlUser,$MysqlPasswd = MysqlPasswd,$MysqlDB = MAINDBData, $MysqlEditHost = MysqlEditHost;
	private $logFile = SHARE_PATH . "/logs/sql.log";
	public $table_lr_string = "`",$errorString = "";
	public $MysqlEidtConn = null;
	public $MysqlConn = null;

	public function ConnectEditMysql(){

		if(  $this -> MysqlEditConn==null ) {

			if($this -> MysqlEditHost == $this -> MysqlHost){
				//如果是同一个服务器则没有读写分离
				$this -> MysqlEditConn = $this -> MysqlConn;
				return true;
			}

			$this -> MysqlEditConn = new mysqli($this -> MysqlEditHost, $this -> MysqlUser, $this -> MysqlPasswd, $this -> MysqlDB);

			# check connection
			if ($this -> MysqlEditConn -> connect_errno) {
				$this -> errorString = $this -> MysqlEditConn -> connect_error;

				return false;
			}
			#utf8
			$this -> MysqlEditConn -> set_charset("utf8");
		}
		return true;
	}

	public function ConnectMysql(){
		if(  $this -> MysqlConn==null ) {
			$this -> MysqlConn = new mysqli($this -> MysqlHost, $this -> MysqlUser, $this -> MysqlPasswd, $this -> MysqlDB);	

			# check connection 
			if ($this -> MysqlConn -> connect_errno) {
				$this -> errorString = $this -> MysqlConn -> connect_error;
				return false;
			}

			#utf8
			$this -> MysqlConn -> set_charset("utf8");

			if( $this -> tableNameExtFlag ){
				$this -> tableNameExtDB = new $this -> tableNameExtDBClass();
				if(!$this -> tableNameExtDB -> ConnectMysql()){
					$this -> errorString = $this -> tableNameExtDB -> errorString;
					return false;
				}
			}
		}

		if( strpos($this -> tableName, "`") !== false ) {
			$this -> table_lr_string = "";
		}

		return true;
	}

	public function CloseMysql(){
		if( $this -> MysqlConn!=null ) {
			@$this -> MysqlConn -> close();
			$this -> MysqlConn = null;
		}
		if( $this -> MysqlEditConn!=null ) {
			@$this -> MysqlEditConn -> close();
			$this -> MysqlEditConn = null;
		}

		return true;
	}

	public function realEscapeString( $string ){
		$returnString = "";
		if( $this -> MysqlConn==null ) {
			$returnString = $string;
		}else{
			$returnString = $this -> MysqlConn -> real_escape_string($string);
		}

		return $returnString;
	}


	# 
	#	初始化的时候，需要初始化的信息 
	#
	public $editDataArray = array();	#数据库 数据项，处理的数据项	"name" => "default value"
	public $tableName = "";				#数据库 表名字  主表 基本的字段 都要做索引， 不然把数据放扩展表   数据格式：数据库.表
										// 可以是连表数据 不过连表数据请将相同的项目unset掉 
										//  unset($this -> adminDB -> editDataArray["admin_id"]);
	public $tableItemClass = "";		#表的结构类
	public $havePrimaryKey = true;		#表是否有主键  	true 有   false 没有
	public $primaryKey = "";			#表的主键 自动编号

	public $databaseOnly = "";			#数据库
	public $tableNameOnly = "";			#表名字

	//扩展表
	public $tableNameExtDB;				#扩展表的执行DB
	public $tableNameExtDBClass;		#扩展表的执行类名
	public $tableNameExtFlag = false;	#是否有扩展表


	#
	#	需要重构的函数
	#
	#初始化数据
	public function initData(&$DataObj){
		return true;
	}

	public function BuileQuerySqlString( $searchKey ){
		$sqlString = "";
		return $sqlString;
	}

	#----------------------------------------------------

	#直接处理数据库，该方法适用直接执行sql
	public function ExcuteSql( $sqlString ){
		if( $sqlString=="" )
			return false;

		if( !$this -> ConnectEditMysql() )
			return false;

		$this -> writeLog($sqlString);

		//echo $sqlString;
		return $this -> MysqlEditConn -> query($sqlString);
	}

	#优化表
	public function OptimizeTable( $sqlString ){
		return $this -> ExcuteSql( "OPTIMIZE TABLE " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string . ";" );
	}

	//执行锁表动作
	public function LockTable(){
		$sqlString = "LOCK TABLE " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string . " WRITE";
		if(!$this->ExcuteSql($sqlString))
			return FALSE;

		return TRUE;
	}	

	//执行解锁动作
	public function UnLockTable(){
		$sqlString = "UNLOCK TABLES";
		if(!$this->ExcuteSql($sqlString))
			return FALSE;

		return TRUE;
	}

	#创建
	public function CreateData( &$DataObj ){
		$tmp = (array) $DataObj;
		$sqlString1 = $sqlString2 = '';
		foreach( $this -> editDataArray as $name => $value ) {

			if( $this -> havePrimaryKey && $this -> primaryKey==$name )
				continue;

			$sqlString1 .= ",`" . $name."`";
			if( !array_key_exists($name,$tmp) ){
				//创建的时候需要初始化下没有的变量，以便后期拓展
				$sqlString2 .= ",'" . $value .  "'";
			}else{
				$sqlString2 .= ",'" . $this -> realEscapeString( $DataObj -> {$name} ) . "'";
			}

		}
		$sqlString = "insert into " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string . " (" . substr($sqlString1,1) . ") values(" . substr($sqlString2,1) . ")";


		if( !$this -> ExcuteSql($sqlString) )
			return false;

		if($this -> havePrimaryKey){
			$DataObj -> {$this->primaryKey} = $this -> MysqlEditConn -> insert_id;
		}

		#创建附表数据
		$flag = true;
		if( $this -> tableNameExtFlag ){
			$flag = $this -> tableNameExtDB -> CreateData( $DataObj );
		}
		return $flag;
	}


	#更新
	#
	#	$sqlString		筛选  sql字符串
	#	$editDataArray	影响的值
	#
	public function updateDataSql($editString,$sqlString){

		if( $editString=="" )
			return false;

		$sqlString = "update " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string .
			" set " . $editString .
			" where 1 " . $sqlString;
		//echo $sqlString;exit();
		// echo $sqlString."\n";

		return $this -> ExcuteSql($sqlString);
	}

	public function UpdateData( &$DataObj ){
		//这里是完整更新数据包括扩展表

		if(is_array($DataObj))
			$DataObj = (object)($DataObj);

		$sqlString = " and `".$this->primaryKey."` = '".$this -> realEscapeString( $DataObj -> {$this->primaryKey} )."'";

		$tempSqlString = "";
		foreach( $this -> editDataArray as $name => $value ) {

			if( $this -> primaryKey==$name ) 
				continue;

			if( property_exists($DataObj,$name) ){
				$tempSqlString .= ",`" . $name . "` = '".$this -> realEscapeString( $DataObj -> {$name} ) . "'";
			}else{
				$tempSqlString .= ",`" . $name . "` = '".$value . "'";
			}
		}

		$flag = true;
		$flag = $this -> updateDataSql(substr($tempSqlString,1) ,$sqlString);

		#附表数据
		$flagExt = true;
		if( $this -> tableNameExtFlag && $DataObj -> table_ext_flag==1 ){
			//table_ext_flag 为安全开关， 必须打开才能更新附表的所有数据， 防止出现意外清空附表数据
			$sqlExtString = " and " . $this -> tableNameExtDB -> table_lr_string . $this -> primaryKey . $this -> tableNameExtDB -> table_lr_string .
				" = ( select " . $this -> table_lr_string . $this -> primaryKey . $this -> table_lr_string . " from " . $this->table_lr_string . $this->tableName . $this->table_lr_string . " where 1=1 " . $sqlString ." limit 1 )";

			//echo $tempSqlString . "\n";

			$flagExt = $this -> tableNameExtDB -> UpdateData( $DataObj,$sqlExtString );
		}

		return ($flag && $flagExt);
	}

	#快速更新多项数据，扩展查询更新
	public function UpdateDataQuickEditMore($editArray, $searchArray){
		if( !is_array($editArray) && count($editArray)==0 )
			return false;

		if( !is_array($searchArray) && count($searchArray)==0 )
			return false;

		$sqlString = $this -> BuileQuerySqlString( $searchArray );

		$tmpSring = '';
		foreach( $editArray as $name => $val ) {
			if( array_key_exists($name, $this -> editDataArray) ) {
				if($tmpSring!="")
					$tmpSring.=",";
				$tmpSring .= $this -> table_lr_string . $this -> realEscapeString( $name ).$this -> table_lr_string . " = '" . $this -> realEscapeString( $val ) . "'";
			}
		}

		$flag = true;
		if( $tmpSring!="" ){
			$flag = $this -> updateDataSql($tmpSring,$sqlString);
		}

		#附表数据
		$flagExt = true;
		if( $this -> tableNameExtFlag ){

			$tmpSring = '';
			foreach( $editArray as $name => $val ) {
				if( array_key_exists($name, $this -> tableNameExtDB -> editDataArray) ) {
					if($tmpSring!="")
						$tmpSring.=",";
					$tmpSring .= $this -> table_lr_string . $this -> realEscapeString( $name ).$this -> table_lr_string . " = '" . $this -> realEscapeString( $val ) . "'";
				}
			}
			if( $tmpSring!="" ){
				$sqlExtString = " and " . $this -> tableNameExtDB -> table_lr_string . $this -> primaryKey . $this -> tableNameExtDB -> table_lr_string .
					" in ( select " . $this -> table_lr_string . $this -> primaryKey . $this -> table_lr_string . " from " . $this->table_lr_string . $this->tableName . $this->table_lr_string . " where 1=1 " . $sqlString ." )";

				$flagExt = $this -> tableNameExtDB -> updateDataSql( $tmpSring,$sqlExtString );
			}

		}
		//echo $sqlString;exit();
		return ($flag && $flagExt);
	}

	#快速更新
	public function UpdateDataQuickEdit($name, $content, $primaryKey){

		if( !is_numeric($primaryKey) && $primaryKey=="")
			return false;

		return $this -> UpdateDataQuickEditMore(array(
			$name => $content,
		), array(
			$this -> primaryKey => $primaryKey,
		));
	}

	# 删除
	public function DeleteData($searchKey = array()){
		$sqlString = $this -> BuileQuerySqlString( $searchKey );
		if($sqlString=="")
			return false;

		return $this -> DeleteDataSql($sqlString);
	}

	#查询数据
	public function QueryData(&$DataItemList,$StartPos = 0 ,$Num = 20 , $searchKey = array()){
		if( !array_key_exists($this -> primaryKey,$searchKey["order_by"]))
			$searchKey["order_by"][$this -> primaryKey] = "desc";

		$sqlString = $this -> BuileQuerySqlString( $searchKey );

		if($Num!=0)
			$sqlString .= " limit $StartPos,$Num";

		//echo "\n".$sqlString;
		//print_r($searchKey);
		return $this -> QueryDataSql($DataItemList,$sqlString);
	}

	#查询数量
	public function GetNumData(&$totalNum,$searchKey = array()){
		$sqlString = $this -> BuileQuerySqlString( $searchKey );
		return $this -> GetNumDataSql($totalNum, $sqlString);
	}


	# 获取一条记录
	public function SelectOneData( &$DataObj,$searchKey = array() ){

		$sqlString = $this -> BuileQuerySqlString( $searchKey );
		if($sqlString=="")
			return false;

		$sqlString .= " limit 1";

		if(!$this -> QueryDataSql($DataItemList,$sqlString))
			return false;

		if( count($DataItemList) ==0)
			return false;

		$DataObj = array_pop($DataItemList);

		if( $this -> tableNameExtFlag ){
			if(!$this -> tableNameExtDB -> SelectOneData( $DataTmpObj,array(
				$this -> primaryKey => $DataObj -> PRIMARY_KEY,
			) )){
				//扩展表无该表的扩展记录， 这里做下安全创建下
				$DataTmpObj = new $this -> tableNameExtDB -> tableItemClass;
				$DataTmpObj -> {$this -> primaryKey} = $DataObj -> {$this -> primaryKey};
				$this -> tableNameExtDB -> CreateData( $DataTmpObj );
			}

			foreach( $DataTmpObj as $nameTmp => $valueTmp ){
				if($nameTmp=="PRIMARY_KEY")
					continue;

				$DataObj -> {$nameTmp} = trim($valueTmp);

				#记录一个安全开关， 在更新全部的时候这个没开的话不更新扩展表数据
				$DataObj -> table_ext_flag = 1;
			}
		}

		return true;

	}

	#sql执行数据
	public function DeleteDataSql($sqlString){
		if($sqlString=="")
			return false;

		if( $this -> tableNameExtFlag ) {
			//需要先干掉扩展表
			$sqlExtString = " and " . $this -> tableNameExtDB -> table_lr_string . $this -> primaryKey . $this -> tableNameExtDB -> table_lr_string .
				" in ( select " . $this -> table_lr_string . $this -> primaryKey . $this -> table_lr_string . " from " . $this->table_lr_string . $this->tableName . $this->table_lr_string . " where 1=1 " . $sqlString ." )";
			$this -> tableNameExtDB -> DeleteDataSql($sqlExtString);
		}

		$sqlDeleteString = "delete from " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string . " where 1=1 ".$sqlString;

		return $this -> ExcuteSql($sqlDeleteString);
	}

	public function QueryDataSql(&$DataItemList,$sqlString){
		if( !$this -> ConnectMysql() )
			return false;

		$DataItemList = array();
		$indataArray = array();
		$tempSqlString = "";
		foreach( $this -> editDataArray as $name => $value ) {
			if($tempSqlString!="")
				$tempSqlString .= ",";


			//$name = str_replace("`", "", $name);
			if( strstr($name,".")===false){
				$tempSqlString .= $name;
				$indataArray[$name] = $name;
			}else{
				$tmp = explode(".", $name);
				if( !array_key_exists($tmp[1], $indataArray)){
					$tempSqlString .=  $tmp[0] . "." . $tmp[1] . " as " . $tmp[1] . "";
					$indataArray[$tmp[1]] = $name;
				}
			}

		}
		$sqlString = "select ".$tempSqlString." from " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string . " where 1=1 ". $sqlString;
		//print_r($sqlString . "\n");
		//exit();
		//echo $sqlString;
		//echo "\n";

		$this -> writeLog($sqlString);

		$Result = $this -> MysqlConn -> query($sqlString);
		if(!$Result)
			return FALSE;

		$index = 0;
		while( ( $Row = $Result -> fetch_array() ) != FALSE) {

			$DataObj = new $this -> tableItemClass();
			foreach( $Row as $name => $value ) {
				if( !array_key_exists($name, $indataArray) )
					continue;

				$DataObj -> {$name} = trim($value);
			}

			#初始化数据
			$this -> initData($DataObj);

			$DataObj -> PRIMARY_KEY = $DataObj -> {$this -> primaryKey};

			$DataItemList[] = $DataObj;

		}
		$Result -> close();
		return TRUE;
	}

/*
	public function GetNumDataSql(&$totalNum,$sqlString){
		$totalNum = 0;

		$sqlString = "explain select count(`".$this -> primaryKey."`) from " . $this -> table_lr_string . $this -> tableName .  $this -> table_lr_string . " where 1=1 ". $sqlString;


		$writeString = "\n";
		$writeString .= "\n" . date("Y-m-d H:i:s");
		$writeString .= "\n" . $sqlString;
		$this -> writeFile($writeString, $this -> logFile);

		$Result = $this -> MysqlConn -> query($sqlString);
		if(!$Result)
			return FALSE;

		$index = 0;
		$Row = $Result -> fetch_array();
		$Result -> close();

		if(!$Row)
			return FALSE;

		$totalNum = (int)($Row["rows"]);

		return TRUE;
	}
*/
	//旧的获取总数, 不删除
	public function GetNumDataSql(&$totalNum,$sqlString){
		$totalNum = 0;
		$editDataArray = $this -> editDataArray;
		$this -> editDataArray = array(
			"count(`".$this -> primaryKey."`)" => 0,
		);
		$returnFlag = $this -> QueryDataSql($DataItemList, $sqlString);
		$this -> editDataArray = $editDataArray;

		if(!$returnFlag)
			return false;

		$returnData = array_pop($DataItemList);

		$totalNum = (int)($returnData -> {"count(`".$this -> primaryKey."`)"});
		return $returnFlag;
	}

	public function writeLog($sqlString){

		if( substr($sqlString, 0, strlen("explain")) == "explain" ){
			return;
		}

		$string = "";
		$string .= "\n";
		$string .= "\n" . date("Y-m-d H:i:s");
		$string .= "\nexplain " . $sqlString;

		#查看sql执行的效率
		if( LandTuDebug == 1 ){

			$sqlStringTmp = "explain  " . $sqlString;
			$Result = $this -> MysqlConn -> query($sqlStringTmp);

			$KeyArray = array();
			$valueArray = array();
			$maxStrLenArray = array();

			while( ( $Row = $Result -> fetch_array() ) != FALSE) {
				$tmp = array();
				foreach( $Row as $name => $value ) {
					if( is_numeric($name) || $name=="0"){
						continue;
					}
					if(!in_array($name,$KeyArray))
						$KeyArray[] = $name;

					$tmp[$name] = $value;

					if( strlen($name) > $maxStrLenArray[$name] ){
						$maxStrLenArray[$name] = strlen($name);
					}
					if( strlen($value) > $maxStrLenArray[$name] ){
						$maxStrLenArray[$name] = strlen($value);
					}
				}
				$valueArray[] = $tmp;
			}
			$Result -> close();


			$guoChar = "-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------";
			$valueChar = "                                                                                                                                                                                                                                                                                                                                                                                                                     ";

			$tmpStringGuo = "\n";
			foreach( $maxStrLenArray as $value){
				$tmpStringGuo .= "+" . substr($guoChar, 0, ($value+2) );
			}
			$tmpStringGuo .=  "+";


			$tmpStringName = "\n";
			foreach( $KeyArray as $key){
				$tmpStringName .= "| " . substr($key.$valueChar, 0, ($maxStrLenArray[$key]+1) );
			}
			$tmpStringName .=  "|";
			
			$tmpStringValue = "";
			foreach( $valueArray as $tmpArray){
				$tmpStringValue .= "\n";
				foreach( $tmpArray as $key => $value ){
					$tmpStringValue .= "| " .substr($value.$valueChar, 0, ($maxStrLenArray[$key]+1) );
				}
				$tmpStringValue .=  "|";
			}


			$string .= $tmpStringGuo;
			$string .= $tmpStringName;
			$string .= $tmpStringGuo;
			$string .= $tmpStringValue;
			$string .= $tmpStringGuo;

		}
		$this -> writeFile($string, $this -> logFile);

		return;
	}

	//方便写日志
	public function writeFile($content,$path){
		//return true;
		$FR = @fopen($path,"a");
		if($FR == FALSE)
			return FALSE;
		@fwrite($FR,$content);
		@fclose($FR);
		return true;
	}

}
