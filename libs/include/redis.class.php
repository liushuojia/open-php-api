<?php
/*
	这里只初始化基本操作, 特殊操作需要直接操作
*/

class TRedisDB
{
	public $host = redisHost;
	public $port = redisPort;
	public $timeout = 0;
	public $password = redisPasswd;
	public $db = 0;

	public $redis = null;
	public $errorMsg = "";

	//连接状态, 相隔120s重新检查连接状态
	public $connectFlag = false;
	public $connectTime = 0;

	public function init()
	{
		if( $this -> redis != null )
			return;

		$this -> redis = new Redis();
		$flag = @$this -> redis -> connect( $this -> host, $this -> port, $this -> timeout);
		if( !$flag ){
			$this -> errorMsg = "服务器连接失败";
			return;			
		}
  		if( $this -> password != "" ){
 			$this -> redis -> auth($this -> password);
 		}

 		if( $this -> db > 0 ){
 			$this -> redis -> select($this -> db);
 		}

		$this -> connectFlag = true;
		$this -> connectTime = time();
		return;
	}

	function checkStatus()
	{
		$this -> init();

		if( $this -> connectFlag && ($this -> connectTime - time()<=120) )
			return true;

		$string = @$this -> redis -> ping();

		if( $string !=="+PONG" ){
			$this -> errorMsg = "服务器连接已断开";
			return false;
		}

		$this -> connectFlag = true;
		$this -> connectTime = time();
		return true;
	}

	function cleanAll(){
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> flushAll();
	}

	function getAllKey(){
		return $this -> redis -> keys('*');
	}

	function resetValue($value) {
		$value = ( is_array($value) || is_object($value) ) ? json_encode($value) : $value;
		return $value;
	}

	function set($key,$value,$expire=0){
		if( !$this -> checkStatus() )
			return false;

		$value = $this -> resetValue($value);
    	return $expire>0 ? $this -> redis -> setex( $key, $expire, $value ) : $this -> redis -> set( $key , $value);
	}
	function get($key){
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> get($key);
	    return is_null(json_decode($result))?$result:json_decode($result,true);
	}

	function delete($key){
		if( !$this -> checkStatus() )
			return false;

		return  $this -> redis -> delete($key);
	}

	//批量操作
	function mget($keyArray){
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> mget($keyArray);

		foreach( $result as $key => $value ){
			if( !is_null(json_decode($value)) ){
				$result[$key] = json_decode($value,true);
			}
		}
	    return $result;
	}

	function mset($keyValueArray){
		if( !$this -> checkStatus() )
			return false;

		foreach( $keyValueArray as $key => $value ){
			$keyValueArray[$key] = $this -> resetValue($value);
		}

	    return $this -> redis -> mset($keyValueArray);
	}

	//持久化的返回-1，有生存时间的返回时间（单位秒）
	function timeout($key){
		if( !$this -> checkStatus() )
			return false;

		return  $this -> redis -> ttl($key);
	}

	//判断是否存在
	function exists($key){
		if( !$this -> checkStatus() )
			return false;

		return  $this -> redis -> exists($key);
	}

	//队列操作
	function lPush($key,$value){
		//插入链表头部/左侧，返回链表长度
		if( !$this -> checkStatus() )
			return false;


		$value = $this -> resetValue($value);

		return  $this -> redis -> lPush($key,$value);
	}

	function rPush($key,$value){
		//插入链表尾部/右侧，返回链表长度
		if( !$this -> checkStatus() )
			return false;

		$value = $this -> resetValue($value);

		return  $this -> redis -> rPush($key,$value);
	}

	function lSize($key){
		//插入链表尾部/右侧，返回链表长度
		if( !$this -> checkStatus() )
			return false;

		return  $this -> redis -> lSize($key);
	}

	function lRange($key, $start = 0, $stop = 0 ){
		//插入链表尾部/右侧，返回链表长度
		if( !$this -> checkStatus() )
			return false;

		if( $stop ==0 && $start==0){
			$stop = $this -> redis -> lSize($key) -1;
		} 

		return  $this -> redis -> lRange($key, $start, $stop);
	}

	function lPop($key){
		//插入链表尾部/右侧，返回链表长度
		if( !$this -> checkStatus() )
			return false;

		if( $this -> lSize($key) <=0 )
			return false;

		$result = $this -> redis -> lPop($key);
		if( !is_null(json_decode($result)) ){
			$result = json_decode($result,true);
		}
		return  $result;
	}

	function rPop($key){
		//插入链表尾部/右侧，返回链表长度
		if( !$this -> checkStatus() )
			return false;

		if( $this -> lSize($key) <=0 )
			return false;

		$result = $this -> redis -> rPop($key);
		if( !is_null(json_decode($result)) ){
			$result = json_decode($result,true);
		}
		return  $result;
	}

	//Set集合类型

	function sMembers($key){
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> sMembers($key);
		foreach( $result as $key => $value ){
			if( !is_null(json_decode($value)) ){
				$result[$key] = json_decode($value,true);
			}
		}

		return  $result;
	}

	function sAdd($key,$value){
		if( !$this -> checkStatus() )
			return false;

		$value = $this -> resetValue($value);
		return $this -> redis -> sAdd($key , $value );
	}

	//返回SET容器的成员数
	function sCard($key){
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> sCard($key);
	}

	//随机返回容器中一个元素，并移除该元素
	function sPop($key){
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> sPop($key);
	}

	//随机返回容器中一个元素，不移除该元素
	function sRandMember($key){
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> sRandMember($key);
	}


	//Hash数据类型
	function hSet($obj, $key,$value){
		if( !$this -> checkStatus() )
			return false;

		$value = $this -> resetValue($value);
		return $this -> redis -> hSet( $obj, $key, $value );
	}

	//在h表中 添加name字段 value为TK 如果字段name的value存在返回false 否则返回 true
	function hSetNx($obj, $key, $value){
		//设置内容
		if( !$this -> checkStatus() )
			return false;

		$value = $this -> resetValue($value);
		return $this -> redis -> hSetNx( $obj, $key, $value );
	}

	function hGet($obj, $key){
		//获取字段
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> hGet($obj, $key);
		if( !is_null(json_decode($result)) ){
			$result = json_decode($result,true);
		}

		return $result;
	}

	function hLen($obj){
		//获取对象长度
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> hLen($obj);
	}

	function hDel($obj, $key){
		//删除对象指定值
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> hDel($obj, $key);
	}

	function hKeys($obj){
		//获取对象所有key
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> hKeys($obj);
	}

	function hVals($obj){
		//获取对象所有value
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> hVals($obj);
		foreach( $result as $key => $value ){
			if( !is_null(json_decode($value)) ){
				$result[$key] = json_decode($value,true);
			}
		}
		return $result;
	}

	function hGetAll($obj){
		//获取对象数据  $key => $val
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> hGetAll($obj);
		foreach( $result as $key => $value ){
			if( !is_null(json_decode($value)) ){
				$result[$key] = json_decode($value,true);
			}
		}
		return $result;
	}

	function hExists($obj,$key){
		//判断email 字段是否存在与表h 不存在返回false
		if( !$this -> checkStatus() )
			return false;

		return $this -> redis -> hExists($obj);
	}

	function hMset($obj,$keyValueArray){
		//判断email 字段是否存在与表h 不存在返回false
		if( !$this -> checkStatus() )
			return false;

		foreach( $keyValueArray as $key => $value ){
			$keyValueArray[$key] = $this -> resetValue($value);
		}

		return $this -> redis -> hMset($obj,$keyValueArray);
	}

	function hMGet($obj,$keyArray){
		// 表h 批量获取字段的value
		if( !$this -> checkStatus() )
			return false;

		$result = $this -> redis -> hMGet($obj,$keyArray);
		foreach( $result as $key => $value ){
			if( !is_null(json_decode($value)) ){
				$result[$key] = json_decode($value,true);
			}
		}
		return $result;
	}

}
